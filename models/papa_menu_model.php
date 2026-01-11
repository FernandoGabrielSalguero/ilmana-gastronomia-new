<?php
require_once __DIR__ . '/../config.php';

class PapaMenuModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerHijosPorUsuario($usuarioId)
    {
        $sql = "SELECT h.Id, h.Nombre, h.Curso_Id, c.Nivel_Educativo
                FROM Usuarios_Hijos uh
                JOIN Hijos h ON h.Id = uh.Hijo_Id
                LEFT JOIN Cursos c ON c.Id = h.Curso_Id
                WHERE uh.Usuario_Id = :usuarioId
                ORDER BY h.Nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalleHijoPorUsuario($usuarioId, $hijoId)
    {
        $sql = "SELECT h.Id, h.Nombre, h.Curso_Id, c.Nivel_Educativo
                FROM Usuarios_Hijos uh
                JOIN Hijos h ON h.Id = uh.Hijo_Id
                LEFT JOIN Cursos c ON c.Id = h.Curso_Id
                WHERE uh.Usuario_Id = :usuarioId
                AND h.Id = :hijoId
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId, 'hijoId' => $hijoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function obtenerMenusPorNivelEducativo($nivelEducativo)
    {
        $sql = "SELECT Id, Nombre, Fecha_entrega, Precio, Estado
                FROM Menú
                WHERE Estado = 'En venta'
                AND Nivel_Educativo = :nivel
                ORDER BY Fecha_entrega ASC, Nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['nivel' => $nivelEducativo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
