<?php
require_once __DIR__ . '/../models/papa_calendarModel.php';

$model = new PapaCalendarModel($pdo);
$usuarioId = $_SESSION['usuario_id'] ?? null;

$vista = isset($_GET['vista']) ? strtolower(trim((string) $_GET['vista'])) : 'mes';
if ($vista !== 'semana') {
    $vista = 'mes';
}

$mes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) date('n');
$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : (int) date('Y');
if ($mes < 1 || $mes > 12) {
    $mes = (int) date('n');
}
if ($anio < 2000 || $anio > 2100) {
    $anio = (int) date('Y');
}

$fechaBaseParam = isset($_GET['fecha']) ? trim((string) $_GET['fecha']) : '';
$fechaBase = DateTime::createFromFormat('Y-m-d', $fechaBaseParam) ?: new DateTime('today');

if ($vista === 'semana') {
    $inicioSemana = (clone $fechaBase)->modify('monday this week');
    $finSemana = (clone $fechaBase)->modify('sunday this week');
    $desde = $inicioSemana->format('Y-m-d');
    $hasta = $finSemana->format('Y-m-d');
} else {
    $primerDiaMes = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
    $desde = $primerDiaMes->format('Y-m-d');
    $hasta = $primerDiaMes->modify('last day of this month')->format('Y-m-d');
}

$pedidos = $model->obtenerPedidosCalendario($usuarioId, $desde, $hasta);
$pedidosPorFecha = [];
foreach ($pedidos as $pedido) {
    $fechaKey = $pedido['Fecha_entrega'] ?? '';
    if ($fechaKey === '') {
        continue;
    }
    if (!isset($pedidosPorFecha[$fechaKey])) {
        $pedidosPorFecha[$fechaKey] = [];
    }
    $pedidosPorFecha[$fechaKey][] = $pedido;
}

$mesSeleccionado = $mes;
$anioSeleccionado = $anio;
$vistaSeleccionada = $vista;
$fechaBaseStr = $fechaBase->format('Y-m-d');
