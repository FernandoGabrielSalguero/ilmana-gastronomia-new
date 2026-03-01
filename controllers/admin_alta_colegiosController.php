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

if ($action === 'list_colegios' && $isAjax) {
    $items = $model->obtenerColegiosConRepresentante();
    $respondJson([
        'ok' => true,
        'items' => $items
    ]);
}

if ($action === 'list_cursos' && $isAjax) {
    $items = $model->obtenerCursos();
    $respondJson([
        'ok' => true,
        'items' => $items
    ]);
}

if ($action === 'list_representantes' && $isAjax) {
    $items = $model->obtenerRepresentantes();
    $respondJson([
        'ok' => true,
        'items' => $items
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear_colegio') {
    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }

    if (empty($errores)) {
        $resultado = $model->crearColegio([
            'nombre' => $nombre,
            'direccion' => $direccion !== '' ? $direccion : null
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear_curso') {
    $nombre = trim($_POST['nombre'] ?? '');
    $colegioIdInput = $_POST['colegio_id'] ?? '';
    $nivel = $_POST['nivel_educativo'] ?? '';

    if ($nombre === '') {
        $errores[] = 'El nombre del curso es obligatorio.';
    }

    $colegioId = null;
    if ($colegioIdInput === '' || !is_numeric($colegioIdInput)) {
        $errores[] = 'Selecciona un colegio valido.';
    } else {
        $colegioId = (int) $colegioIdInput;
        if ($colegioId <= 0) {
            $errores[] = 'Selecciona un colegio valido.';
        }
    }

    $nivelesValidos = ['Inicial', 'Primaria', 'Secundaria', 'Sin Curso Asignado'];
    if ($nivel === '' || !in_array($nivel, $nivelesValidos, true)) {
        $errores[] = 'Selecciona un nivel educativo valido.';
    }

    if (empty($errores)) {
        $resultado = $model->crearCurso([
            'nombre' => $nombre,
            'colegio_id' => $colegioId,
            'nivel_educativo' => $nivel
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'asignar_representante') {
    $colegioIdInput = $_POST['colegio_id'] ?? '';
    $representanteIdInput = $_POST['representante_id'] ?? '';

    $colegioId = null;
    if ($colegioIdInput === '' || !is_numeric($colegioIdInput)) {
        $errores[] = 'Selecciona un colegio valido.';
    } else {
        $colegioId = (int) $colegioIdInput;
        if ($colegioId <= 0) {
            $errores[] = 'Selecciona un colegio valido.';
        }
    }

    $representanteId = null;
    if ($representanteIdInput === '' || !is_numeric($representanteIdInput)) {
        $errores[] = 'Selecciona un representante valido.';
    } else {
        $representanteId = (int) $representanteIdInput;
        if ($representanteId <= 0) {
            $errores[] = 'Selecciona un representante valido.';
        }
    }

    if (empty($errores)) {
        $resultado = $model->asignarRepresentante($colegioId, $representanteId);
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

$colegios = $model->obtenerColegiosConRepresentante();
$cursos = $model->obtenerCursos();
$colegiosSelect = $model->obtenerColegios();
$representantes = $model->obtenerRepresentantes();
