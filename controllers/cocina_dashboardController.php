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
        'menus' => []
    ];
}

foreach ($resumenMenusRaw as $row) {
    $nivelRaw = trim((string) ($row['Nivel_Educativo'] ?? ''));
    $nivel = in_array($nivelRaw, $nivelesOrden, true) ? $nivelRaw : 'Inicial';

    $menuNombre = trim((string) ($row['Menu_Nombre'] ?? ''));
    if ($menuNombre === '') {
        $menuNombre = 'Menu sin nombre';
    }

    $cantidad = (int) ($row['Total'] ?? 0);

    if (!isset($nivelesTarjetas[$nivel]['menus'][$menuNombre])) {
        $nivelesTarjetas[$nivel]['menus'][$menuNombre] = [
            'nombre' => $menuNombre,
            'total' => 0,
            'cursos' => []
        ];
    }

    $cursoIdRaw = $row['Curso_Id'] ?? 'sin_curso';
    $cursoId = $cursoIdRaw === null || $cursoIdRaw === '' ? 'sin_curso' : (string) $cursoIdRaw;
    $cursoNombre = trim((string) ($row['Curso_Nombre'] ?? ''));
    if ($cursoNombre === '') {
        $cursoNombre = 'Sin curso asignado';
    }

    if (!isset($nivelesTarjetas[$nivel]['menus'][$menuNombre]['cursos'][$cursoId])) {
        $nivelesTarjetas[$nivel]['menus'][$menuNombre]['cursos'][$cursoId] = [
            'id' => $cursoId,
            'nombre' => $cursoNombre,
            'cantidad' => 0
        ];
    }

    $nivelesTarjetas[$nivel]['menus'][$menuNombre]['cursos'][$cursoId]['cantidad'] += $cantidad;
    $nivelesTarjetas[$nivel]['menus'][$menuNombre]['total'] += $cantidad;
    $nivelesTarjetas[$nivel]['total'] += $cantidad;
}

$nivelesList = [];
foreach ($nivelesOrden as $nivel) {
    $nivelData = $nivelesTarjetas[$nivel];
    $menusList = [];
    foreach ($nivelData['menus'] as $menuData) {
        $menuData['cursos'] = array_values($menuData['cursos']);
        $menusList[] = $menuData;
    }
    $nivelData['menus'] = $menusList;
    $nivelesList[] = $nivelData;
}

$totalesPorNivel = [
    'Inicial' => $nivelesTarjetas['Inicial']['total'] ?? 0,
    'Primaria' => $nivelesTarjetas['Primaria']['total'] ?? 0,
    'Secundaria' => $nivelesTarjetas['Secundaria']['total'] ?? 0
];

$menusResumen = [];
foreach ($resumenMenusRaw as $row) {
    $menuNombre = trim((string) ($row['Menu_Nombre'] ?? ''));
    if ($menuNombre === '') {
        $menuNombre = 'Menu sin nombre';
    }
    $nivelRaw = trim((string) ($row['Nivel_Educativo'] ?? ''));
    $nivel = in_array($nivelRaw, $nivelesOrden, true) ? $nivelRaw : 'Inicial';
    $cantidad = (int) ($row['Total'] ?? 0);

    if (!isset($menusResumen[$menuNombre])) {
        $menusResumen[$menuNombre] = [
            'nombre' => $menuNombre,
            'total' => 0,
            'niveles' => [
                'Inicial' => 0,
                'Primaria' => 0,
                'Secundaria' => 0
            ],
            'niveles_prefs' => [
                'Inicial' => [],
                'Primaria' => [],
                'Secundaria' => []
            ]
        ];
    }

    $menusResumen[$menuNombre]['total'] += $cantidad;
    $menusResumen[$menuNombre]['niveles'][$nivel] += $cantidad;

    $prefRaw = trim((string) ($row['Preferencias'] ?? ''));
    if ($prefRaw !== '') {
        $prefItems = array_map('trim', explode(',', $prefRaw));
        foreach ($prefItems as $prefItem) {
            if ($prefItem === '') {
                continue;
            }
            if (!in_array($prefItem, $menusResumen[$menuNombre]['niveles_prefs'][$nivel], true)) {
                $menusResumen[$menuNombre]['niveles_prefs'][$nivel][] = $prefItem;
            }
        }
    }
}

$menusResumenList = array_values($menusResumen);
