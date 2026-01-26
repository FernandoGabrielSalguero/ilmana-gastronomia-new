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

$usuarioId = filter_input(INPUT_GET, 'usuario', FILTER_VALIDATE_INT);
$usuarioId = $usuarioId ?: null;

$usuariosFiltro = $model->obtenerUsuariosConCorreosLog();
$logs = $model->obtenerCorreosLog($usuarioId);
