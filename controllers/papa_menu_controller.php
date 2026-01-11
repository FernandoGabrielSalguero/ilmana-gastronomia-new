<?php
require_once __DIR__ . '/../models/papa_menu_model.php';

$model = new PapaMenuModel($pdo);
$usuarioId = $_SESSION['usuario_id'] ?? null;

$hijoSeleccionadoId = isset($_GET['hijo_id']) ? (int) $_GET['hijo_id'] : null;

$hijos = $usuarioId ? $model->obtenerHijosPorUsuario($usuarioId) : [];
$hijoSeleccionado = null;
$menus = [];

if ($usuarioId && $hijoSeleccionadoId) {
    $hijoSeleccionado = $model->obtenerDetalleHijoPorUsuario($usuarioId, $hijoSeleccionadoId);
}

$niveles = [];
foreach ($hijos as $hijo) {
    if (!empty($hijo['Nivel_Educativo'])) {
        $niveles[] = $hijo['Nivel_Educativo'];
    }
}
$niveles = array_values(array_unique($niveles));
if ($niveles) {
    $menus = $model->obtenerMenusPorNivelesEducativos($niveles);
}
