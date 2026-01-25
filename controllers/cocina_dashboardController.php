<?php
require_once __DIR__ . '/../models/cocina_dashboardModel.php';

$fechaEntrega = $_GET['fecha_entrega'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEntrega)) {
    $fechaEntrega = date('Y-m-d');
}

$model = new CocinaDashboardModel($pdo);
$resumenMenusRaw = $model->obtenerMenusPorCurso($fechaEntrega);
$preferenciasRaw = $model->obtenerPreferenciasPorMenuNivel($fechaEntrega);
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
            if (mb_strtolower($prefItem) === 'sin preferencias') {
                continue;
            }
            if (!in_array($prefItem, $menusResumen[$menuNombre]['niveles_prefs'][$nivel], true)) {
                $menusResumen[$menuNombre]['niveles_prefs'][$nivel][] = $prefItem;
            }
        }
    }
}

$menusResumenList = array_values($menusResumen);

$prefCounts = [];
foreach ($preferenciasRaw as $row) {
    $nivelRaw = trim((string) ($row['Nivel_Educativo'] ?? ''));
    $nivel = in_array($nivelRaw, $nivelesOrden, true) ? $nivelRaw : 'Inicial';

    $menuNombre = trim((string) ($row['Menu_Nombre'] ?? ''));
    if ($menuNombre === '') {
        $menuNombre = 'Menu sin nombre';
    }

    $prefId = $row['Preferencia_Id'] ?? null;
    $prefNombre = trim((string) ($row['Preferencia_Nombre'] ?? ''));
    if ($prefNombre === '') {
        $prefNombre = 'Sin preferencias';
    }

    $cantidad = (int) ($row['Total'] ?? 0);
    if ($cantidad <= 0) {
        continue;
    }

    if (!isset($prefCounts[$menuNombre])) {
        $prefCounts[$menuNombre] = [];
    }
    if (!isset($prefCounts[$menuNombre][$nivel])) {
        $prefCounts[$menuNombre][$nivel] = [
            'sin' => 0,
            'prefs' => [],
            'has_pref' => false
        ];
    }

    $prefIsSin = false;
    if ($prefId !== null && (string) $prefId === '6') {
        $prefIsSin = true;
    }
    if (mb_strtolower($prefNombre) === 'sin preferencias') {
        $prefIsSin = true;
    }

    if ($prefIsSin) {
        $prefCounts[$menuNombre][$nivel]['sin'] += $cantidad;
        continue;
    }

    if (!isset($prefCounts[$menuNombre][$nivel]['prefs'][$prefNombre])) {
        $prefCounts[$menuNombre][$nivel]['prefs'][$prefNombre] = 0;
    }
    $prefCounts[$menuNombre][$nivel]['prefs'][$prefNombre] += $cantidad;
    $prefCounts[$menuNombre][$nivel]['has_pref'] = true;
}

foreach ($menusResumenList as &$menuResumen) {
    $menuNombre = $menuResumen['nombre'];
    $menuResumen['niveles_pref_counts'] = [];
    foreach ($nivelesOrden as $nivel) {
        $menuResumen['niveles_pref_counts'][$nivel] = $prefCounts[$menuNombre][$nivel] ?? [
            'sin' => 0,
            'prefs' => [],
            'has_pref' => false
        ];
    }
}
unset($menuResumen);
