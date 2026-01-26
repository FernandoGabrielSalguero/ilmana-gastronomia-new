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

require_once __DIR__ . '/../models/admin_logsModel.php';

$model = new AdminLogsModel($pdo);

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || ($_POST['ajax'] ?? '') === '1';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($isAjax && $action === 'buscar') {
    $termino = trim((string) ($_POST['termino'] ?? $_GET['termino'] ?? ''));
    $logs = $model->buscarCorreosLog($termino);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok' => true,
        'logs' => $logs
    ]);
    exit;
}

$actionAuditoria = $action === 'buscar_auditoria';
if ($isAjax && $actionAuditoria) {
    $termino = trim((string) ($_POST['termino'] ?? $_GET['termino'] ?? ''));
    $auditoria = $model->buscarAuditoriaEventos($termino);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok' => true,
        'auditoria' => $auditoria
    ]);
    exit;
}

$logs = $model->obtenerCorreosLog();
$auditoria = $model->obtenerAuditoriaEventos();
