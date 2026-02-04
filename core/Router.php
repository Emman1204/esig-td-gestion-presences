<?php

require_once CORE_PATH . '/Controller.php';

class Router
{
    public function run()
    {
        // -------------------------------
        // RÉCUPÉRATION DE L’URL
        // -------------------------------
        $uri = $_SERVER['REQUEST_URI'];

        // Supprimer les paramètres GET (?x=...)
        $uri = parse_url($uri, PHP_URL_PATH);

        // -------------------------------
        // NETTOYAGE DE L’URL
        // -------------------------------

        // Supprimer /public s'il est présent
        $uri = str_replace('/public', '', $uri);

        // Supprimer index.php s'il est présent
        $uri = str_replace('/index.php', '', $uri);

        // Normalisation finale : supprimer les slashs début/fin
        $uri = trim($uri, '/');

        // -------------------------------
        // ROUTE PAR DÉFAUT
        // -------------------------------
        // Si aucune route → redirection vers la page de login
        if ($uri === '') {
            header('Location: /public/login');
            exit;
        }

        // -------------------------------
        // AUTHENTIFICATION
        // -------------------------------

        // Page de connexion
        if ($uri === 'login') {
            require_once APP_PATH . '/controllers/AuthController.php';
            (new AuthController())->login();
            return;
        }

        // Traitement du formulaire de connexion (POST)
        if ($uri === 'authenticate') {
            require_once APP_PATH . '/controllers/AuthController.php';
            (new AuthController())->authenticate();
            return;
        }

        // Déconnexion
        if ($uri === 'logout') {
            require_once APP_PATH . '/controllers/AuthController.php';
            (new AuthController())->logout();
            return;
        }

        // -------------------------------
        // ESPACE ÉLÈVE
        // -------------------------------

        // Page principale de l'élève
        if ($uri === 'eleve') {
            require_once APP_PATH . '/controllers/EleveController.php';
            (new EleveController())->index();
            return;
        }

        // AJAX : action Départ / Fin pour marquer la présence
        if ($uri === 'eleve/marquerPresence') {
            require_once APP_PATH . '/controllers/EleveController.php';
            (new EleveController())->marquerPresence();
            return;
        }

        // -------------------------------
        // ESPACE ENSEIGNANT
        // -------------------------------

        // Page principale de l'enseignant
        if ($uri === 'enseignant') {
            require_once APP_PATH . '/controllers/EnseignantController.php';
            (new EnseignantController())->index();
            return;
        }

        // -------------------------------
        // 404 - AUCUNE ROUTE TROUVÉE
        // -------------------------------
        http_response_code(404);
        echo "404 - Page introuvable";
    }
}
