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

require_once __DIR__ . '/../models/cuyo_placa_pedidosModel.php';

$usuarioId = $_SESSION['usuario_id'] ?? null;
if (!$usuarioId) {
    die("Acceso denegado. Usuario invalido.");
}

$plantas = [
    ['key' => 'Aglomerado', 'label' => 'Aglomerado'],
    ['key' => 'Revestimiento', 'label' => 'Revestimiento'],
    ['key' => 'Impregnacion', 'label' => 'Impregnacion'],
    ['key' => 'Muebles', 'label' => 'Muebles'],
    ['key' => 'Transporte', 'label' => 'Transporte (Revestimiento)'],
];

$menuGrupos = [
    [
        'key' => 'Manana',
        'label' => 'Mañana',
        'menus' => [
            ['key' => 'desayuno_dia_siguiente', 'label' => 'Desayuno día siguiente', 'db' => 'Desayuno día siguiente'],
            ['key' => 'almuerzo_caliente', 'label' => 'Almuerzo Caliente', 'db' => 'Almuerzo Caliente'],
            ['key' => 'refrigerio_sandwich_almuerzo', 'label' => 'Refrigerio sandwich almuerzo', 'db' => 'Refrigerio sandwich almuerzo'],
        ],
    ],
    [
        'key' => 'Tarde',
        'label' => 'Tarde',
        'menus' => [
            ['key' => 'media_tarde', 'label' => 'Media tarde', 'db' => 'Media tarde'],
            ['key' => 'cena_caliente', 'label' => 'Cena caliente', 'db' => 'Cena caliente'],
            ['key' => 'refrigerio_sandwich_cena', 'label' => 'Refrigerio sandwich cena', 'db' => 'Refrigerio sandwich cena'],
        ],
    ],
    [
        'key' => 'Noche',
        'label' => 'Noche',
        'menus' => [
            ['key' => 'desayuno_noche', 'label' => 'Desayuno noche', 'db' => 'Desayuno noche'],
            ['key' => 'sandwich_noche', 'label' => 'Sandwich noche', 'db' => 'Sandwich noche'],
        ],
    ],
];

$menuPorClave = [];
$menuPorNombreDb = [];
foreach ($menuGrupos as $grupo) {
    foreach ($grupo['menus'] as $menu) {
        $menuPorClave[$menu['key']] = [
            'label' => $menu['label'],
            'db' => $menu['db'],
            'turno' => $grupo['key'],
        ];
        $menuPorNombreDb[$menu['db']] = $menu['key'];
    }
}

$model = new CuyoPlacaPedidosModel($pdo);
$alerta = null;

$fechaRaw = $_GET['fecha'] ?? $_POST['fecha'] ?? null;
$fechaSeleccionada = $fechaRaw !== null ? trim((string) $fechaRaw) : date('Y-m-d');
if ($fechaSeleccionada && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSeleccionada)) {
    $fechaSeleccionada = date('Y-m-d');
    $alerta = [
        'tipo' => 'error',
        'mensaje' => 'Selecciona una fecha valida para cargar los pedidos.',
    ];
}

$pedidoExistente = null;
$detallePedido = [];
if ($fechaSeleccionada) {
    $pedidoExistente = $model->obtenerPedidoPorFecha($fechaSeleccionada);
    if ($pedidoExistente) {
        $detallePedido = $model->obtenerDetallePedido((int) $pedidoExistente['id']);
    }
}

$fechaBase = $fechaSeleccionada ?: date('Y-m-d');
$inicioSemana = new DateTime($fechaBase);
$inicioSemana->modify('monday this week');
$finSemana = clone $inicioSemana;
$finSemana->modify('+6 days');

