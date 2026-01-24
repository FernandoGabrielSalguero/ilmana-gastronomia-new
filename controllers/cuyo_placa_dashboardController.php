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

$plantasDisponibles = [
    'Aglomerado',
    'Impregnacion',
    'Mebles',
    'Revestimiento',
    'Transporte',
];

$fechaDesde = $_GET['fecha_desde'] ?? '';
$fechaHasta = $_GET['fecha_hasta'] ?? '';
$plantasSeleccionadas = $_GET['planta'] ?? [];

if (!is_array($plantasSeleccionadas)) {
    $plantasSeleccionadas = [];
}

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

$usarTodasLasPlantas = empty($plantasSeleccionadas) || in_array('todos', $plantasSeleccionadas, true);
$plantasFiltro = $usarTodasLasPlantas ? [] : array_values(array_intersect($plantasSeleccionadas, $plantasDisponibles));

$model = new CuyoPlacaDashboardModel($pdo);
$resumenMenus = $model->obtenerResumenMenus($fechaDesde, $fechaHasta, $plantasFiltro);

$menuOrden = [
    'Refrigerio sandwich almuerzo',
    'Almuerzo Caliente',
    'Desayuno día siguiente',
    'Media tarde',
    'Refrigerio sandwich cena',
    'Cena caliente',
    'Desayuno noche',
    'Sandwich noche',
];

$menuGrupos = [
    'Manana' => [
        'label' => 'Mañana',
        'menus' => [
            'Refrigerio sandwich almuerzo',
            'Almuerzo Caliente',
            'Desayuno día siguiente',
        ],
    ],
    'Tarde' => [
        'label' => 'Tarde',
        'menus' => [
            'Media tarde',
            'Refrigerio sandwich cena',
            'Cena caliente',
        ],
    ],
    'Noche' => [
        'label' => 'Noche',
        'menus' => [
            'Desayuno noche',
            'Sandwich noche',
        ],
    ],
];

$resumenPlantas = [];
$totalPedidos = 0;
foreach ($plantasDisponibles as $planta) {
    $resumenPlantas[$planta] = [
        'menus' => array_fill_keys($menuOrden, 0),
        'total' => 0,
    ];
}

$totalMenus = array_fill_keys($menuOrden, 0);

foreach ($resumenMenus as $fila) {
    $planta = $fila['planta'] ?? '';
    $menu = $fila['menu'] ?? 'Sin menu';
    $cantidad = (int) ($fila['total'] ?? 0);

    if (!isset($resumenPlantas[$planta])) {
        $resumenPlantas[$planta] = [
            'menus' => [],
            'total' => 0,
        ];
    }

    if (!isset($resumenPlantas[$planta]['menus'][$menu])) {
        $resumenPlantas[$planta]['menus'][$menu] = 0;
        $menuOrden[] = $menu;
        $totalMenus[$menu] = 0;
    }

    $resumenPlantas[$planta]['menus'][$menu] += $cantidad;
    $resumenPlantas[$planta]['total'] += $cantidad;
    $totalMenus[$menu] += $cantidad;
    $totalPedidos += $cantidad;
}

if ($fechaDesde && $fechaHasta) {
    $rangoTexto = "Desde {$fechaDesde} hasta {$fechaHasta}";
} elseif ($fechaDesde) {
    $rangoTexto = "Desde {$fechaDesde}";
} elseif ($fechaHasta) {
    $rangoTexto = "Hasta {$fechaHasta}";
} else {
    $rangoTexto = "Todo el historial";
}

$textoPlantas = $usarTodasLasPlantas ? 'Todas' : implode(', ', $plantasFiltro);
$tooltipFiltros = "Planta: {$textoPlantas}\nFecha desde: " . ($fechaDesde ?: 'Sin fecha') . "\nFecha hasta: " . ($fechaHasta ?: 'Sin fecha');

$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
$correo = $_SESSION['correo'] ?? 'Sin correo';
$usuario = $_SESSION['usuario'] ?? 'Sin usuario';
$telefono = $_SESSION['telefono'] ?? 'Sin telefono';
