<?php

require_once CORE_PATH . '/Controller.php';
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
        $classeModel = new Classe($pdo);

        // 🔹 ID de l'enseignant connecté
        $enseignantId = $_SESSION['user']['id'] ?? null;
        if (!$enseignantId) {
            die("⚠️ Aucun enseignant connecté !");
        }

        // Récupérer toutes les séances assignées à cet enseignant
        $seances = $seanceModel->findByEnseignant($enseignantId);

        // Pour chaque séance, récupérer le nom de la classe
        foreach ($seances as &$s) {
            $classe = $classeModel->findById($s['SPP_CLASSE_ID'] ?? 0);
            $s['SPP_CLASSE_NOM'] = $classe['SPP_CLASSE_NOM'] ?? '-';
        }

        // Informations de l’enseignant connecté
        $enseignant = [
            'SPP_UTIL_NOM' => trim(
                ($_SESSION['user']['nom'] ?? '') . ' ' . ($_SESSION['user']['prenom'] ?? '')
            )
        ];

        // Affichage de la vue
        $this->render('home/enseignant', [
            'seances' => $seances,
            'enseignant' => $enseignant
        ]);
    }

    /**
     * Récupérer via AJAX toutes les séances avec leurs présences
     */
    public function getSeances()
    {
        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);

        $enseignantId = $_SESSION['user']['id'] ?? null;
        if (!$enseignantId) {
            echo json_encode(['status' => 'error', 'message' => 'Enseignant non connecté']);
            return;
        }

        $seances = $seanceModel->getSeancesByEnseignant($enseignantId);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'seances' => $seances]);
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
}
