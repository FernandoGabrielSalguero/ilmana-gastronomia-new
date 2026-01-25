<?php
// Mostrar errores en pantalla (util en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesion y proteger acceso
session_start();

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
$pedidosPorCurso = $model->obtenerPedidosPorCurso($colegioId, $cursoId, $fechaDesde, $fechaHasta);
$pedidosDiarios = $model->obtenerPedidosDiariosPorCurso($colegioId, $cursoId, $fechaDesde, $fechaHasta);

$seriesPorCurso = [];
foreach ($pedidosDiarios as $row) {
    $key = $row['ColegioId'] . '-' . $row['CursoId'];
    if (!isset($seriesPorCurso[$key])) {
        $seriesPorCurso[$key] = [];
    }
    $seriesPorCurso[$key][] = [
        'dia' => $row['Dia'],
        'total' => (int) $row['Total'],
    ];
}

$tablaPedidos = [];
foreach ($pedidosPorCurso as $row) {
    $key = $row['ColegioId'] . '-' . $row['CursoId'];
    $tablaPedidos[] = [
        'colegio' => $row['ColegioNombre'] ?? '',
        'curso' => $row['CursoNombre'] ?? '',
        'total' => (int) $row['Total'],
        'series' => $seriesPorCurso[$key] ?? [],
    ];
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'totalSaldoAprobado' => round($totalSaldoAprobado, 2),
        'saldoPendiente' => round($saldoPendiente, 2),
        'totalPedidosSaldo' => $totalPedidosSaldo,
        'totalPedidosComida' => $totalPedidosComida,
        'totalPapas' => $totalPapas,
        'totalHijos' => $totalHijos,
        'tablaPedidos' => $tablaPedidos,
        'cursos' => $cursos,
        'cursoId' => $cursoId,
    ]);
    exit;
}
