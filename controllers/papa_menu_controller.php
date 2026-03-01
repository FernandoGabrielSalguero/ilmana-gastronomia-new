<?php
require_once __DIR__ . '/../models/papa_menu_model.php';

$model = new PapaMenuModel($pdo);
$usuarioId = $_SESSION['usuario_id'] ?? null;
$saldoActual = $usuarioId ? $model->obtenerSaldoUsuario($usuarioId) : 0.0;
if ($usuarioId) {
    $_SESSION['saldo'] = $saldoActual;
}

$esAjax = isset($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $esAjax) {
    $selecciones = $_POST['menu_por_dia'] ?? [];
    $resultado = $model->guardarPedidosComida($usuarioId, is_array($selecciones) ? $selecciones : []);
    $saldoActual = $usuarioId ? $model->obtenerSaldoUsuario($usuarioId) : 0.0;
    if ($usuarioId) {
        $_SESSION['saldo'] = $saldoActual;
    }

    if ($resultado['ok']) {
        $totalItems = 0;
        if (is_array($selecciones)) {
            foreach ($selecciones as $porFecha) {
                if (!is_array($porFecha)) {
                    continue;
                }
                foreach ($porFecha as $menuIds) {
                    if (is_array($menuIds)) {
                        foreach ($menuIds as $menuId) {
                            if ((int) $menuId > 0) {
                                $totalItems++;
                            }
                        }
                    } elseif ((int) $menuIds > 0) {
                        $totalItems++;
                    }
                }
            }
        }
        registrarAuditoria($pdo, [
            'evento' => 'papa_pedido_comida',
            'modulo' => 'papa',
            'entidad' => 'Pedidos_Comida',
            'estado' => 'ok',
            'datos' => [
                'pedido_ids' => $resultado['pedidoIds'],
                'total' => $resultado['total'],
                'descuento' => $resultado['descuento'] ?? 0,
                'total_final' => $resultado['total_final'] ?? $resultado['total'],
                'items' => $totalItems,
            ],
        ]);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'ok' => $resultado['ok'],
        'errores' => $resultado['errores'],
        'pedidoIds' => $resultado['pedidoIds'],
        'total' => $resultado['total'],
        'descuento' => $resultado['descuento'] ?? 0,
        'totalFinal' => $resultado['total_final'] ?? $resultado['total'],
        'saldoActual' => $saldoActual,
        'saldoRestante' => $saldoActual,
        'mensaje' => $resultado['ok'] ? 'Pedido guardado correctamente.' : ''
    ]);
    exit;
}

$hijoSeleccionadoId = isset($_GET['hijo_id']) ? (int) $_GET['hijo_id'] : null;

$hijos = $usuarioId ? $model->obtenerHijosPorUsuario($usuarioId) : [];
$hijoSeleccionado = null;
$menus = [];
$descuentosPorColegioNivel = [];

if (!empty($hijos)) {
    $colegioIds = [];
    $niveles = [];
    foreach ($hijos as $hijo) {
        $colegioId = isset($hijo['Colegio_Id']) ? (int)$hijo['Colegio_Id'] : 0;
        $nivel = $hijo['Nivel_Educativo'] ?? '';
        if ($colegioId > 0) {
            $colegioIds[] = $colegioId;
        }
        if ($nivel !== '') {
            $niveles[] = $nivel;
        }
    }
    $colegioIds = array_values(array_unique($colegioIds));
    $niveles = array_values(array_unique($niveles));
    $descuentos = $model->obtenerDescuentosActivos($colegioIds, $niveles);
    foreach ($descuentos as $item) {
        $colegioId = isset($item['Colegio_Id']) ? (int)$item['Colegio_Id'] : 0;
        $nivel = $item['Nivel_Educativo'] ?? '';
        if ($colegioId <= 0 || $nivel === '') {
            continue;
        }
        if (!isset($descuentosPorColegioNivel[$colegioId])) {
            $descuentosPorColegioNivel[$colegioId] = [];
        }
        if (!isset($descuentosPorColegioNivel[$colegioId][$nivel])) {
            $descuentosPorColegioNivel[$colegioId][$nivel] = $item;
            continue;
        }
        $actual = $descuentosPorColegioNivel[$colegioId][$nivel];
        $nuevoPorcentaje = isset($item['Porcentaje']) ? (float)$item['Porcentaje'] : 0.0;
        $actualPorcentaje = isset($actual['Porcentaje']) ? (float)$actual['Porcentaje'] : 0.0;
        if ($nuevoPorcentaje > $actualPorcentaje) {
            $descuentosPorColegioNivel[$colegioId][$nivel] = $item;
        }
    }
}

if ($usuarioId && $hijoSeleccionadoId) {
    $hijoSeleccionado = $model->obtenerDetalleHijoPorUsuario($usuarioId, $hijoSeleccionadoId);
}

$niveles = [];
foreach ($hijos as $hijo) {
    if (!empty($hijo['Nivel_Educativo'])) {
        $niveles[] = $hijo['Nivel_Educativo'];
    }
}
$niveles = array_values(array_unique($niveles));
if ($niveles) {
    $menus = $model->obtenerMenusPorNivelesEducativos($niveles);
}
