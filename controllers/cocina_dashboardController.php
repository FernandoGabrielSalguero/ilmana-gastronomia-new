<?php
require_once __DIR__ . '/../models/cocina_dashboardModel.php';

$fechaEntrega = $_GET['fecha_entrega'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEntrega)) {
    $fechaEntrega = date('Y-m-d');
}

$model = new CocinaDashboardModel($pdo);
$resumenMenusRaw = $model->obtenerMenusPorCurso($fechaEntrega);
$totalPedidosDia = $model->obtenerTotalPedidosDia($fechaEntrega);

$tarjetasCursos = [];
foreach ($resumenMenusRaw as $row) {
    $cursoIdRaw = $row['Curso_Id'] ?? 'sin_curso';
    $cursoId = $cursoIdRaw === null || $cursoIdRaw === '' ? 'sin_curso' : (string) $cursoIdRaw;
    $cursoNombre = trim((string) ($row['Curso_Nombre'] ?? ''));
    if ($cursoNombre === '') {
        $cursoNombre = 'Sin curso asignado';
    }

    $menuNombre = trim((string) ($row['Menu_Nombre'] ?? ''));
    if ($menuNombre === '') {
        $menuNombre = 'Menu sin nombre';
    }

    $cantidad = (int) ($row['Total'] ?? 0);

    if (!isset($tarjetasCursos[$cursoId])) {
        $tarjetasCursos[$cursoId] = [
            'id' => $cursoId,
            'nombre' => $cursoNombre,
            'total' => 0,
            'menus' => []
        ];
    }

    $tarjetasCursos[$cursoId]['menus'][] = [
        'nombre' => $menuNombre,
        'cantidad' => $cantidad
    ];
    $tarjetasCursos[$cursoId]['total'] += $cantidad;
}

$cursosTarjetas = array_values($tarjetasCursos);
