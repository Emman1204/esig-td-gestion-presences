<?php

require_once CORE_PATH . '/Controller.php';
require_once BASE_PATH . '/config/database.php';
require_once APP_PATH . '/models/Seance.php';

// ⚠️ Protection des accès : seuls les élèves
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'eleve') {
    header('Location: /login');
    exit;
}

class EleveController extends Controller
{
    /**
     * Page principale de l'élève
     */
    public function index()
    {
        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);

        $eleveId = $_SESSION['user']['id'];

        // ⚡ Récupérer la séance du jour (si l'élève a déjà pointé départ ou fin)
        $seanceDuJour = $seanceModel->findTodayByEleve($eleveId);

        // ⚡ Récupérer la séance en cours (si départ marqué mais pas fin)
        $seanceEnCours = $seanceModel->getCurrentSeance($eleveId);

        // ⚡ Passer les deux informations à la vue
        $this->render('home/eleve', [
            'seanceDuJour' => $seanceDuJour,
            'seanceEnCours' => $seanceEnCours
        ]);
    }


    /**
     * Ancienne méthode (ne pas toucher)
     */
    public function presence()
    {
        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);

        $seanId = (int)($_POST['seanId'] ?? 0);
        $heure  = date('Y-m-d H:i:s');

        if ($seanId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Séance invalide']);
            return;
        }

        $ok = $seanceModel->marquerPresence($seanId, $heure);

        echo json_encode([
            'success' => $ok,
            'heure'   => $heure
        ]);
    }

    /**
     * AJAX : Départ / Fin
     */
    public function marquerPresence()
    {
        header('Content-Type: application/json');

        // -------------------------------
        // Récupération des données JSON depuis le front
        // -------------------------------
        $input = json_decode(file_get_contents('php://input'), true);

        $action   = $input['action'] ?? null;
        $heure    = $input['heure'] ?? null;
        $seanceId = (int)($input['seanceId'] ?? 0);

        if (!$action || !$heure) {
            echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
            exit;
        }

        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);
        $eleveId = $_SESSION['user']['id'];

        // =====================================
        // CAS 1 : Départ
        // =====================================
        if ($action === 'depart') {
            // ⚡ Vérifier si une séance en cours existe déjà
            $current = $seanceModel->getCurrentSeance($eleveId);
            if ($current) {
                echo json_encode([
                    'status'    => 'error',
                    'message'   => 'Une séance est déjà en cours',
                    'seanceId'  => $current['SPP_SEAN_ID']
                ]);
                exit;
            }

            // 🔹 Créer une nouvelle séance pour ce départ
            $seanceId = $seanceModel->creerSeance($eleveId, date('Y-m-d'));

            if (!$seanceId) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Impossible de créer une séance'
                ]);
                exit;
            }

            // 🔹 Marquer l'heure de début
            $success = $seanceModel->updateHeureDebut($seanceId, $heure);

            echo json_encode([
                'status'   => $success ? 'success' : 'error',
                'seanceId' => $seanceId
            ]);
            exit;
        }

        // =====================================
        // CAS 2 : Fin
        // =====================================
        if ($action === 'fin') {
            if ($seanceId <= 0) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Séance invalide'
                ]);
                exit;
            }

            $success = $seanceModel->updateHeureFin($seanceId, $heure);

            echo json_encode([
                'status' => $success ? 'success' : 'error'
            ]);
            exit;
        }

        // =====================================
        // Sécurité : action inconnue
        // =====================================
        echo json_encode([
            'status'  => 'error',
            'message' => 'Action inconnue'
        ]);
    }


    /**
     * AJAX : récupérer toutes les séances
     */
    public function getSeances()
    {
        header('Content-Type: application/json');

        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);

        $eleveId = $_SESSION['user']['id'];
        $seances = $seanceModel->findByEleve($eleveId);

        echo json_encode([
            'status' => 'success',
            'seances' => $seances
        ]);
        exit;
    }

    // ===================================================
    // 🆕 NOUVELLE MÉTHODE : réception du commentaire
    // ===================================================
    public function commentaire()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eleve');
            exit;
        }

        $seanceId   = (int)($_POST['seance_id'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');

        if ($seanceId <= 0 || $commentaire === '') {
            header('Location: /eleve');
            exit;
        }

        $this->updateCommentaire($seanceId, $commentaire);

        header('Location: /eleve');
        exit;
    }

    // ===================================================
    // 🆕 AJAX : mise à jour du commentaire
    // ===================================================
    public function updateCommentaire()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        $seanceId = (int)($input['seanceId'] ?? 0);
        $commentaire = trim($input['commentaire'] ?? '');

        if ($seanceId <= 0 || $commentaire === '') {
            echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
            return;
        }

        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);

        $success = $seanceModel->updateCommentaire($seanceId, $commentaire);

        echo json_encode([
            'status' => $success ? 'success' : 'error'
        ]);
    }
}