$pedidosSemana = $model->obtenerPedidosPorRango($inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d'));
$pedidosPorFecha = [];
foreach ($pedidosSemana as $pedido) {
    $fechaPedido = $pedido['fecha'] ?? null;
    if ($fechaPedido) {
        $pedidosPorFecha[$fechaPedido] = $pedido;
    }
}

$diasSemana = [
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miercoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sabado',
    'Sunday' => 'Domingo',
];

$semanaDias = [];
$cursor = clone $inicioSemana;
$ahora = new DateTime('now');
$limiteHoy = new DateTime($ahora->format('Y-m-d') . ' 10:00:00');
for ($i = 0; $i < 7; $i++) {
    $fechaDia = $cursor->format('Y-m-d');
    $pedidoDia = $pedidosPorFecha[$fechaDia] ?? null;
    $esHoy = $fechaDia === $ahora->format('Y-m-d');
    $puedeModificar = $pedidoDia && $esHoy && $ahora < $limiteHoy;
    $semanaDias[] = [
        'fecha' => $fechaDia,
        'label' => $diasSemana[$cursor->format('l')] ?? $cursor->format('l'),
        'pedido' => $pedidoDia,
        'puedeModificar' => $puedeModificar,
        'seleccionada' => $fechaDia === $fechaSeleccionada,
    ];
    $cursor->modify('+1 day');
}

$bloqueoEdicion = false;
$bloqueoPorHora = false;
if ($fechaSeleccionada) {
    $limite = new DateTime($fechaSeleccionada . ' 10:00:00');
    if ($fechaSeleccionada === $ahora->format('Y-m-d') && $ahora >= $limite) {
        $bloqueoPorHora = true;
    }
}
$bloqueoPorFecha = $pedidoExistente ? true : false;
$bloqueoEdicion = $bloqueoPorHora || $bloqueoPorFecha;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$fechaSeleccionada) {
        if (!$alerta) {
            $alerta = [
                'tipo' => 'error',
                'mensaje' => 'Selecciona una fecha valida para guardar el pedido.',
            ];
        }
    } elseif ($bloqueoPorHora) {
        $alerta = [
            'tipo' => 'error',
            'mensaje' => 'No se pueden cargar pedidos para hoy despues de las 10:00 (hora Argentina).',
        ];
    } elseif ($bloqueoPorFecha) {
        $alerta = [
            'tipo' => 'error',
            'mensaje' => 'Ya existe un pedido para esa fecha. No se pueden cargar nuevos pedidos.',
        ];
    } else {
        $detalles = [];
        $datosPedido = $_POST['pedido'] ?? [];
        foreach ($plantas as $planta) {
            $plantaKey = $planta['key'];
            $menusPlanta = $datosPedido[$plantaKey] ?? [];
            foreach ($menuPorClave as $menuKey => $menuInfo) {
                $cantidad = $menusPlanta[$menuKey] ?? 0;
                $cantidad = is_numeric($cantidad) ? (int) $cantidad : 0;
                if ($cantidad > 0) {
                    $detalles[] = [
                        'planta' => $plantaKey,
                        'turno' => $menuInfo['turno'],
                        'menu' => $menuInfo['db'],
                        'cantidad' => $cantidad,
                    ];
                }
            }
        }

        if (empty($detalles)) {
            $alerta = [
                'tipo' => 'error',
                'mensaje' => 'Debes cargar al menos una cantidad mayor a 0.',
            ];
        } else {
            $ok = $model->crearPedido($usuarioId, $fechaSeleccionada, $detalles);

            $alerta = [
                'tipo' => $ok ? 'success' : 'error',
                'mensaje' => $ok
                    ? 'Pedido cargado correctamente.'
                    : 'Ocurrio un error al guardar el pedido.',
            ];
        }
    }
}

if ($fechaSeleccionada) {
    $pedidoExistente = $model->obtenerPedidoPorFecha($fechaSeleccionada);
    $detallePedido = $pedidoExistente ? $model->obtenerDetallePedido((int) $pedidoExistente['id']) : [];
}
$bloqueoPorFecha = $pedidoExistente ? true : false;
$bloqueoEdicion = $bloqueoPorHora || $bloqueoPorFecha;

if (!$alerta && $pedidoExistente && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $alerta = [
        'tipo' => 'warning',
        'mensaje' => 'Ya existe un pedido para esta fecha. No se pueden cargar nuevos pedidos.',
    ];
} elseif (!$alerta && $bloqueoPorHora && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $alerta = [
        'tipo' => 'warning',
        'mensaje' => 'Las cargas para hoy se cierran a las 10:00 (hora Argentina).',
    ];
}

$semanaAnterior = clone $inicioSemana;
$semanaAnterior->modify('-7 days');
$semanaSiguiente = clone $inicioSemana;
$semanaSiguiente->modify('+7 days');

$detalleMap = [];
foreach ($plantas as $planta) {
    $detalleMap[$planta['key']] = array_fill_keys(array_keys($menuPorClave), 0);
}

foreach ($detallePedido as $fila) {
    $plantaKey = $fila['planta'] ?? '';
    $menuDb = $fila['menu'] ?? '';
    $cantidad = (int) ($fila['cantidad'] ?? 0);

    if (!isset($detalleMap[$plantaKey])) {
        continue;
    }

    $menuKey = $menuPorNombreDb[$menuDb] ?? null;
    if (!$menuKey || !isset($detalleMap[$plantaKey][$menuKey])) {
        continue;
    }

    $detalleMap[$plantaKey][$menuKey] = $cantidad;
}

$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
