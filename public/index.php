<?php

// ===============================
// 1. Session (OBLIGATOIRE)
// ===============================
session_start();

// ===============================
// 2. Affichage erreurs (DEV)
// ===============================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===============================
// 3. Chargements de base
// ===============================
require_once __DIR__ . '/../config/config.php';

// 👉 AJOUTÉS suite au déplacement
require_once __DIR__ . '/../app/controllers/Controller.php';
require_once __DIR__ . '/../app/models/Model.php';

require_once __DIR__ . '/../core/Router.php';

// ===============================
// 4. Lancer le routeur
// ===============================
$router = new Router();
$router->run();
