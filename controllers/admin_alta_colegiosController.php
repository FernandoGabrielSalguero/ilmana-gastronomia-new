<?php
// Mostrar errores en pantalla (util en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Proteccion de acceso general
if (!isset($_SESSION['usuario']) && !isset($_SESSION['usuario_id'])) {
    die("Acceso denegado. No has iniciado sesion.");
}

// Proteccion por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    die("Acceso restringido: esta pagina es solo para usuarios Administrador.");
}

require_once __DIR__ . '/../models/admin_alta_colegiosModel.php';

$model = new AdminAltaColegiosModel($pdo);
$errores = [];
$mensaje = null;

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1');
$action = $_POST['action'] ?? $_GET['action'] ?? null;

$respondJson = function (array $payload) {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
};

if ($action === 'list' && $isAjax) {
    $items = $model->obtenerColegios();
    $respondJson([
        'ok' => true,
        'items' => $items
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }

    if (empty($errores)) {
        $resultado = $model->crearColegio([
            'nombre' => $nombre
        ]);

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

$colegios = $model->obtenerColegios();
