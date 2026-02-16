<?php

class Model
{
    protected static function getDB()
    {
        require_once BASE_PATH . '/config/database.php';
        return Database::getInstance();
    }
}
