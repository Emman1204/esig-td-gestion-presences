<?php

require_once CORE_PATH . '/Controller.php';
require_once BASE_PATH . '/config/database.php';
require_once APP_PATH . '/models/Seance.php';


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

        // ⚠️ Enseignant simulé
        $enseignantId = 1;

        $seances = $seanceModel->findByEnseignant($enseignantId);

        $this->render('enseignant/index', [
            'seances' => $seances
        ]);
    }
}
