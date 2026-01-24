<?php
require_once __DIR__ . '/../config.php';

class AdminViandasColegioModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerMenuActual()
    {
        return [];
    }
}
