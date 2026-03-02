<?php

require_once APP_PATH . '/controllers/Controller.php';
require_once BASE_PATH . '/config/database.php';
require_once APP_PATH . '/models/Seance.php';
require_once APP_PATH . '/models/Classe.php';

// ⚠️ Protection des accès : seuls les enseignants
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'enseignant') {
    header('Location: /login');
    exit;
}

class EnseignantController extends Controller
{
    /**
     * Page principale enseignant
     */
    public function index()
    {
        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);

        // ID de l'enseignant connecté
        $enseignantId = $_SESSION['user']['id'] ?? null;
        if (!$enseignantId) {
            die("⚠️ Aucun enseignant connecté !");
        }

        // Récupération des élèves avec mise en évidence pour aujourd'hui
        $eleves = $seanceModel->getElevesByEnseignant($enseignantId);

        // Informations de l’enseignant connecté
        $enseignant = [
            'SPP_UTIL_NOM' => trim(
                ($_SESSION['user']['nom'] ?? '') . ' ' . ($_SESSION['user']['prenom'] ?? '')
            )
        ];

        // Affichage de la vue
        $this->render('home/enseignant', [
            'eleves' => $eleves,
            'enseignant' => $enseignant
        ]);
    }

    /**
     * Récupérer via AJAX toutes les séances avec leurs présences
     */
    public function getSeances()
    {
        header('Content-Type: application/json');

        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);

        $enseignantId = $_SESSION['user']['id'] ?? null;
        if (!$enseignantId) {
            echo json_encode(['status' => 'error', 'message' => 'Enseignant non connecté']);
            exit;
        }

        $seances = $seanceModel->getSeancesByEnseignant($enseignantId);
        echo json_encode(['status' => 'success', 'seances' => $seances]);
        exit;
    }



    /**
     * Valider ou modifier le statut de présence d'un élève
     */
    public function validerPresence()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $seanceId = (int)($data['seanceId'] ?? 0);
        $eleveId  = (int)($data['eleveId'] ?? 0);
        $status   = $data['status'] ?? null;

        if ($seanceId <= 0 || $eleveId <= 0 || !$status) {
            echo json_encode(['status' => 'error', 'message' => 'Données invalides']);
            return;
        }

        // Vérifier que le statut est valide
        $validStatus = ['EN ATTENTE', 'PRESENT', 'ABSENT', 'EXCUSE', 'RETARD'];
        if (!in_array($status, $validStatus)) {
            echo json_encode(['status' => 'error', 'message' => 'Statut invalide']);
            return;
        }

        $res = Seance::updateStatutPresence($eleveId, $seanceId, $status);

        if ($res) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la mise à jour du statut']);
        }
    }
    public function eleveDetails($id)
    {
        $db = Database::getInstance();

        // 1️⃣ Infos élève
        $stmt = $db->prepare("
        SELECT SPP_UTIL_ID, SPP_UTIL_NOM, SPP_UTIL_PRENOM
        FROM SPP_ELEVE
        WHERE SPP_UTIL_ID = :id
    ");
        $stmt->execute(['id' => $id]);
        $eleve = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$eleve) {
            http_response_code(404);
            echo json_encode(['error' => 'Élève introuvable']);
            return;
        }

        // 2️⃣ Pointage du jour
        $stmtJour = $db->prepare("
        SELECT s.SPP_SEAN_ID,
               s.SPP_SEAN_HEURE_DEB,
               s.SPP_SEAN_HEURE_FIN,
               s.SPP_SEAN_COMM,
               es.SPP_ENS_SEAN_STATUS
        FROM SPP_SEANCE s
        LEFT JOIN SPP_ENSEI_SEAN es 
            ON es.SPP_SEAN_ID = s.SPP_SEAN_ID
        WHERE s.SPP_UTIL_ID = :id
        AND DATE(s.SPP_SEAN_DATE) = CURDATE()
        ORDER BY s.SPP_SEAN_ID DESC
    ");
        $stmtJour->execute(['id' => $id]);
        $pointagesJour = $stmtJour->fetchAll(PDO::FETCH_ASSOC);

        // 3️⃣ Historique (hors aujourd’hui)
        $stmtHist = $db->prepare("
        SELECT 
            SPP_SEAN_DATE,
            SPP_SEAN_HEURE_DEB,
            SPP_SEAN_HEURE_FIN,
            SPP_SEAN_COMM,
            SPP_SEAN_ID
        FROM SPP_SEANCE
        WHERE SPP_UTIL_ID = :id
        ORDER BY SPP_SEAN_DATE DESC
    ");
        $stmtHist->execute(['id' => $id]);
        $historique = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'eleve' => $eleve,
            'pointagesJour' => $pointagesJour,
            'historique' => $historique
        ]);
    }
    public function updateStatut()
    {
        header('Content-Type: application/json');
        $db = Database::getInstance();

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['seanceId'], $data['statut'])) {
            echo json_encode(['status' => 'error', 'message' => 'Paramètres manquants']);
            exit;
        }

        $seanceId = (int)$data['seanceId'];
        $statut = $data['statut'];

        try {
            // 🔹 Mettre à jour le statut directement dans SPP_ENSEI_SEAN
            $sql = "UPDATE SPP_ENSEI_SEAN 
                SET SPP_ENS_SEAN_STATUS = :statut 
                WHERE SPP_SEAN_ID = :seanceId";
            $stmt = $db->prepare($sql);
            $stmt->execute(['statut' => $statut, 'seanceId' => $seanceId]);

            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Erreur SQL : ' . $e->getMessage()
            ]);
        }
        exit;
    }
    public function verifierEtCreerAbsencesAutomatiques($profId)
{
    $db = Database::getInstance();
    // 🔹 1. Récupérer les horaires de la journée
    $stmtParam = $db->query("SELECT * FROM SPP_PARAMETRE LIMIT 1");
    $param = $stmtParam->fetch(PDO::FETCH_ASSOC);

    if (!$param) return;
   

    $heureActuelle = date('H:i:s');

    $debutMatin = $param['SPP_PARA_HOR_DEB_MAT'];
    $finMatin   = $param['SPP_PARA_HOR_FIN_MAT'];
    $debutAprem = $param['SPP_PARA_HOR_DEB_APREM'];
    $finAprem   = $param['SPP_PARA_HOR_FIN_APREM'];

    $heureDebut = null;
    $heureFin   = null;
var_dump($heureActuelle, $debutMatin, $finMatin, $debutAprem, $finAprem);

    // 🔹 3. Récupérer les élèves du prof
    $stmtEleves = $db->prepare("
        SELECT u.SPP_UTIL_ID
        FROM SPP_ELEVE u
        JOIN SPP_EST_INSCRIT ei ON ei.SPP_UTIL_ID = u.SPP_UTIL_ID
        JOIN SPP_SUPERVISE s ON s.SPP_CLASSE_ID = ei.SPP_CLASSE_ID
        WHERE s.SPP_UTIL_ID = :profId
    ");
    $stmtEleves->execute(['profId' => $profId]);
    $eleves = $stmtEleves->fetchAll(PDO::FETCH_ASSOC);
    var_dump($eleves);
  
    foreach ($eleves as $eleve) {

        $eleveId = $eleve['SPP_UTIL_ID'];

        // 🔹 4. Vérifier si séance existe déjà aujourd’hui pour ce créneau
        $stmtCheck = $db->prepare("
            SELECT SPP_SEAN_ID
            FROM SPP_SEANCE 
            WHERE SPP_UTIL_ID = :eleveId
            AND DATE(SPP_SEAN_DATE) = CURDATE()
        ");
        $stmtCheck->execute([
            'eleveId' => $eleveId,
        ]);

        $seanceExist = $stmtCheck->fetch();

        if (!$seanceExist) {

            // 🔹 5. Créer séance vide
            $stmtInsertSeance = $db->prepare("
                INSERT INTO SPP_SEANCE 
                (SPP_UTIL_ID, SPP_SEAN_DATE, SPP_SEAN_HEURE_DEB, SPP_SEAN_HEURE_FIN, SPP_SEAN_COMM)
                VALUES (:eleveId, CURDATE(), :heureDebut, :heureFin, NULL)
            ");
            $stmtInsertSeance->execute([
                'eleveId' => $eleveId,
                'heureDebut' => $heureDebut,
                'heureFin' => $heureFin
            ]);

            $seanceId = $db->lastInsertId();

            // 🔹 6. Associer statut ABSENT
            $stmtInsertStatut = $db->prepare("
                INSERT INTO SPP_ENSEI_SEAN 
                (SPP_SEAN_ID, SPP_UTIL_ID, SPP_ENS_SEAN_STATUS)
                VALUES (:seanceId, :profId, 'ABSENT')
            ");
            $stmtInsertStatut->execute([
                'seanceId' => $seanceId,
                'profId' => $profId
            ]);
        }
    }
}

}
