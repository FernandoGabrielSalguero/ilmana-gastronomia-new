<?php
require_once __DIR__ . '/../models/papa_menu_model.php';

$model = new PapaMenuModel($pdo);
$usuarioId = $_SESSION['usuario_id'] ?? null;

$hijoSeleccionadoId = isset($_GET['hijo_id']) ? (int) $_GET['hijo_id'] : null;

$hijos = $usuarioId ? $model->obtenerHijosPorUsuario($usuarioId) : [];
$hijoSeleccionado = null;
$menus = [];
$nivelEducativo = null;

if ($usuarioId && $hijoSeleccionadoId) {
    $hijoSeleccionado = $model->obtenerDetalleHijoPorUsuario($usuarioId, $hijoSeleccionadoId);
    if ($hijoSeleccionado) {
        $nivelEducativo = $hijoSeleccionado['Nivel_Educativo'] ?? null;
        if ($nivelEducativo) {
            $menus = $model->obtenerMenusPorNivelEducativo($nivelEducativo);
        }
    }
}
