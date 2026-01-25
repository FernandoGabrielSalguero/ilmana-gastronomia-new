<?php
require_once __DIR__ . '/../models/cocina_dashboardModel.php';

$fechaEntrega = $_GET['fecha_entrega'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEntrega)) {
    $fechaEntrega = date('Y-m-d');
}

$model = new CocinaDashboardModel($pdo);
$resumenViandasRaw = $model->obtenerResumenViandasPorColegioCurso($fechaEntrega);
$cuyoPlacaPedidos = $model->obtenerPedidosCuyoPlaca($fechaEntrega);

$viandasPorColegio = [];
$totalViandas = 0;
foreach ($resumenViandasRaw as $row) {
    $colegio = trim((string) ($row['Colegio_Nombre'] ?? ''));
    if ($colegio === '') {
        $colegio = 'Sin colegio';
    }

    $curso = trim((string) ($row['Curso_Nombre'] ?? ''));
    if ($curso === '') {
        $curso = 'Sin curso asignado';
    }

    $cantidad = (int) ($row['Total'] ?? 0);
    if (!isset($viandasPorColegio[$colegio])) {
        $viandasPorColegio[$colegio] = [
            'total' => 0,
            'cursos' => []
        ];
    }
    if (!isset($viandasPorColegio[$colegio]['cursos'][$curso])) {
        $viandasPorColegio[$colegio]['cursos'][$curso] = 0;
    }

    $viandasPorColegio[$colegio]['cursos'][$curso] += $cantidad;
    $viandasPorColegio[$colegio]['total'] += $cantidad;
    $totalViandas += $cantidad;
}

$totalCuyoPlaca = 0;
foreach ($cuyoPlacaPedidos as $pedido) {
    $totalCuyoPlaca += (int) ($pedido['cantidad'] ?? 0);
}
