<?php

// Affichage des erreurs (dev uniquement)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Autoload simple
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Router.php';

// Lancer le routeur
$router = new Router();
$router->run();
