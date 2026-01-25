<?php
require_once __DIR__ . '/../models/cocina_dashboardModel.php';

$fechaEntrega = $_GET['fecha_entrega'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEntrega)) {
    $fechaEntrega = date('Y-m-d');
}

$model = new CocinaDashboardModel($pdo);
$resumenMenusRaw = $model->obtenerMenusPorCurso($fechaEntrega);
$totalPedidosDia = $model->obtenerTotalPedidosDia($fechaEntrega);

$nivelesOrden = ['Inicial', 'Primaria', 'Secundaria'];
$nivelesTarjetas = [];
foreach ($nivelesOrden as $nivel) {
    $nivelesTarjetas[$nivel] = [
        'nivel' => $nivel,
        'total' => 0,
        'cursos' => []
    ];
}

foreach ($resumenMenusRaw as $row) {
    $nivelRaw = trim((string) ($row['Nivel_Educativo'] ?? ''));
    $nivel = in_array($nivelRaw, $nivelesOrden, true) ? $nivelRaw : 'Inicial';

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

    if (!isset($nivelesTarjetas[$nivel]['cursos'][$cursoId])) {
        $nivelesTarjetas[$nivel]['cursos'][$cursoId] = [
            'id' => $cursoId,
            'nombre' => $cursoNombre,
            'total' => 0,
            'menus' => []
        ];
    }

    $nivelesTarjetas[$nivel]['cursos'][$cursoId]['menus'][] = [
        'nombre' => $menuNombre,
        'cantidad' => $cantidad
    ];
    $nivelesTarjetas[$nivel]['cursos'][$cursoId]['total'] += $cantidad;
    $nivelesTarjetas[$nivel]['total'] += $cantidad;
}

$nivelesList = [];
foreach ($nivelesOrden as $nivel) {
    $nivelData = $nivelesTarjetas[$nivel];
    $nivelData['cursos'] = array_values($nivelData['cursos']);
    $nivelesList[] = $nivelData;
}
