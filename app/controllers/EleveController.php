<?php

require_once CORE_PATH . '/Controller.php';
require_once BASE_PATH . '/config/database.php';
require_once APP_PATH . '/models/Seance.php';


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

        // ⚠️ Pour l’instant on simule un élève connecté
        $eleveId = 1;

        // On récupère la séance du jour de l'élève
        $seance = $seanceModel->findTodayByEleve($eleveId);

        $this->render('home/eleve', [
            'seance' => $seance
        ]);
    }

    /**
     * Action AJAX : clic sur le bouton Départ / Fin
     * (sera appelée sans rechargement de page)
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
}
