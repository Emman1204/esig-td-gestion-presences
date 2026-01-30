<?php

require_once CORE_PATH . '/Controller.php';              // Classe parente Controller
require_once BASE_PATH . '/config/database.php';        // Connexion PDO existante
require_once APP_PATH . '/models/Eleve.php';            // Modèle Eleve
require_once APP_PATH . '/models/Enseignant.php';       // Modèle Enseignant (nouveau)

class HomeController extends Controller
{
    public function index()
    {
        // -------------------------------
        // TEST DU MODÈLE ELEVE
        // -------------------------------

        // Récupérer la connexion PDO
        $pdo = Database::getInstance(); // ou getConnection(), selon ta classe Database

        // Instancier le modèle Eleve
        $eleveModel = new Eleve($pdo);

        // Récupérer tous les élèves
        $eleves = $eleveModel->findAll();

        // Afficher le résultat pour vérifier (format lisible)
        echo '<pre>';
        echo "==== TEST ELEVE ====\n";
        var_dump($eleves);
        echo '</pre>';

        // -------------------------------
        // FIN TEST ELEVE
        // -------------------------------

        // -------------------------------
        // TEST DU MODÈLE ENSEIGNANT
        // -------------------------------

        // Instancier le modèle Enseignant
        $enseignantModel = new Enseignant($pdo);

        // Récupérer tous les enseignants
        $enseignants = $enseignantModel->findAll();

        // Afficher le résultat pour vérifier
        echo '<pre>';
        echo "==== TEST ENSEIGNANT ====\n";
        var_dump($enseignants);
        echo '</pre>';

        // -------------------------------
        // FIN TEST ENSEIGNANT
        // -------------------------------

        // Affichage de la vue (garder la page HTML normale)
        $this->render('home/index');
    }
}
