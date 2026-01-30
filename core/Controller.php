<?php

class Controller
{
    protected function render(string $view, array $data = [])
    {
        extract($data);

        require VIEW_PATH . '/layouts/header.php';
        require VIEW_PATH . '/' . $view . '.php';
        require VIEW_PATH . '/layouts/footer.php';
    }
}
