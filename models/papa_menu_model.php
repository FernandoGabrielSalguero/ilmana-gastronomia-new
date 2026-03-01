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
        $sql = "SELECT h.Id, h.Nombre, h.Curso_Id, h.Colegio_Id, c.Nivel_Educativo
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
        $stmtHijoInfo = $this->db->prepare("SELECT h.Colegio_Id, c.Nivel_Educativo
            FROM Hijos h
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            WHERE h.Id = :hijoId
            LIMIT 1");
        $stmtInsert = $this->db->prepare("INSERT INTO Pedidos_Comida (Fecha_entrega, Preferencias_alimenticias, Hijo_Id, Fecha_pedido, Estado, Menú_Id)
            VALUES (:fecha_entrega, :preferencias, :hijo_id, NOW(), 'Procesando', :menu_id)");

        $menuCache = [];
        $prefCache = [];
        $hijoInfoCache = [];

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
        $seleccionesPorHijoFecha = [];
        $totalesPorHijo = [];
        foreach ($items as $item) {
            $menuData = $menuCache[$item['menuId']];
            $seleccionesPorHijo[$item['hijoId']] = ($seleccionesPorHijo[$item['hijoId']] ?? 0) + 1;
            $totalesPorHijo[$item['hijoId']] = ($totalesPorHijo[$item['hijoId']] ?? 0.0)
                + ($menuData['Precio'] !== null ? (float)$menuData['Precio'] : 0.0);
            $fechaEntrega = $menuData['Fecha_entrega'] ?? null;
            if ($fechaEntrega) {
                if (!isset($seleccionesPorHijoFecha[$item['hijoId']])) {
                    $seleccionesPorHijoFecha[$item['hijoId']] = [];
                }
                $seleccionesPorHijoFecha[$item['hijoId']][$fechaEntrega] = ($seleccionesPorHijoFecha[$item['hijoId']][$fechaEntrega] ?? 0) + 1;
            }
        }

        $colegioIds = [];
        $niveles = [];
        foreach ($totalesPorHijo as $hijoId => $totalHijo) {
            if (!isset($hijoInfoCache[$hijoId])) {
                $stmtHijoInfo->execute(['hijoId' => $hijoId]);
                $hijoInfoCache[$hijoId] = $stmtHijoInfo->fetch(PDO::FETCH_ASSOC) ?: [];
            }
            $colegioId = isset($hijoInfoCache[$hijoId]['Colegio_Id']) ? (int)$hijoInfoCache[$hijoId]['Colegio_Id'] : 0;
            $nivel = $hijoInfoCache[$hijoId]['Nivel_Educativo'] ?? null;
            if ($colegioId > 0) {
                $colegioIds[] = $colegioId;
            }
            if ($nivel) {
                $niveles[] = $nivel;
            }
        }
        $colegioIds = array_values(array_unique($colegioIds));
        $niveles = array_values(array_unique($niveles));
        $descuentosActivos = $this->obtenerDescuentosActivos($colegioIds, $niveles);
        $descuentosMap = $this->mapearDescuentos($descuentosActivos);

        foreach ($totalesPorHijo as $hijoId => $totalHijo) {
            $info = $hijoInfoCache[$hijoId] ?? [];
            $colegioId = isset($info['Colegio_Id']) ? (int)$info['Colegio_Id'] : 0;
            $nivel = $info['Nivel_Educativo'] ?? '';
            if ($colegioId <= 0 || $nivel === '') {
                continue;
            }
            $promo = $descuentosMap[$colegioId][$nivel] ?? null;
            if (!$promo) {
                continue;
            }
            $porcentaje = isset($promo['Porcentaje']) ? (float)$promo['Porcentaje'] : 0.0;
            $minPorDia = isset($promo['Viandas_Por_Dia_Min']) ? (int)$promo['Viandas_Por_Dia_Min'] : 0;
            if ($porcentaje <= 0 || $minPorDia <= 0) {
                continue;
            }
            $diasObligatorios = $this->parseDiasObligatorios($promo['Dias_Obligatorios'] ?? '');
            if (empty($diasObligatorios)) {
                continue;
            }
            $seleccionesFecha = $seleccionesPorHijoFecha[$hijoId] ?? [];
            $cumple = true;
            foreach ($diasObligatorios as $dia) {
                $cantidad = (int)($seleccionesFecha[$dia] ?? 0);
                if ($cantidad < $minPorDia) {
                    $cumple = false;
                    break;
                }
            }
            if ($cumple) {
                $descuento += $totalHijo * ($porcentaje / 100);
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

    public function obtenerDescuentosActivos(array $colegioIds, array $niveles)
    {
        if (empty($colegioIds) || empty($niveles)) {
            return [];
        }

        $placeholdersColegios = implode(',', array_fill(0, count($colegioIds), '?'));
        $placeholdersNiveles = implode(',', array_fill(0, count($niveles), '?'));

        $sql = "SELECT Id, Colegio_Id, Nivel_Educativo, Porcentaje, Viandas_Por_Dia_Min,
                       Vigencia_Desde, Vigencia_Hasta, Dias_Obligatorios, Terminos
                FROM descuentos_colegios
                WHERE Estado = 'activo'
                  AND Colegio_Id IN ($placeholdersColegios)
                  AND Nivel_Educativo IN ($placeholdersNiveles)
                  AND (Vigencia_Desde IS NULL OR Vigencia_Desde <= CURDATE())
                  AND (Vigencia_Hasta IS NULL OR Vigencia_Hasta >= NOW())
                ORDER BY Id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($colegioIds, $niveles));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function mapearDescuentos(array $items)
    {
        $map = [];
        foreach ($items as $item) {
            $colegioId = isset($item['Colegio_Id']) ? (int)$item['Colegio_Id'] : 0;
            $nivel = $item['Nivel_Educativo'] ?? '';
            if ($colegioId <= 0 || $nivel === '') {
                continue;
            }
            if (!isset($map[$colegioId])) {
                $map[$colegioId] = [];
            }
            if (!isset($map[$colegioId][$nivel])) {
                $map[$colegioId][$nivel] = $item;
                continue;
            }
            $actual = $map[$colegioId][$nivel];
            $nuevoPorcentaje = isset($item['Porcentaje']) ? (float)$item['Porcentaje'] : 0.0;
            $actualPorcentaje = isset($actual['Porcentaje']) ? (float)$actual['Porcentaje'] : 0.0;
            if ($nuevoPorcentaje > $actualPorcentaje) {
                $map[$colegioId][$nivel] = $item;
            }
        }
        return $map;
    }

    private function parseDiasObligatorios($diasRaw)
    {
        $diasRaw = trim((string)$diasRaw);
        if ($diasRaw === '') {
            return [];
        }
        $parts = preg_split('/\s*,\s*/', $diasRaw);
        $dias = [];
        foreach ($parts as $part) {
            $fecha = trim($part);
            if ($fecha !== '') {
                $dias[] = $fecha;
            }
        }
        return array_values(array_unique($dias));
    }
}
