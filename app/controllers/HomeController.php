<?php

require_once CORE_PATH . '/Controller.php';

class HomeController extends Controller
{
    public function index()
    {
        $this->render('home/index');
    }
}
