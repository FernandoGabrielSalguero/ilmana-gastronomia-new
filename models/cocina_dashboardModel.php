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
                GROUP_CONCAT(DISTINCT COALESCE(pa.Nombre, h.Preferencias_Alimenticias) SEPARATOR ', ') AS Preferencias,
                COUNT(pc.Id) AS Total
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            JOIN Menú m ON m.Id = pc.Menú_Id
            LEFT JOIN Preferencias_Alimenticias pa ON pa.Id = h.Preferencias_Alimenticias
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

    public function obtenerPreferenciasPorMenuNivel($fechaEntrega)
    {
        $sql = "SELECT
                c.Nivel_Educativo AS Nivel_Educativo,
                m.Nombre AS Menu_Nombre,
                pa.Id AS Preferencia_Id,
                COALESCE(pa.Nombre, NULLIF(TRIM(h.Preferencias_Alimenticias), ''), 'Sin preferencias') AS Preferencia_Nombre,
                COUNT(pc.Id) AS Total
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            JOIN Menú m ON m.Id = pc.Menú_Id
            LEFT JOIN Preferencias_Alimenticias pa ON pa.Id = h.Preferencias_Alimenticias
            WHERE pc.Fecha_entrega = :fechaEntrega
              AND pc.Estado <> 'Cancelado'
            GROUP BY c.Nivel_Educativo, m.Nombre, pa.Id, Preferencia_Nombre
            ORDER BY m.Nombre, Preferencia_Nombre";

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

    public function obtenerFirmaPedidosDia($fechaEntrega)
    {
        $sql = "SELECT
                COUNT(pc.Id) AS Total,
                MAX(pc.Fecha_pedido) AS Max_Fecha_Pedido,
                MAX(pc.Id) AS Max_Id
            FROM Pedidos_Comida pc
            WHERE pc.Fecha_entrega = :fechaEntrega
              AND pc.Estado <> 'Cancelado'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'fechaEntrega' => $fechaEntrega
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return sha1('0|0|0');
        }

        $payload = implode('|', [
            (string) ($row['Total'] ?? 0),
            (string) ($row['Max_Fecha_Pedido'] ?? ''),
            (string) ($row['Max_Id'] ?? 0)
        ]);

        return sha1($payload);
    }
}
