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
     * - Affiche la séance du jour
     * - Affiche le bouton Départ / Fin
     */
    public function index()
    {
        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);

        // Élève connecté
        $eleveId = $_SESSION['user']['id'];

        // ⚠️ Pour affichage initial du bouton, on peut récupérer la dernière séance du jour
        $seance = $seanceModel->findTodayByEleve($eleveId);

        $this->render('home/eleve', [
            'seance' => $seance
        ]);
    }

    /**
     * ⚠️ Ancienne méthode (POST classique)
     * On ne la touche pas → fonctionne déjà
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
     * ✅ MÉTHODE AJAX (JSON) pour Départ / Fin
     * - Permet plusieurs départs/fins dans la même journée
     */
    public function marquerPresence()
    {
        header('Content-Type: application/json');

        // -------------------------------
        // Récupération des données JSON depuis JS
        // -------------------------------
        $input = json_decode(file_get_contents('php://input'), true);

        $action   = $input['action'] ?? null;
        $heure    = $input['heure'] ?? null;
        $seanceId = (int)($input['seanceId'] ?? 0);

        if (!$action || !$heure) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Données manquantes'
            ]);
            exit;
        }

        $pdo = Database::getInstance();
        $seanceModel = new Seance($pdo);

        $eleveId = $_SESSION['user']['id'];

        // =====================================
        // CAS 1 : Départ
        // - À chaque départ, créer une nouvelle séance
        // - Retourner le nouvel ID pour que le JS l'utilise pour la fin
        // =====================================
        if ($action === 'depart') {

            // 🔹 Création d'une nouvelle séance pour ce départ
            $seanceId = $seanceModel->creerSeance($eleveId, date('Y-m-d'));

            if (!$seanceId) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Création de séance impossible'
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
        // - Utiliser le seanceId reçu du dernier départ
        // =====================================
        if ($action === 'fin') {

            if ($seanceId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Séance invalide'
                ]);
                exit;
            }

            // 🔹 Marquer l'heure de fin sur la séance correspondante
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
            'status' => 'error',
            'message' => 'Action inconnue'
        ]);
    }
}
