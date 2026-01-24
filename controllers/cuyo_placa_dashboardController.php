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
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cuyo_placa') {
    die("Acceso restringido: esta pagina es solo para usuarios cuyo_placa.");
}

require_once __DIR__ . '/../models/cuyo_placa_dashboardModel.php';

$fechaDesde = $_GET['fecha_desde'] ?? '';
$fechaHasta = $_GET['fecha_hasta'] ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
    $fechaDesde = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
    $fechaHasta = '';
}

if ($fechaDesde && $fechaHasta && $fechaDesde > $fechaHasta) {
    $tmp = $fechaDesde;
    $fechaDesde = $fechaHasta;
    $fechaHasta = $tmp;
}

$model = new CuyoPlacaDashboardModel($pdo);
$totalPedidos = $model->obtenerTotalPedidos($fechaDesde, $fechaHasta);

if ($fechaDesde && $fechaHasta) {
    $rangoTexto = "Desde {$fechaDesde} hasta {$fechaHasta}";
} elseif ($fechaDesde) {
    $rangoTexto = "Desde {$fechaDesde}";
} elseif ($fechaHasta) {
    $rangoTexto = "Hasta {$fechaHasta}";
} else {
    $rangoTexto = "Todo el historial";
}

$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
$correo = $_SESSION['correo'] ?? 'Sin correo';
$usuario = $_SESSION['usuario'] ?? 'Sin usuario';
$telefono = $_SESSION['telefono'] ?? 'Sin telefono';
