<?php
require_once __DIR__ . '/../config.php';

class AdminAltaColegiosModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerColegios()
    {
        $sql = "SELECT Id, Nombre
                FROM Colegios
                ORDER BY Nombre";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function crearColegio(array $data)
    {
        return [
            'ok' => false,
            'mensaje' => 'Funcionalidad en desarrollo.'
        ];
    }
}
