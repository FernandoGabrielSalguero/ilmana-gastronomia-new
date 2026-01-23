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

require_once __DIR__ . '/../models/admin_dashboardModel.php';

$colegioId = filter_input(INPUT_GET, 'colegio', FILTER_VALIDATE_INT) ?: null;
$cursoId = filter_input(INPUT_GET, 'curso', FILTER_VALIDATE_INT) ?: null;
$fechaDesde = $_GET['fecha_desde'] ?? '';
$fechaHasta = $_GET['fecha_hasta'] ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
    $fechaDesde = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
    $fechaHasta = '';
}

$model = new AdminDashboardModel($pdo);

$colegios = $model->obtenerColegios();
$cursos = $model->obtenerCursos($colegioId);

$totalPedidosComida = $model->obtenerTotalPedidos($colegioId, $cursoId, $fechaDesde, $fechaHasta);
$totalPedidosSaldo = $model->obtenerTotalPedidosSaldo($colegioId, $cursoId, $fechaDesde, $fechaHasta);
$saldoPendiente = $model->obtenerSaldoPendiente($colegioId, $cursoId, $fechaDesde, $fechaHasta);
$totalSaldoAprobado = $model->obtenerTotalSaldoAprobado($colegioId, $cursoId, $fechaDesde, $fechaHasta);
$totalPapas = $model->obtenerTotalPapas($colegioId, $cursoId);
$totalHijos = $model->obtenerTotalHijos($colegioId, $cursoId);
