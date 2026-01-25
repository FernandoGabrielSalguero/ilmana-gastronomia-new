<?php
require_once __DIR__ . '/../config.php';

class RepresentanteDashboardModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerCursosConPedidos($representanteId, $fechaEntrega)
    {
        $sql = "SELECT DISTINCT
                c.Id AS Curso_Id,
                c.Nombre AS Curso_Nombre,
                h.Id AS Hijo_Id,
                h.Nombre AS Alumno,
                pc.Estado,
                pc.motivo_cancelacion
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = h.Colegio_Id
            WHERE rc.Representante_Id = :representanteId
                AND pc.Fecha_entrega = :fechaEntrega
            ORDER BY c.Nombre, h.Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'representanteId' => $representanteId,
            'fechaEntrega' => $fechaEntrega
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerResumenPedidosPorCurso($representanteId, $fechaEntrega)
    {
        $sql = "SELECT
                c.Id AS Curso_Id,
                c.Nombre AS Curso_Nombre,
                COUNT(pc.Id) AS Total
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = h.Colegio_Id
            WHERE rc.Representante_Id = :representanteId
              AND pc.Fecha_entrega = :fechaEntrega
              AND pc.Estado <> 'Cancelado'
            GROUP BY c.Id, c.Nombre
            ORDER BY c.Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'representanteId' => $representanteId,
            'fechaEntrega' => $fechaEntrega
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTotalPedidosDia($representanteId, $fechaEntrega)
    {
        $sql = "SELECT COUNT(pc.Id) AS Total
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = h.Colegio_Id
            WHERE rc.Representante_Id = :representanteId
              AND pc.Fecha_entrega = :fechaEntrega
              AND pc.Estado <> 'Cancelado'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'representanteId' => $representanteId,
            'fechaEntrega' => $fechaEntrega
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['Total'] : 0;
    }
}
