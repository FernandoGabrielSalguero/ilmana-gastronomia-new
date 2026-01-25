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

require_once __DIR__ . '/../models/admin_viandasColegioModel.php';

$model = new AdminViandasColegioModel($pdo);
$errores = [];
$mensaje = null;

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1');
$action = $_POST['action'] ?? $_GET['action'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$action) {
    $action = 'crear';
}

$respondJson = function (array $payload) {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
};

if ($action === 'list' && $isAjax) {
    $menuItems = $model->obtenerMenuActual();
    $respondJson([
        'ok' => true,
        'items' => $menuItems
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');
    $fechaEntrega = $_POST['fecha_entrega'] ?? '';
    $fechaHoraCompra = $_POST['fecha_hora_compra'] ?? null;
    $fechaHoraCancelacion = $_POST['fecha_hora_cancelacion'] ?? null;
    $precioInput = $_POST['precio'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $niveles = $_POST['nivel_educativo'] ?? [];

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif ((function_exists('mb_strlen') ? mb_strlen($nombre) : strlen($nombre)) > 100) {
        $errores[] = 'El nombre no puede superar los 100 caracteres.';
    }

    if ($fechaEntrega === '') {
        $errores[] = 'La fecha de entrega es obligatoria.';
    }

    $estadosValidos = ['En venta', 'Sin stock'];
    if ($estado === '' || !in_array($estado, $estadosValidos, true)) {
        $errores[] = 'Selecciona un estado valido.';
    }

    $nivelesValidos = ['Inicial', 'Primaria', 'Secundaria', 'Sin Curso Asignado'];
    if (!is_array($niveles)) {
        $niveles = [$niveles];
    }
    $niveles = array_values(array_filter($niveles, function ($nivel) use ($nivelesValidos) {
        return in_array($nivel, $nivelesValidos, true);
    }));

    $precio = null;
    if ($precioInput !== '') {
        if (!is_numeric($precioInput)) {
            $errores[] = 'El precio debe ser numerico.';
        } else {
            $precio = number_format((float)$precioInput, 2, '.', '');
        }
    }

    $data = [
        'nombre' => $nombre,
        'fecha_entrega' => $fechaEntrega ?: null,
        'fecha_hora_compra' => $fechaHoraCompra ?: null,
        'fecha_hora_cancelacion' => $fechaHoraCancelacion ?: null,
        'precio' => $precio,
        'estado' => $estado
    ];

    if (empty($errores)) {
        $resultado = $model->crearMenu($data, $niveles);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'actualizar') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $fechaEntrega = $_POST['fecha_entrega'] ?? '';
    $fechaHoraCompra = $_POST['fecha_hora_compra'] ?? null;
    $fechaHoraCancelacion = $_POST['fecha_hora_cancelacion'] ?? null;
    $precioInput = $_POST['precio'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $nivel = $_POST['nivel_educativo'] ?? '';

    if ($id <= 0) {
        $errores[] = 'Id invalido.';
    }

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif ((function_exists('mb_strlen') ? mb_strlen($nombre) : strlen($nombre)) > 100) {
        $errores[] = 'El nombre no puede superar los 100 caracteres.';
    }

    if ($fechaEntrega === '') {
        $errores[] = 'La fecha de entrega es obligatoria.';
    }

    $estadosValidos = ['En venta', 'Sin stock'];
    if ($estado === '' || !in_array($estado, $estadosValidos, true)) {
        $errores[] = 'Selecciona un estado valido.';
    }

    $nivelesValidos = ['Inicial', 'Primaria', 'Secundaria', 'Sin Curso Asignado'];
    if (!in_array($nivel, $nivelesValidos, true)) {
        $errores[] = 'Selecciona un nivel educativo valido.';
    }

    $precio = null;
    if ($precioInput !== '') {
        if (!is_numeric($precioInput)) {
            $errores[] = 'El precio debe ser numerico.';
        } else {
            $precio = number_format((float)$precioInput, 2, '.', '');
        }
    }

    $data = [
        'nombre' => $nombre,
        'fecha_entrega' => $fechaEntrega ?: null,
        'fecha_hora_compra' => $fechaHoraCompra ?: null,
        'fecha_hora_cancelacion' => $fechaHoraCancelacion ?: null,
        'precio' => $precio,
        'estado' => $estado
    ];

    if (empty($errores)) {
        $resultado = $model->actualizarMenu($id, $data, $nivel);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'eliminar') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        $errores[] = 'Id invalido.';
    }

    if (empty($errores)) {
        $resultado = $model->eliminarMenu($id);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

$menuItems = $model->obtenerMenuActual();
