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

    header('Content-Type: application/json');
    echo json_encode([
        'ok' => $resultado['ok'],
        'errores' => $resultado['errores'],
        'pedidoIds' => $resultado['pedidoIds'],
        'total' => $resultado['total'],
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
