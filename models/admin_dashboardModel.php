<?php
require_once __DIR__ . '/../config.php';

class AdminDashboardModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerColegios()
    {
        $stmt = $this->db->query("SELECT Id, Nombre FROM Colegios ORDER BY Nombre");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function obtenerCursos($colegioId = null)
    {
        $sql = "SELECT Id, Nombre FROM Cursos";
        $params = [];
        if ($colegioId) {
            $sql .= " WHERE Colegio_Id = :colegioId";
            $params['colegioId'] = $colegioId;
        }
        $sql .= " ORDER BY Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTotalPedidos($colegioId, $cursoId, $fechaDesde, $fechaHasta)
    {
        $params = [];
        $where = [];

        if ($colegioId) {
            $where[] = "h.Colegio_Id = :colegioId";
            $params['colegioId'] = $colegioId;
        }
        if ($cursoId) {
            $where[] = "h.Curso_Id = :cursoId";
            $params['cursoId'] = $cursoId;
        }
        if ($fechaDesde) {
            $where[] = "pc.Fecha_pedido >= :fechaDesde";
            $params['fechaDesde'] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta) {
            $where[] = "pc.Fecha_pedido <= :fechaHasta";
            $params['fechaHasta'] = $fechaHasta . ' 23:59:59';
        }

        $sql = "SELECT COUNT(*) FROM Pedidos_Comida pc JOIN Hijos h ON h.Id = pc.Hijo_Id";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function obtenerTotalUsuarios($colegioId, $cursoId)
    {
        $params = [];
        $where = [];
        $usuarioFilter = $this->buildUsuarioFilter('u.Id', $colegioId, $cursoId, $params, 'usr_');
        if ($usuarioFilter) {
            $where[] = $usuarioFilter;
        }

        $sql = "SELECT COUNT(DISTINCT u.Id) FROM Usuarios u";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function obtenerTotalSaldoPendiente($colegioId, $cursoId, $fechaDesde, $fechaHasta)
    {
        $params = [];
        $where = ["ps.Estado = 'Pendiente de aprobacion'"];
        $usuarioFilter = $this->buildUsuarioFilter('ps.Usuario_Id', $colegioId, $cursoId, $params, 'sp_');
        if ($usuarioFilter) {
            $where[] = $usuarioFilter;
        }
        if ($fechaDesde) {
            $where[] = "ps.Fecha_pedido >= :sp_fechaDesde";
            $params['sp_fechaDesde'] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta) {
            $where[] = "ps.Fecha_pedido <= :sp_fechaHasta";
            $params['sp_fechaHasta'] = $fechaHasta . ' 23:59:59';
        }

        $sql = "SELECT COUNT(*) FROM Pedidos_Saldo ps WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function obtenerTotalSaldoAprobado($colegioId, $cursoId, $fechaDesde, $fechaHasta)
    {
        $params = [];
        $where = ["ps.Estado = 'Aprobado'"];
        $usuarioFilter = $this->buildUsuarioFilter('ps.Usuario_Id', $colegioId, $cursoId, $params, 'sa_');
        if ($usuarioFilter) {
            $where[] = $usuarioFilter;
        }
        if ($fechaDesde) {
            $where[] = "ps.Fecha_pedido >= :sa_fechaDesde";
            $params['sa_fechaDesde'] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta) {
            $where[] = "ps.Fecha_pedido <= :sa_fechaHasta";
            $params['sa_fechaHasta'] = $fechaHasta . ' 23:59:59';
        }

        $sql = "SELECT COALESCE(SUM(ps.Saldo), 0) FROM Pedidos_Saldo ps WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    private function buildUsuarioFilter($usuarioField, $colegioId, $cursoId, &$params, $prefix)
    {
        $cond = [];
        if ($colegioId) {
            $cond[] = "h.Colegio_Id = :{$prefix}colegio";
            $params["{$prefix}colegio"] = $colegioId;
        }
        if ($cursoId) {
            $cond[] = "h.Curso_Id = :{$prefix}curso";
            $params["{$prefix}curso"] = $cursoId;
        }
        if (!$cond) {
            return '';
        }
        return "EXISTS (SELECT 1 FROM Usuarios_Hijos uh JOIN Hijos h ON h.Id = uh.Hijo_Id WHERE uh.Usuario_Id = {$usuarioField} AND " . implode(' AND ', $cond) . ")";
    }
}
