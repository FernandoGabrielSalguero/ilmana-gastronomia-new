<?php
require_once __DIR__ . '/../config.php';

class CocinaDashboardModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerMenusPorCurso($fechaEntrega)
    {
        $sql = "SELECT
                c.Nombre AS Curso_Nombre,
                c.Id AS Curso_Id,
                c.Nivel_Educativo AS Nivel_Educativo,
                m.Nombre AS Menu_Nombre,
                COUNT(pc.Id) AS Total
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            JOIN Menú m ON m.Id = pc.Menú_Id
            WHERE pc.Fecha_entrega = :fechaEntrega
              AND pc.Estado <> 'Cancelado'
            GROUP BY c.Id, c.Nombre, m.Id, m.Nombre
            ORDER BY c.Nombre, m.Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'fechaEntrega' => $fechaEntrega
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTotalPedidosDia($fechaEntrega)
    {
        $sql = "SELECT COUNT(pc.Id) AS Total
            FROM Pedidos_Comida pc
            WHERE pc.Fecha_entrega = :fechaEntrega
              AND pc.Estado <> 'Cancelado'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'fechaEntrega' => $fechaEntrega
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['Total'] : 0;
    }
}
