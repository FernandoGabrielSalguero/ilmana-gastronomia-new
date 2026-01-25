<?php
require_once __DIR__ . '/../config.php';

class CocinaDashboardModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerResumenViandasPorColegioCurso($fechaEntrega)
    {
        $sql = "SELECT
                col.Nombre AS Colegio_Nombre,
                c.Nombre AS Curso_Nombre,
                COUNT(pc.Id) AS Total
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Colegios col ON col.Id = h.Colegio_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            WHERE pc.Fecha_entrega = :fechaEntrega
              AND pc.Estado <> 'Cancelado'
            GROUP BY col.Id, col.Nombre, c.Id, c.Nombre
            ORDER BY col.Nombre, c.Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'fechaEntrega' => $fechaEntrega
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPedidosCuyoPlaca($fechaEntrega)
    {
        $sql = "SELECT
                pc.id AS pedido_id,
                pc.fecha,
                u.Nombre AS usuario,
                dp.planta,
                dp.turno,
                dp.menu,
                dp.cantidad
            FROM Pedidos_Cuyo_Placa pc
            JOIN Detalle_Pedidos_Cuyo_Placa dp ON dp.pedido_id = pc.id
            LEFT JOIN Usuarios u ON u.Id = pc.usuario_id
            WHERE pc.fecha = :fechaEntrega
            ORDER BY pc.id, dp.planta, dp.turno, dp.menu";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'fechaEntrega' => $fechaEntrega
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
