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
}
