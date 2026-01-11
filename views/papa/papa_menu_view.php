<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controllers/papa_menu_controller.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'papas') {
    echo '<p>Acceso restringido.</p>';
    return;
}

$hijoSeleccionadoId = $hijoSeleccionado['Id'] ?? null;

$menusPorNivel = [];
$fechasMap = [];
foreach ($menus as $menu) {
    $nivel = $menu['Nivel_Educativo'] ?? 'Sin Curso Asignado';
    $fechaKey = $menu['Fecha_entrega'] ?: 'sin_fecha';
    if (!isset($fechasMap[$fechaKey])) {
        $fechasMap[$fechaKey] = $menu['Fecha_entrega']
            ? (new DateTime($menu['Fecha_entrega']))->format('d/m/Y')
            : 'Sin fecha';
    }
    $menusPorNivel[$nivel][$fechaKey][] = $menu;
}

$fechasOrdenadas = [];
$fechasConFecha = array_filter(array_keys($fechasMap), function ($fechaKey) {
    return $fechaKey !== 'sin_fecha';
});
sort($fechasConFecha);
foreach ($fechasConFecha as $fechaKey) {
    $fechasOrdenadas[$fechaKey] = $fechasMap[$fechaKey];
}
if (isset($fechasMap['sin_fecha'])) {
    $fechasOrdenadas['sin_fecha'] = $fechasMap['sin_fecha'];
}
?>

<style>
    .vianda-table {
        width: 100%;
    }

    .vianda-table select {
        width: 100%;
    }

    .vianda-selected-row {
        background-color: #eef2ff;
    }
</style>

<div>
    <h4>Menu por dia y alumno</h4>
    <?php if (empty($hijos)): ?>
        <div class="card">
            <p>No hay hijos asociados.</p>
        </div>
    <?php elseif (empty($fechasOrdenadas)): ?>
        <div class="card">
            <p>No hay menu disponible para los niveles educativos asociados.</p>
        </div>
    <?php else: ?>
        <form id="vianda-form">
            <table class="data-table vianda-table">
                <thead>
                    <tr>
                        <th>Alumno</th>
                        <?php foreach ($fechasOrdenadas as $fechaLabel): ?>
                            <th><?= htmlspecialchars($fechaLabel) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hijos as $hijo): ?>
                        <?php
                        $nivel = $hijo['Nivel_Educativo'] ?? 'Sin Curso Asignado';
                        $menusPorFecha = $menusPorNivel[$nivel] ?? [];
                        $esSeleccionado = $hijoSeleccionadoId && (int)$hijo['Id'] === (int)$hijoSeleccionadoId;
                        ?>
                        <tr class="<?= $esSeleccionado ? 'vianda-selected-row' : '' ?>">
                            <td><?= htmlspecialchars($hijo['Nombre']) ?></td>
                            <?php foreach ($fechasOrdenadas as $fechaKey => $fechaLabel): ?>
                                <?php $listaMenus = $menusPorFecha[$fechaKey] ?? []; ?>
                                <td>
                                    <?php if (empty($listaMenus)): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <select name="menu_por_dia[<?= (int) $hijo['Id'] ?>][<?= htmlspecialchars($fechaKey) ?>]">
                                            <option value="">Seleccionar menu</option>
                                            <?php foreach ($listaMenus as $menu): ?>
                                                <?php
                                                $precio = $menu['Precio'] !== null
                                                    ? '$' . number_format((float)$menu['Precio'], 2, ',', '.')
                                                    : 'Sin precio';
                                                $label = $menu['Nombre']
                                                    ? $menu['Nombre'] . ' (' . $precio . ')'
                                                    : $precio;
                                                ?>
                                                <option value="<?= (int) $menu['Id'] ?>">
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php endif; ?>
</div>
