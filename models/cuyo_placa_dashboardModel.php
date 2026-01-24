<?php
require_once __DIR__ . '/../config.php';

class CuyoPlacaDashboardModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerTotalPedidos($fechaDesde = '', $fechaHasta = '')
    {
        $params = [];
        $where = [];

        if ($fechaDesde) {
            $where[] = "pc.created_at >= :fechaDesde";
            $params['fechaDesde'] = $fechaDesde . ' 00:00:00';
        }

        if ($fechaHasta) {
            $where[] = "pc.created_at <= :fechaHasta";
            $params['fechaHasta'] = $fechaHasta . ' 23:59:59';
        }

        $sql = "SELECT COUNT(*) FROM Pedidos_Cuyo_Placa pc";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function obtenerResumenMenus($fechaDesde = '', $fechaHasta = '', array $plantas = [])
    {
        $params = [];
        $where = [];

        if ($fechaDesde) {
            $where[] = "pc.created_at >= :fechaDesde";
            $params['fechaDesde'] = $fechaDesde . ' 00:00:00';
        }

        if ($fechaHasta) {
            $where[] = "pc.created_at <= :fechaHasta";
            $params['fechaHasta'] = $fechaHasta . ' 23:59:59';
        }

        if ($plantas) {
            $placeholders = [];
            foreach ($plantas as $index => $planta) {
                $key = "planta{$index}";
                $placeholders[] = ':' . $key;
                $params[$key] = $planta;
            }
            $where[] = "dp.planta IN (" . implode(', ', $placeholders) . ")";
        }

        $sql = "SELECT dp.planta, dp.menu, SUM(dp.cantidad) AS total
            FROM Detalle_Pedidos_Cuyo_Placa dp
            INNER JOIN Pedidos_Cuyo_Placa pc ON pc.id = dp.pedido_id";

        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " GROUP BY dp.planta, dp.menu ORDER BY dp.planta, dp.menu";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPedidosPorPlanta($fechaDesde = '', $fechaHasta = '', array $plantas = [])
    {
        $params = [];
        $where = [];

        if ($fechaDesde) {
            $where[] = "pc.created_at >= :fechaDesde";
            $params['fechaDesde'] = $fechaDesde . ' 00:00:00';
        }

        if ($fechaHasta) {
            $where[] = "pc.created_at <= :fechaHasta";
            $params['fechaHasta'] = $fechaHasta . ' 23:59:59';
        }

        if ($plantas) {
            $placeholders = [];
            foreach ($plantas as $index => $planta) {
                $key = "planta{$index}";
                $placeholders[] = ':' . $key;
                $params[$key] = $planta;
            }
            $where[] = "dp.planta IN (" . implode(', ', $placeholders) . ")";
        }

        $sql = "SELECT dp.planta, pc.id AS pedido_id
            FROM Detalle_Pedidos_Cuyo_Placa dp
            INNER JOIN Pedidos_Cuyo_Placa pc ON pc.id = dp.pedido_id";

        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " GROUP BY dp.planta, pc.id ORDER BY dp.planta, pc.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerDetallePedidosExcel($fechaDesde = '', $fechaHasta = '', array $plantas = [])
    {
        $params = [];
        $where = [];

        if ($fechaDesde) {
            $where[] = "pc.created_at >= :fechaDesde";
            $params['fechaDesde'] = $fechaDesde . ' 00:00:00';
        }

        if ($fechaHasta) {
            $where[] = "pc.created_at <= :fechaHasta";
            $params['fechaHasta'] = $fechaHasta . ' 23:59:59';
        }

        if ($plantas) {
            $placeholders = [];
            foreach ($plantas as $index => $planta) {
                $key = "planta{$index}";
                $placeholders[] = ':' . $key;
                $params[$key] = $planta;
            }
            $where[] = "dp.planta IN (" . implode(', ', $placeholders) . ")";
        }

        $sql = "SELECT pc.id AS pedido_id,
                pc.fecha AS fecha_entrega,
                pc.created_at AS fecha_creacion,
                pc.usuario_id,
                u.Nombre AS usuario_nombre,
                dp.planta,
                dp.turno,
                dp.menu,
                dp.cantidad
            FROM Pedidos_Cuyo_Placa pc
            INNER JOIN Detalle_Pedidos_Cuyo_Placa dp ON pc.id = dp.pedido_id
            LEFT JOIN Usuarios u ON u.Id = pc.usuario_id";

        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY pc.id, dp.planta, dp.turno, dp.menu";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
