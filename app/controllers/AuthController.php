<?php

// ----------------------------------
// INCLUDES
// ----------------------------------

// Classe Controller parente (render, etc.)
require_once APP_PATH . '/controllers/Controller.php';

// Connexion PDO (singleton Database)
require_once BASE_PATH . '/config/database.php';

// Modèle User
require_once APP_PATH . '/models/User.php';

class AuthController extends Controller
{
    /**
     * -------------------------------------------------
     * Affiche la page de connexion
     * URL : /login
     * -------------------------------------------------
     */
    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 🔐 Si déjà connecté → redirection selon rôle
        if (isset($_SESSION['user'])) {

            if ($_SESSION['user']['role'] === 'eleve') {
                header('Location: /public/eleve');
            } elseif ($_SESSION['user']['role'] === 'enseignant') {
                header('Location: /public/enseignant');
            }

            exit;
        }

        // Anti-cache navigateur
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        $this->render('auth/login');
    }
    /**
     * -------------------------------------------------
     * Traite le formulaire de connexion
     * URL : /authenticate (POST)
     * -------------------------------------------------
     */
    public function authenticate()
    {
        // Sécurité : uniquement en POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /public/login');
            exit;
        }

        // Récupération de la connexion PDO
        $pdo = Database::getInstance();

        // Instanciation du modèle User
        $userModel = new User($pdo);

        // Récupération des champs du formulaire
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Vérification basique
        if (empty($email) || empty($password)) {
            $this->render('auth/login', [
                'error' => 'Veuillez remplir tous les champs'
            ]);
            return;
        }

        // Recherche de l'utilisateur par email
        $user = $userModel->findByEmail($email);

        // Vérification utilisateur + mot de passe
        // ⚠️ Pour l’instant comparaison simple (pas encore password_hash)
        if (!$user || $user['SPP_UTIL_MDP'] !== $password) {
            $this->render('auth/login', [
                'error' => 'Email ou mot de passe incorrect'
            ]);
            return;
        }

        // ----------------------------------
        // CONNEXION RÉUSSIE
        // ----------------------------------

        // Démarrage de la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Stockage des infos utilisateur en session
        $_SESSION['user'] = [
            'id'   => $user['SPP_UTIL_ID'],
            'nom'  => $user['SPP_UTIL_NOM'],
            'role' => $user['SPP_UTIL_ROLE']
        ];

        // ----------------------------------
        // REDIRECTION SELON LE RÔLE
        // ----------------------------------

        if ($user['SPP_UTIL_ROLE'] === 'eleve') {
            header('Location: /public/eleve');
        } elseif ($user['SPP_UTIL_ROLE'] === 'enseignant') {
            header('Location: /public/enseignant');
        } else {
            // Rôle inconnu → sécurité
            session_destroy();
            header('Location: /public/login');
        }

        exit;
    }

    /**
     * -------------------------------------------------
     * Déconnexion
     * URL : /logout
     * -------------------------------------------------
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
        header('Location: /public/login');
        exit;
    }
}
