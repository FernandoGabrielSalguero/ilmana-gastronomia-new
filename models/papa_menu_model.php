<?php
require_once __DIR__ . '/../config.php';

class PapaMenuModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerHijosPorUsuario($usuarioId)
    {
        $sql = "SELECT h.Id, h.Nombre, h.Curso_Id, c.Nivel_Educativo
                FROM Usuarios_Hijos uh
                JOIN Hijos h ON h.Id = uh.Hijo_Id
                LEFT JOIN Cursos c ON c.Id = h.Curso_Id
                WHERE uh.Usuario_Id = :usuarioId
                ORDER BY h.Nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalleHijoPorUsuario($usuarioId, $hijoId)
    {
        $sql = "SELECT h.Id, h.Nombre, h.Curso_Id, c.Nivel_Educativo
                FROM Usuarios_Hijos uh
                JOIN Hijos h ON h.Id = uh.Hijo_Id
                LEFT JOIN Cursos c ON c.Id = h.Curso_Id
                WHERE uh.Usuario_Id = :usuarioId
                AND h.Id = :hijoId
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId, 'hijoId' => $hijoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function obtenerMenusPorNivelEducativo($nivelEducativo)
    {
        $sql = "SELECT Id, Nombre, Fecha_entrega, Precio, Estado, Nivel_Educativo
                FROM Menú
                WHERE Estado = 'En venta'
                AND Nivel_Educativo = :nivel
                AND (Fecha_hora_compra IS NULL OR Fecha_hora_compra >= NOW())
                ORDER BY Fecha_entrega ASC, Nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['nivel' => $nivelEducativo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerMenusPorNivelesEducativos(array $niveles)
    {
        if (empty($niveles)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($niveles), '?'));
        $sql = "SELECT Id, Nombre, Fecha_entrega, Precio, Estado, Nivel_Educativo
                FROM Menú
                WHERE Estado = 'En venta'
                AND Nivel_Educativo IN ($placeholders)
                AND (Fecha_hora_compra IS NULL OR Fecha_hora_compra >= NOW())
                ORDER BY Fecha_entrega ASC, Nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($niveles));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerSaldoUsuario($usuarioId)
    {
        $sql = "SELECT Saldo FROM Usuarios WHERE Id = :usuarioId LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['Saldo'] : 0.0;
    }

    public function guardarPedidosComida($usuarioId, array $selecciones)
    {
        $errores = [];
        $pedidoIds = [];
        $total = 0.0;
        $descuento = 0.0;
        $totalFinal = 0.0;

        if (!$usuarioId) {
            return [
                'ok' => false,
                'errores' => ['Usuario no valido.'],
                'pedidoIds' => [],
                'total' => 0.0
            ];
        }

        $items = [];
        foreach ($selecciones as $hijoId => $porFecha) {
            if (!is_array($porFecha)) {
                continue;
            }
            foreach ($porFecha as $menuId) {
                $menuId = (int)$menuId;
                if ($menuId > 0) {
                    $items[] = [
                        'hijoId' => (int)$hijoId,
                        'menuId' => $menuId
                    ];
                }
            }
        }

        if (empty($items)) {
            return [
                'ok' => false,
                'errores' => ['Selecciona al menos una vianda.'],
                'pedidoIds' => [],
                'total' => 0.0,
                'descuento' => 0.0,
                'total_final' => 0.0
            ];
        }

        $stmtHijo = $this->db->prepare("SELECT 1 FROM Usuarios_Hijos WHERE Usuario_Id = :usuarioId AND Hijo_Id = :hijoId");
        $stmtMenu = $this->db->prepare("SELECT Id, Fecha_entrega, Precio FROM Menú WHERE Id = :menuId AND Estado = 'En venta' AND (Fecha_hora_compra IS NULL OR Fecha_hora_compra >= NOW()) LIMIT 1");
        $stmtPref = $this->db->prepare("SELECT Preferencias_Alimenticias FROM Hijos WHERE Id = :hijoId LIMIT 1");
        $stmtNivel = $this->db->prepare("SELECT c.Nivel_Educativo
            FROM Hijos h
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            WHERE h.Id = :hijoId
            LIMIT 1");
        $stmtDias = $this->db->prepare("SELECT COUNT(DISTINCT Fecha_entrega) AS con_fecha,
            SUM(CASE WHEN Fecha_entrega IS NULL THEN 1 ELSE 0 END) AS sin_fecha
            FROM Menú
            WHERE Estado = 'En venta'
            AND Nivel_Educativo = :nivel
            AND (Fecha_hora_compra IS NULL OR Fecha_hora_compra >= NOW())");
        $stmtInsert = $this->db->prepare("INSERT INTO Pedidos_Comida (Fecha_entrega, Preferencias_alimenticias, Hijo_Id, Fecha_pedido, Estado, Menú_Id)
            VALUES (:fecha_entrega, :preferencias, :hijo_id, NOW(), 'Procesando', :menu_id)");

        $menuCache = [];
        $prefCache = [];
        $nivelCache = [];
        $diasCache = [];

        foreach ($items as $item) {
            $stmtHijo->execute(['usuarioId' => $usuarioId, 'hijoId' => $item['hijoId']]);
            if (!$stmtHijo->fetchColumn()) {
                $errores[] = 'El alumno seleccionado no pertenece al usuario.';
                break;
            }

            if (!isset($menuCache[$item['menuId']])) {
                $stmtMenu->execute(['menuId' => $item['menuId']]);
                $menuCache[$item['menuId']] = $stmtMenu->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            if (!$menuCache[$item['menuId']]) {
                $errores[] = 'El menu seleccionado no esta disponible.';
                break;
            }
        }

        if (!empty($errores)) {
            return [
                'ok' => false,
                'errores' => $errores,
                'pedidoIds' => [],
                'total' => 0.0
            ];
        }

        foreach ($items as $item) {
            $menuData = $menuCache[$item['menuId']];
            $total += $menuData['Precio'] !== null ? (float)$menuData['Precio'] : 0.0;
        }

        $seleccionesPorHijo = [];
        $totalesPorHijo = [];
        foreach ($items as $item) {
            $menuData = $menuCache[$item['menuId']];
            $seleccionesPorHijo[$item['hijoId']] = ($seleccionesPorHijo[$item['hijoId']] ?? 0) + 1;
            $totalesPorHijo[$item['hijoId']] = ($totalesPorHijo[$item['hijoId']] ?? 0.0)
                + ($menuData['Precio'] !== null ? (float)$menuData['Precio'] : 0.0);
        }

        foreach ($totalesPorHijo as $hijoId => $totalHijo) {
            if (!isset($nivelCache[$hijoId])) {
                $stmtNivel->execute(['hijoId' => $hijoId]);
                $nivelCache[$hijoId] = $stmtNivel->fetchColumn() ?: null;
            }
            $nivel = $nivelCache[$hijoId];
            if (!$nivel) {
                continue;
            }
            if (!isset($diasCache[$nivel])) {
                $stmtDias->execute(['nivel' => $nivel]);
                $row = $stmtDias->fetch(PDO::FETCH_ASSOC) ?: ['con_fecha' => 0, 'sin_fecha' => 0];
                $conFecha = (int)($row['con_fecha'] ?? 0);
                $sinFecha = (int)($row['sin_fecha'] ?? 0);
                $diasCache[$nivel] = $conFecha + ($sinFecha > 0 ? 1 : 0);
            }
            $requiredDays = (int)($diasCache[$nivel] ?? 0);
            $seleccionados = (int)($seleccionesPorHijo[$hijoId] ?? 0);
            if ($requiredDays > 0 && $seleccionados === $requiredDays) {
                $descuento += $totalHijo * 0.1;
            }
        }

        $totalFinal = max(0.0, $total - $descuento);

        try {
            $this->db->beginTransaction();

            $stmtSaldoActual = $this->db->prepare("SELECT Saldo FROM Usuarios WHERE Id = :usuarioId LIMIT 1 FOR UPDATE");
            $stmtSaldoActual->execute(['usuarioId' => $usuarioId]);
            $saldoDisponible = (float)($stmtSaldoActual->fetchColumn() ?: 0.0);

            if ($saldoDisponible <= 0 || $saldoDisponible < $totalFinal) {
                $this->db->rollBack();
                return [
                    'ok' => false,
                    'errores' => ['Saldo insuficiente para completar el pedido.'],
                    'pedidoIds' => [],
                    'total' => 0.0,
                    'descuento' => 0.0,
                    'total_final' => 0.0
                ];
            }

            foreach ($items as $item) {
                $menuData = $menuCache[$item['menuId']];
                if (!isset($prefCache[$item['hijoId']])) {
                    $stmtPref->execute(['hijoId' => $item['hijoId']]);
                    $prefRow = $stmtPref->fetch(PDO::FETCH_ASSOC);
                    $prefCache[$item['hijoId']] = $prefRow ? $prefRow['Preferencias_Alimenticias'] : null;
                }

                $stmtInsert->execute([
                    'fecha_entrega' => $menuData['Fecha_entrega'],
                    'preferencias' => $prefCache[$item['hijoId']],
                    'hijo_id' => $item['hijoId'],
                    'menu_id' => $item['menuId']
                ]);

                $pedidoIds[] = (int)$this->db->lastInsertId();
            }

            $stmtSaldo = $this->db->prepare("UPDATE Usuarios
                SET Saldo = Saldo - :total
                WHERE Id = :usuarioId");
            $stmtSaldo->execute([
                'total' => $totalFinal,
                'usuarioId' => $usuarioId
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'ok' => false,
                'errores' => ['No se pudo guardar el pedido.'],
                'pedidoIds' => [],
                'total' => 0.0
            ];
        }

        return [
            'ok' => true,
            'errores' => [],
            'pedidoIds' => $pedidoIds,
            'total' => $total,
            'descuento' => $descuento,
            'total_final' => $totalFinal
        ];
    }
}
