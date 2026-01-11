<?php
require_once __DIR__ . '/../config.php';

class PapaDashboardModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerHijosPorUsuario($usuarioId)
    {
        $sql = "SELECT h.Id, h.Nombre 
                FROM Usuarios_Hijos uh
                JOIN Hijos h ON h.Id = uh.Hijo_Id
                WHERE uh.Usuario_Id = :usuarioId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerHijosDetallePorUsuario($usuarioId)
    {
        $sql = "SELECT 
                h.Id,
                h.Nombre,
                COALESCE(pa.Nombre, h.Preferencias_Alimenticias) AS Preferencia,
                c.Nombre AS Colegio,
                cu.Nombre AS Curso
            FROM Usuarios_Hijos uh
            JOIN Hijos h ON h.Id = uh.Hijo_Id
            LEFT JOIN Preferencias_Alimenticias pa ON pa.Id = h.Preferencias_Alimenticias
            LEFT JOIN Colegios c ON c.Id = h.Colegio_Id
            LEFT JOIN Cursos cu ON cu.Id = h.Curso_Id
            WHERE uh.Usuario_Id = :usuarioId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPedidosSaldo($usuarioId, $desde = null, $hasta = null)
    {
        $sql = "SELECT Id, Saldo, Estado, Comprobante 
            FROM Pedidos_Saldo 
            WHERE Usuario_Id = :usuarioId";

        $params = ['usuarioId' => $usuarioId];

        if ($desde && $hasta) {
            $sql .= " AND Fecha_pedido BETWEEN :desde AND :hasta";
            $params['desde'] = $desde . ' 00:00:00';
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function obtenerPedidosComida($usuarioId, $hijoId = null, $desde = null, $hasta = null)
    {
        $sql = "SELECT 
                pc.Id,
                h.Nombre AS Alumno,
                m.Nombre AS Menu,
                pc.Fecha_entrega,
                pc.Estado
            FROM Pedidos_Comida pc
            JOIN Usuarios_Hijos uh ON pc.Hijo_Id = uh.Hijo_Id
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            JOIN Menú m ON m.Id = pc.Menú_Id
            WHERE uh.Usuario_Id = :usuarioId";

        $params = ['usuarioId' => $usuarioId];

        if ($hijoId) {
            $sql .= " AND pc.Hijo_Id = :hijoId";
            $params['hijoId'] = $hijoId;
        }

        if ($desde && $hasta) {
            $sql .= " AND pc.Fecha_entrega BETWEEN :desde AND :hasta";
            $params['desde'] = $desde;
            $params['hasta'] = $hasta;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerSaldoPendiente($usuarioId)
    {
        $sql = "SELECT COALESCE(SUM(Saldo), 0) AS TotalPendiente
            FROM Pedidos_Saldo
            WHERE Usuario_Id = :usuarioId
            AND Estado = 'Pendiente de aprobación'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['TotalPendiente'] : 0.0;
    }
}
