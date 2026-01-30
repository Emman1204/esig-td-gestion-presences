<?php

require_once CORE_PATH . '/Controller.php';

class Router
{
    public function run()
    {
        $controllerName = $_GET['controller'] ?? 'home';
        $action = $_GET['action'] ?? 'index';

        $controllerClass = ucfirst($controllerName) . 'Controller';
        $controllerFile = APP_PATH . '/controllers/' . $controllerClass . '.php';

        // var_dump($controllerFile);
        // die();

        if (!file_exists($controllerFile)) {
            die('Contrôleur introuvable dans le if');
        }

        require_once $controllerFile;

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            die('Action introuvable');
        }

        $controller->$action();
    }
}
