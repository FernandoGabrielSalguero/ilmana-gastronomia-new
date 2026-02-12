<?php
require_once __DIR__ . '/../config.php';

class AdminSaldoModel
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

    public function obtenerSolicitudesSaldo($colegioId, $cursoId, $estado, $fechaDesde, $fechaHasta)
    {
        $params = [];
        $where = [];

        $usuarioFilter = $this->buildUsuarioFilter('ps.Usuario_Id', $colegioId, $cursoId, $params, 'ss_');
        if ($usuarioFilter) {
            $where[] = $usuarioFilter;
        }

        if ($estado) {
            $where[] = "ps.Estado = :estado";
            $params['estado'] = $estado;
        }

        if ($fechaDesde) {
            $where[] = "ps.Fecha_pedido >= :fechaDesde";
            $params['fechaDesde'] = $fechaDesde . ' 00:00:00';
        }

        if ($fechaHasta) {
            $where[] = "ps.Fecha_pedido <= :fechaHasta";
            $params['fechaHasta'] = $fechaHasta . ' 23:59:59';
        }

        $sql = "SELECT ps.Id, ps.Usuario_Id, ps.Saldo, ps.Estado, ps.Comprobante, ps.Observaciones, ps.Fecha_pedido,
                    u.Nombre AS UsuarioNombre, u.Usuario AS UsuarioLogin, u.Correo AS UsuarioCorreo,
                    u.Telefono AS UsuarioTelefono, u.Saldo AS UsuarioSaldo
                FROM Pedidos_Saldo ps
                JOIN Usuarios u ON u.Id = ps.Usuario_Id";

        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY ps.Id DESC LIMIT 150";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerSolicitudSaldo($pedidoId)
    {
        $stmt = $this->db->prepare("SELECT ps.Id, ps.Usuario_Id, ps.Saldo, ps.Estado, ps.Observaciones,
                u.Nombre AS UsuarioNombre, u.Usuario AS UsuarioLogin, u.Correo AS UsuarioCorreo,
                u.Telefono AS UsuarioTelefono, u.Saldo AS UsuarioSaldo
            FROM Pedidos_Saldo ps
            JOIN Usuarios u ON u.Id = ps.Usuario_Id
            WHERE ps.Id = :id
            LIMIT 1");
        $stmt->execute(['id' => $pedidoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function actualizarEstadoSaldo($pedidoId, $estado, $observaciones)
    {
        if (!$pedidoId || !$estado) {
            return ['ok' => false, 'mensaje' => 'Datos invalidos.'];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT Usuario_Id, Saldo, Estado FROM Pedidos_Saldo WHERE Id = :id LIMIT 1 FOR UPDATE");
            $stmt->execute(['id' => $pedidoId]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) {
                $this->db->rollBack();
                return ['ok' => false, 'mensaje' => 'Solicitud no encontrada.'];
            }

            if ($pedido['Estado'] !== 'Pendiente de aprobacion') {
                $this->db->rollBack();
                return ['ok' => false, 'mensaje' => 'La solicitud ya fue procesada.'];
            }

            $stmt = $this->db->prepare("UPDATE Pedidos_Saldo
                SET Estado = :estado, Observaciones = :observaciones
                WHERE Id = :id");
            $stmt->execute([
                'estado' => $estado,
                'observaciones' => $observaciones !== '' ? $observaciones : null,
                'id' => $pedidoId
            ]);

            $saldoFinal = null;
            if ($estado === 'Aprobado') {
                $stmt = $this->db->prepare("UPDATE Usuarios
                    SET Saldo = Saldo + :monto
                    WHERE Id = :usuarioId");
                $stmt->execute([
                    'monto' => $pedido['Saldo'],
                    'usuarioId' => $pedido['Usuario_Id']
                ]);

                $stmt = $this->db->prepare("SELECT Saldo FROM Usuarios WHERE Id = :usuarioId");
                $stmt->execute(['usuarioId' => $pedido['Usuario_Id']]);
                $saldoFinal = $stmt->fetchColumn();
            }

            $this->db->commit();
            return [
                'ok' => true,
                'mensaje' => 'Solicitud actualizada correctamente.',
                'saldo_final' => $saldoFinal
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la solicitud.'];
        }
    }

    public function obtenerMovimientosSaldo($filtro = '')
    {
        $params = [];
        $whereFiltro = '';
        $filtro = trim((string) $filtro);
        if ($filtro !== '') {
            $whereFiltro = " AND (u.Nombre LIKE :filtro OR u.Correo LIKE :filtro OR u.Usuario LIKE :filtro)";
            $params['filtro'] = '%' . $filtro . '%';
        }

        $sql = "
            (SELECT
                CONCAT('recarga-', ps.Id) AS MovimientoId,
                'Recarga' AS Tipo,
                u.Nombre AS UsuarioNombre,
                u.Usuario AS UsuarioLogin,
                u.Correo AS UsuarioCorreo,
                ps.Fecha_pedido AS Fecha,
                ps.Estado AS Estado,
                ps.Saldo AS Monto,
                ps.Observaciones AS Observaciones
            FROM Pedidos_Saldo ps
            JOIN Usuarios u ON u.Id = ps.Usuario_Id
            WHERE ps.Estado IN ('Aprobado', 'Cancelado'){$whereFiltro})
            UNION ALL
            (SELECT
                CONCAT('compra-', pc.Id) AS MovimientoId,
                'Compra' AS Tipo,
                u.Nombre AS UsuarioNombre,
                u.Usuario AS UsuarioLogin,
                u.Correo AS UsuarioCorreo,
                pc.Fecha_pedido AS Fecha,
                CASE WHEN pc.Estado = 'Cancelado' THEN 'Cancelado' ELSE 'Aprobado' END AS Estado,
                CASE WHEN pc.Estado = 'Cancelado' THEN COALESCE(m.Precio, 0) ELSE -COALESCE(m.Precio, 0) END AS Monto,
                pc.motivo_cancelacion AS Observaciones
            FROM Pedidos_Comida pc
            JOIN Menú m ON m.Id = pc.Menú_Id
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            JOIN Usuarios_Hijos uh ON uh.Hijo_Id = h.Id
            JOIN Usuarios u ON u.Id = uh.Usuario_Id
            WHERE pc.Estado IN ('Cancelado', 'Entregado', 'Procesando'){$whereFiltro})
            ORDER BY Fecha DESC
            LIMIT 200";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
