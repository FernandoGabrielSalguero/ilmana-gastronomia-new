<?php
require_once __DIR__ . '/../config.php';

class CuyoPlacaPedidosModel
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

    public function obtenerPedidoPorFechaUsuario($usuarioId, $fecha)
    {
        $sql = "SELECT id, usuario_id, fecha
            FROM Pedidos_Cuyo_Placa
            WHERE usuario_id = :usuarioId AND fecha = :fecha
            LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'usuarioId' => $usuarioId,
            'fecha' => $fecha,
        ]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        return $pedido ?: null;
    }

    public function obtenerPedidoPorFecha($fecha)
    {
        $sql = "SELECT id, usuario_id, fecha
            FROM Pedidos_Cuyo_Placa
            WHERE fecha = :fecha
            LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'fecha' => $fecha,
        ]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        return $pedido ?: null;
    }

    public function obtenerDetallePedido($pedidoId)
    {
        $sql = "SELECT planta, turno, menu, cantidad
            FROM Detalle_Pedidos_Cuyo_Placa
            WHERE pedido_id = :pedidoId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pedidoId' => $pedidoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPedidosPorRango($fechaDesde, $fechaHasta)
    {
        $sql = "SELECT id, fecha
            FROM Pedidos_Cuyo_Placa
            WHERE fecha BETWEEN :fechaDesde AND :fechaHasta";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearPedido($usuarioId, $fecha, array $detalles)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO Pedidos_Cuyo_Placa (usuario_id, fecha)
                VALUES (:usuarioId, :fecha)");
            $stmt->execute([
                'usuarioId' => $usuarioId,
                'fecha' => $fecha,
            ]);

            $pedidoId = (int) $this->db->lastInsertId();

            $stmtDetalle = $this->db->prepare("INSERT INTO Detalle_Pedidos_Cuyo_Placa
                (pedido_id, planta, turno, menu, cantidad)
                VALUES (:pedidoId, :planta, :turno, :menu, :cantidad)");

            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    'pedidoId' => $pedidoId,
                    'planta' => $detalle['planta'],
                    'turno' => $detalle['turno'],
                    'menu' => $detalle['menu'],
                    'cantidad' => $detalle['cantidad'],
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function actualizarPedido($pedidoId, array $detalles)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("DELETE FROM Detalle_Pedidos_Cuyo_Placa
                WHERE pedido_id = :pedidoId");
            $stmt->execute(['pedidoId' => $pedidoId]);

            $stmtDetalle = $this->db->prepare("INSERT INTO Detalle_Pedidos_Cuyo_Placa
                (pedido_id, planta, turno, menu, cantidad)
                VALUES (:pedidoId, :planta, :turno, :menu, :cantidad)");

            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    'pedidoId' => $pedidoId,
                    'planta' => $detalle['planta'],
                    'turno' => $detalle['turno'],
                    'menu' => $detalle['menu'],
                    'cantidad' => $detalle['cantidad'],
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }
}
