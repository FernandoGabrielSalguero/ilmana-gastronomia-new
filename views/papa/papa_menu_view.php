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

    .vianda-resumen {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
        align-items: center;
        justify-content: space-between;
    }

    .vianda-actions {
        margin-top: 12px;
        display: flex;
        justify-content: flex-end;
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
        <form id="vianda-form" data-saldo-actual="<?= htmlspecialchars((string) $saldoActual) ?>">
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
                                                <option value="<?= (int) $menu['Id'] ?>" data-precio="<?= htmlspecialchars((string) ($menu['Precio'] ?? 0)) ?>">
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

            <div class="vianda-resumen">
                <div>
                    <strong>Saldo disponible:</strong>
                    $<span id="vianda-saldo-actual"><?= number_format((float) $saldoActual, 2, ',', '.') ?></span>
                </div>
                <div>
                    <strong>Total pedido:</strong>
                    $<span id="vianda-total">0,00</span>
                </div>
                <div>
                    <strong>Saldo restante:</strong>
                    $<span id="vianda-saldo-restante"><?= number_format((float) $saldoActual, 2, ',', '.') ?></span>
                </div>
            </div>

            <div class="vianda-actions">
                <button class="btn btn-aceptar" type="submit" id="vianda-submit">Guardar Pedido</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
    (function () {
        const form = document.getElementById('vianda-form');
        if (!form) return;

        const saldoActual = parseFloat(form.dataset.saldoActual || '0') || 0;
        const totalEl = document.getElementById('vianda-total');
        const saldoRestanteEl = document.getElementById('vianda-saldo-restante');

        const formatearMonto = (valor) => {
            const numero = Number(valor) || 0;
            return new Intl.NumberFormat('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(numero);
        };

        const recalcularTotales = () => {
            let total = 0;
            const selects = form.querySelectorAll('select[name^="menu_por_dia"]');
            selects.forEach((select) => {
                const option = select.options[select.selectedIndex];
                const precio = option ? parseFloat(option.dataset.precio || '0') : 0;
                if (!Number.isNaN(precio)) {
                    total += precio;
                }
            });
            if (totalEl) {
                totalEl.textContent = formatearMonto(total);
            }
            if (saldoRestanteEl) {
                saldoRestanteEl.textContent = formatearMonto(saldoActual - total);
            }
        };

        const showAlert = (type, message) => {
            if (typeof window.showAlertSafe === 'function') {
                window.showAlertSafe(type, message);
                return;
            }
            if (typeof window.showAlert === 'function') {
                try {
                    window.showAlert(type, message);
                    return;
                } catch (err) {
                    console.warn('showAlert failed, falling back to alert.', err);
                }
            }
            alert(message);
        };

        form.addEventListener('change', (event) => {
            if (event.target && event.target.matches('select[name^="menu_por_dia"]')) {
                recalcularTotales();
            }
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch('papa_menu_view.php', {
                method: 'POST',
                body: formData
            })
                .then(async (res) => {
                    if (!res.ok) {
                        const body = await res.text();
                        console.error('Error guardando pedido:', {
                            status: res.status,
                            statusText: res.statusText,
                            body
                        });
                        throw new Error('Error guardando pedido');
                    }
                    return res.json();
                })
                .then((data) => {
                    if (data.ok) {
                        const ids = Array.isArray(data.pedidoIds) ? data.pedidoIds : [];
                        const mensaje = ids.length ? `Pedido guardado. No. ${ids.join(', ')}` : 'Pedido guardado.';
                        showAlert('success', mensaje);
                        if (typeof window.cerrarModalVianda === 'function') {
                            window.cerrarModalVianda();
                        }
                        form.reset();
                        recalcularTotales();
                        return;
                    }
                    const errores = Array.isArray(data.errores) && data.errores.length
                        ? data.errores.join(' | ')
                        : 'No se pudo guardar el pedido.';
                    showAlert('error', errores);
                })
                .catch((err) => {
                    console.error('Error de conexion guardando pedido:', err);
                    showAlert('error', 'Error de conexion. Intenta nuevamente.');
                });
        });

        recalcularTotales();
    })();
</script>
