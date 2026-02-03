<?php

require_once CORE_PATH . '/Controller.php';

class HomeController extends Controller
{
    /**
     * Page d'accueil de l'application
     * - Aucun test
     * - Aucun accès BDD
     * - Simple point d'entrée
     */
    public function index()
    {
        // Affichage de la page d'accueil
        $this->render('home/index');
    }
}
