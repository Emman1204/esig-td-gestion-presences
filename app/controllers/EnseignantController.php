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

        $this->render('home/enseignant', [
            'seances' => $seances
        ]);
    }
    // Récupère les séances et les présences des élèves pour cet enseignant
    public function getSeances()
    {
        $enseignantId = $_SESSION['user_id']; // récupère ID enseignant connecté
        $seances = Seance::getSeancesByEnseignant($enseignantId);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'seances' => $seances]);
    }

    // Valide ou modifie le statut de présence d'un élève
    public function validerPresence()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $seanceId = $data['seanceId'] ?? 0;
        $eleveId  = $data['eleveId'] ?? 0;
        $status   = $data['status'] ?? null;

        if ($status && in_array($status, ['PRESENT', 'ABSENT', 'EXCUSE', 'RETARD'])) {
            $res = Seance::updateStatutPresence($eleveId, $seanceId, $status);
            if ($res) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la mise à jour du statut']);
    }
}
