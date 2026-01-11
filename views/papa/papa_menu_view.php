<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controllers/papa_menu_controller.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'papas') {
    echo '<p>Acceso restringido.</p>';
    return;
}

$hijoSeleccionadoId = $hijoSeleccionado['Id'] ?? null;
?>

<style>
    .vianda-layout {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }

    .vianda-hijos {
        flex: 1 1 220px;
        max-width: 260px;
    }

    .vianda-hijos-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .vianda-menu {
        flex: 3 1 480px;
        min-width: 320px;
    }

    .vianda-menu select {
        width: 100%;
    }
</style>

<div class="vianda-layout">
    <div class="vianda-hijos">
        <h4>Hijos</h4>
        <?php if (empty($hijos)): ?>
            <p>No hay hijos asociados.</p>
        <?php else: ?>
            <div class="vianda-hijos-list">
                <?php foreach ($hijos as $hijo): ?>
                    <?php $esSeleccionado = $hijoSeleccionadoId && (int)$hijo['Id'] === (int)$hijoSeleccionadoId; ?>
                    <button
                        type="button"
                        class="btn btn-small <?= $esSeleccionado ? 'btn-aceptar' : 'btn-cancelar' ?>"
                        data-hijo-id="<?= (int) $hijo['Id'] ?>"
                        data-hijo-nombre="<?= htmlspecialchars($hijo['Nombre']) ?>">
                        <?= htmlspecialchars($hijo['Nombre']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="vianda-menu">
        <h4>Menu por dia</h4>
        <?php if (!$hijoSeleccionado): ?>
            <div class="card">
                <p>Selecciona un hijo para ver el menu disponible.</p>
            </div>
        <?php else: ?>
            <p class="text-muted">Nivel educativo: <?= htmlspecialchars($nivelEducativo ?? 'Sin curso asignado') ?></p>
            <?php if (empty($menus)): ?>
                <div class="card">
                    <p>No hay menu disponible para el nivel educativo seleccionado.</p>
                </div>
            <?php else: ?>
                <?php
                $menusPorDia = [];
                foreach ($menus as $menu) {
                    $fecha = $menu['Fecha_entrega'] ?: 'Sin fecha';
                    $menusPorDia[$fecha][] = $menu;
                }
                ?>
                <form id="vianda-form">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Menu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menusPorDia as $fecha => $listaMenus): ?>
                                <?php
                                $fechaLabel = $fecha !== 'Sin fecha'
                                    ? (new DateTime($fecha))->format('d/m/Y')
                                    : 'Sin fecha';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($fechaLabel) ?></td>
                                    <td>
                                        <select name="menu_por_dia[<?= htmlspecialchars($fecha) ?>]">
                                            <option value="">Seleccionar menu</option>
                                            <?php foreach ($listaMenus as $menu): ?>
                                                <?php
                                                $precio = $menu['Precio'] !== null
                                                    ? '$' . number_format((float)$menu['Precio'], 2, ',', '.')
                                                    : 'Sin precio';
                                                $label = $menu['Nombre'] ? $menu['Nombre'] . ' (' . $precio . ')' : $precio;
                                                ?>
                                                <option value="<?= (int) $menu['Id'] ?>">
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    (function () {
        const body = document.getElementById('vianda-modal-body');
        if (!body) return;

        body.querySelectorAll('[data-hijo-id]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const hijoId = btn.getAttribute('data-hijo-id');
                const hijoNombre = btn.getAttribute('data-hijo-nombre') || '';
                if (!hijoId) return;

                const title = document.getElementById('vianda-modal-title');
                if (title) {
                    title.textContent = hijoNombre ? `Pedir vianda - ${hijoNombre}` : 'Pedir vianda';
                }

                window.selectedHijoId = parseInt(hijoId, 10) || 0;
                body.innerHTML = '<p>Cargando...</p>';

                const params = new URLSearchParams({ modal: '1', hijo_id: hijoId });
                fetch(`papa_menu_view.php?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(async (res) => {
                        if (!res.ok) {
                            const bodyText = await res.text();
                            console.error('Error cargando menu:', {
                                status: res.status,
                                statusText: res.statusText,
                                body: bodyText
                            });
                            throw new Error('Error cargando menu');
                        }
                        return res.text();
                    })
                    .then((html) => {
                        body.innerHTML = html;
                    })
                    .catch(() => {
                        body.innerHTML = '<p>Error al cargar el menu.</p>';
                    });
            });
        });
    })();
</script>
