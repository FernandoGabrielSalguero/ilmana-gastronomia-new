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
        overflow: visible;
    }

    .vianda-table .input-icon {
        width: 100%;
    }

    .vianda-table .input-icon select {
        width: 100%;
    }

    .vianda-table td {
        overflow: visible;
        position: relative;
    }

    .vianda-table tr {
        overflow: visible;
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

    .vianda-actions .btn-disabled {
        background-color: #cbd5e1;
        color: #64748b;
        cursor: not-allowed;
        opacity: 0.9;
    }

    .vianda-warning {
        color: #dc2626;
        font-weight: 600;
        margin-top: 8px;
        display: none;
    }

    .vianda-descuento-leyenda {
        color: #dc2626;
        font-size: 11px;
        margin-top: 4px;
        font-weight: 600;
        display: none;
        overflow: visible;
    }

    .vianda-descuento-leyenda.ok {
        color: #16a34a;
    }

    .vianda-descuento-leyenda .leyenda-icon {
        font-size: 16px;
        vertical-align: middle;
        margin-left: 6px;
        cursor: pointer;
        color: #2563eb;
    }

    .vianda-descuento-leyenda .leyenda-text {
        display: inline-block;
        max-width: 170px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }

    .vianda-descuento-leyenda .vianda-terminos {
        display: none;
        margin-top: 6px;
        font-size: 12px;
        color: #0f172a;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: 8px 10px;
        line-height: 1.35;
        max-width: 100%;
        width: 100%;
        box-sizing: border-box;
        white-space: normal;
        overflow-wrap: break-word;
    }

    .vianda-descuento-leyenda .vianda-terminos.is-open {
        display: block;
    }

    .vianda-row-expanded td {
        vertical-align: top;
    }

    .vianda-dropdown {
        position: relative;
        z-index: 20;
    }

    .vianda-dropdown summary {
        list-style: none;
        cursor: pointer;
        padding: 6px 10px;
        border-radius: 8px;
        border: 1px solid #cbd5f5;
        background: #f8fafc;
        color: #0f172a;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .vianda-dropdown summary::-webkit-details-marker {
        display: none;
    }

    .vianda-dropdown summary::after {
        content: '▾';
        font-size: 12px;
        color: #475569;
    }

    .vianda-dropdown[open] summary::after {
        content: '▴';
    }

    .vianda-dropdown .dropdown-panel {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        min-width: 220px;
        max-width: 320px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        z-index: 50;
    }

    .vianda-dropdown[open] {
        z-index: 9999;
    }

    .vianda-dropdown[open] .dropdown-panel {
        z-index: 10000;
    }

    .vianda-option {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12px;
        color: #0f172a;
    }

    .vianda-option:hover {
        background: #f1f5f9;
    }

    .vianda-option input {
        margin-top: 2px;
    }

    .vianda-option .chip-price {
        color: #475569;
        font-weight: 600;
    }

    .vianda-descuento-leyenda .leyenda-tooltip-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
    }

    .vianda-descuento-leyenda .leyenda-tooltip {
        position: absolute;
        left: 50%;
        top: 26px;
        transform: translateX(-50%);
        min-width: 180px;
        max-width: 260px;
        background: #0f172a;
        color: #f8fafc;
        padding: 8px 10px;
        border-radius: 10px;
        font-size: 12px;
        line-height: 1.3;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.25);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.15s ease, transform 0.15s ease;
        transform-origin: top center;
        z-index: 10;
        pointer-events: none;
    }

    .vianda-descuento-leyenda .leyenda-tooltip::after {
        content: '';
        position: absolute;
        top: -6px;
        left: 50%;
        transform: translateX(-50%);
        border-width: 0 6px 6px 6px;
        border-style: solid;
        border-color: transparent transparent #0f172a transparent;
    }

    .vianda-descuento-leyenda .leyenda-tooltip-wrap.tooltip-open .leyenda-tooltip {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(2px);
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
                        $diasDisponibles = 0;
                        foreach ($fechasOrdenadas as $fechaKey => $fechaLabel) {
                            if (!empty($menusPorFecha[$fechaKey])) {
                                $diasDisponibles++;
                            }
                        }
                        $colegioId = isset($hijo['Colegio_Id']) ? (int)$hijo['Colegio_Id'] : 0;
                        $promo = ($colegioId > 0 && !empty($descuentosPorColegioNivel[$colegioId][$nivel]))
                            ? $descuentosPorColegioNivel[$colegioId][$nivel]
                            : null;
                        $promoPorcentaje = $promo['Porcentaje'] ?? '';
                        $promoMin = $promo['Viandas_Por_Dia_Min'] ?? '';
                        $promoDias = $promo['Dias_Obligatorios'] ?? '';
                        $promoTerminos = $promo['Terminos'] ?? '';
                        $promoHasta = $promo['Vigencia_Hasta'] ?? '';
                        $promoRestante = '';
                        if ($promoHasta) {
                            try {
                                $ahora = new DateTime('now');
                                $hasta = new DateTime($promoHasta);
                                if ($hasta > $ahora) {
                                    $diff = $ahora->diff($hasta);
                                    $dias = (int)$diff->days;
                                    $horas = (int)$diff->h;
                                    $minutos = (int)$diff->i;
                                    if ($dias > 0) {
                                        $promoRestante = $dias . ' dia' . ($dias === 1 ? '' : 's');
                                        if ($horas > 0) {
                                            $promoRestante .= ' y ' . $horas . ' hs';
                                        }
                                    } elseif ($horas > 0) {
                                        $promoRestante = $horas . ' hs';
                                        if ($minutos > 0) {
                                            $promoRestante .= ' y ' . $minutos . ' min';
                                        }
                                    } else {
                                        $promoRestante = max(1, $minutos) . ' min';
                                    }
                                }
                            } catch (Exception $e) {
                                $promoRestante = '';
                            }
                        }
                        ?>
                        <tr class="<?= $esSeleccionado ? 'vianda-selected-row' : '' ?>"
                            data-required-days="<?= (int) $diasDisponibles ?>"
                            data-promo-percent="<?= htmlspecialchars((string) $promoPorcentaje) ?>"
                            data-promo-min="<?= htmlspecialchars((string) $promoMin) ?>"
                            data-promo-days="<?= htmlspecialchars((string) $promoDias) ?>"
                            data-promo-terminos="<?= htmlspecialchars((string) $promoTerminos) ?>"
                            data-promo-hasta="<?= htmlspecialchars((string) $promoHasta) ?>"
                            data-promo-restante="<?= htmlspecialchars((string) $promoRestante) ?>">
                            <td>
                                <div><?= htmlspecialchars($hijo['Nombre']) ?></div>
                                <div class="vianda-descuento-leyenda" data-vianda-leyenda>
                                    <span class="leyenda-text"></span>
                                    <span class="material-icons leyenda-icon" title="" aria-label="Ver terminos">help_outline</span>
                                    <div class="vianda-terminos" data-vianda-terminos></div>
                                </div>
                            </td>
                            <?php foreach ($fechasOrdenadas as $fechaKey => $fechaLabel): ?>
                                <?php $listaMenus = $menusPorFecha[$fechaKey] ?? []; ?>
                                <td>
                                    <?php if (empty($listaMenus)): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <details class="vianda-dropdown">
                                            <summary>Seleccionar <span class="vianda-selected-count" data-selected-count>0</span></summary>
                                            <div class="dropdown-panel">
                                                <?php foreach ($listaMenus as $menu): ?>
                                                    <?php
                                                    $precio = $menu['Precio'] !== null
                                                        ? '$' . number_format((float)$menu['Precio'], 2, ',', '.')
                                                        : 'Sin precio';
                                                    $label = $menu['Nombre'] ?: 'Menu';
                                                    ?>
                                                    <label class="vianda-option">
                                                        <input type="checkbox"
                                                            name="menu_por_dia[<?= (int) $hijo['Id'] ?>][<?= htmlspecialchars($fechaKey) ?>][]"
                                                            value="<?= (int) $menu['Id'] ?>"
                                                            data-precio="<?= htmlspecialchars((string) ($menu['Precio'] ?? 0)) ?>"
                                                            data-fecha="<?= htmlspecialchars($fechaKey) ?>">
                                                        <span><?= htmlspecialchars($label) ?></span>
                                                        <span class="chip-price">(<?= htmlspecialchars($precio) ?>)</span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
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
                    <strong>Descuento 10%:</strong>
                    $<span id="vianda-descuento">0,00</span>
                </div>
                <div>
                    <strong>Total final:</strong>
                    $<span id="vianda-total-final">0,00</span>
                </div>
                <div>
                    <strong>Saldo restante:</strong>
                    $<span id="vianda-saldo-restante"><?= number_format((float) $saldoActual, 2, ',', '.') ?></span>
                </div>
            </div>

            <div class="vianda-actions">
                <button class="btn btn-aceptar" type="submit" id="vianda-submit">Guardar Pedido</button>
            </div>
            <div id="vianda-saldo-warning" class="vianda-warning"></div>
        </form>
    <?php endif; ?>
</div>
