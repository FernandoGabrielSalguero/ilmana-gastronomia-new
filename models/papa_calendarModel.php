<?php
require_once __DIR__ . '/../config.php';

class PapaCalendarModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerPedidosCalendario($usuarioId, $desde, $hasta)
    {
        if (!$usuarioId) {
            return [];
        }

        $sql = "SELECT
                pc.Id,
                pc.Fecha_entrega,
                pc.Estado,
                pc.motivo_cancelacion,
                h.Nombre AS Alumno,
                m.Nombre AS Menu
            FROM Pedidos_Comida pc
            JOIN Usuarios_Hijos uh ON pc.Hijo_Id = uh.Hijo_Id
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            JOIN Menǧ m ON m.Id = pc.Menǧ_Id
            WHERE uh.Usuario_Id = :usuarioId
            AND pc.Fecha_entrega BETWEEN :desde AND :hasta
            ORDER BY pc.Fecha_entrega ASC, pc.Id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'usuarioId' => $usuarioId,
            'desde' => $desde,
            'hasta' => $hasta
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
