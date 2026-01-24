<?php
// Mostrar errores en pantalla (util en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesion y proteger acceso
session_start();

// Expiracion por inactividad (20 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: /index.php?expired=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time(); // Actualiza el tiempo de actividad

// Proteccion de acceso general
if (!isset($_SESSION['usuario'])) {
    die("Acceso denegado. No has iniciado sesion.");
}

// Proteccion por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    die("Acceso restringido: esta pagina es solo para usuarios Administrador.");
}

require_once __DIR__ . '/../models/admin_saldoModel.php';

$model = new AdminSaldoModel($pdo);
$errores = [];
$mensaje = null;

$colegioId = filter_input(INPUT_GET, 'colegio', FILTER_VALIDATE_INT) ?: null;
$cursoId = filter_input(INPUT_GET, 'curso', FILTER_VALIDATE_INT) ?: null;
$estado = $_GET['estado'] ?? '';
$fechaDesde = $_GET['fecha_desde'] ?? '';
$fechaHasta = $_GET['fecha_hasta'] ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
    $fechaDesde = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
    $fechaHasta = '';
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1');
$action = $_POST['action'] ?? $_GET['action'] ?? null;

$respondJson = function (array $payload) {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['aprobar', 'cancelar'], true)) {
    $pedidoId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $observaciones = trim($_POST['observaciones'] ?? '');
    $nuevoEstado = $action === 'aprobar' ? 'Aprobado' : 'Cancelado';

    if ($pedidoId <= 0) {
        $errores[] = 'Id invalido.';
    }

    if (empty($errores)) {
        $resultado = $model->actualizarEstadoSaldo($pedidoId, $nuevoEstado, $observaciones);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

if ($action === 'list' && $isAjax) {
    $solicitudes = $model->obtenerSolicitudesSaldo($colegioId, $cursoId, $estado, $fechaDesde, $fechaHasta);
    $respondJson([
        'ok' => true,
        'items' => $solicitudes
    ]);
}

$colegios = $model->obtenerColegios();
$cursos = $model->obtenerCursos($colegioId);
$solicitudes = $model->obtenerSolicitudesSaldo($colegioId, $cursoId, $estado, $fechaDesde, $fechaHasta);
