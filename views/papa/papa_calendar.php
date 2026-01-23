<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controllers/papa_calendarController.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'papas') {
    echo '<p>Acceso restringido.</p>';
    return;
}

$meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'
];

$primerDiaMes = new DateTime(sprintf('%04d-%02d-01', $anioSeleccionado, $mesSeleccionado));
$diasMes = (int) $primerDiaMes->format('t');
$diaSemanaInicio = (int) $primerDiaMes->format('N');

$semanas = [];
$dia = 1;
for ($fila = 0; $fila < 6; $fila++) {
    $semana = [];
    for ($col = 1; $col <= 7; $col++) {
        if (($fila === 0 && $col < $diaSemanaInicio) || $dia > $diasMes) {
            $semana[] = null;
        } else {
            $semana[] = $dia;
            $dia++;
        }
    }
    $semanas[] = $semana;
    if ($dia > $diasMes) {
        break;
    }
}
?>

<style>
    .calendar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        gap: 8px;
    }

    .calendar-title {
        font-size: 18px;
        font-weight: 700;
    }

    .calendar-grid {
        width: 100%;
        border-collapse: collapse;
    }

    .calendar-grid th,
    .calendar-grid td {
        border: 1px solid #e2e8f0;
        vertical-align: top;
        padding: 8px;
        min-width: 120px;
        height: 120px;
    }

    .calendar-grid th {
        background-color: #f8fafc;
        text-align: center;
        font-weight: 700;
    }

    .calendar-day {
        font-weight: 700;
        margin-bottom: 6px;
        color: #1f2937;
    }

    .calendar-event {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px;
        margin-bottom: 6px;
        font-size: 12px;
    }

    .calendar-event-title {
        font-weight: 600;
        margin-bottom: 4px;
    }

    .calendar-event-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .calendar-event-cancel {
        margin-top: 4px;
        color: #b91c1c;
        font-weight: 600;
    }

    .calendar-empty {
        color: #9ca3af;
        font-size: 12px;
    }

    .calendar-view-toggle {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .calendar-icon-btn {
        border: none;
        background: transparent;
        padding: 6px;
        border-radius: 999px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .calendar-icon-btn:hover {
        background: #e5e7eb;
    }

    .calendar-icon-btn .material-icons {
        font-size: 20px;
        color: #374151;
    }

    .calendar-icon-btn.is-active {
        background: #e0e7ff;
    }

    .calendar-icon-btn.is-active .material-icons {
        color: #3730a3;
    }
</style>

<div class="calendar-wrapper"
    data-cal-mes="<?= (int) $mesSeleccionado ?>"
    data-cal-anio="<?= (int) $anioSeleccionado ?>"
    data-cal-vista="<?= htmlspecialchars($vistaSeleccionada ?? 'mes') ?>"
    data-cal-fecha-base="<?= htmlspecialchars($fechaBaseStr ?? '') ?>">
    <div class="calendar-header">
        <button class="calendar-icon-btn" type="button" data-cal-nav="prev" title="Semana anterior / Mes anterior">
            <span class="material-icons">chevron_left</span>
        </button>
        <div class="calendar-title">
            <?php if (($vistaSeleccionada ?? 'mes') === 'semana'): ?>
                <?php
                $inicioSemana = DateTime::createFromFormat('Y-m-d', $desde);
                $finSemana = DateTime::createFromFormat('Y-m-d', $hasta);
                ?>
                Semana <?= $inicioSemana ? $inicioSemana->format('d/m/Y') : $desde ?> al <?= $finSemana ? $finSemana->format('d/m/Y') : $hasta ?>
            <?php else: ?>
                <?= htmlspecialchars($meses[$mesSeleccionado] ?? 'Mes') ?> <?= (int) $anioSeleccionado ?>
            <?php endif; ?>
        </div>
        <button class="calendar-icon-btn" type="button" data-cal-nav="next" title="Semana siguiente / Mes siguiente">
            <span class="material-icons">chevron_right</span>
        </button>
        <div class="calendar-view-toggle">
            <button class="calendar-icon-btn <?= ($vistaSeleccionada ?? 'mes') === 'mes' ? 'is-active' : '' ?>" type="button" data-cal-view="mes" title="Vista mensual">
                <span class="material-icons">calendar_month</span>
            </button>
            <button class="calendar-icon-btn <?= ($vistaSeleccionada ?? 'mes') === 'semana' ? 'is-active' : '' ?>" type="button" data-cal-view="semana" title="Vista semanal">
                <span class="material-icons">view_week</span>
            </button>
        </div>
    </div>

    <table class="calendar-grid">
        <thead>
            <tr>
                <th>Lun</th>
                <th>Mar</th>
                <th>Mie</th>
                <th>Jue</th>
                <th>Vie</th>
                <th>Sab</th>
                <th>Dom</th>
            </tr>
        </thead>
        <tbody>
            <?php if (($vistaSeleccionada ?? 'mes') === 'semana'): ?>
                <tr>
                    <?php
                    $inicioSemana = DateTime::createFromFormat('Y-m-d', $desde);
                    $fechasSemana = [];
                    if ($inicioSemana) {
                        for ($i = 0; $i < 7; $i++) {
                            $fechasSemana[] = $inicioSemana->format('Y-m-d');
                            $inicioSemana->modify('+1 day');
                        }
                    }
                    ?>
                    <?php foreach ($fechasSemana as $fechaKey): ?>
                        <?php
                        $fechaDia = DateTime::createFromFormat('Y-m-d', $fechaKey);
                        $pedidosDia = $pedidosPorFecha[$fechaKey] ?? [];
                        ?>
                        <td>
                            <div class="calendar-day">
                                <?= $fechaDia ? $fechaDia->format('d/m') : $fechaKey ?>
                            </div>
                            <?php if (empty($pedidosDia)): ?>
                                <div class="calendar-empty">Sin pedidos</div>
                            <?php else: ?>
                                <?php foreach ($pedidosDia as $pedido): ?>
                                    <div class="calendar-event">
                                        <div class="calendar-event-title">
                                            <?= htmlspecialchars($pedido['Alumno'] ?? 'Alumno') ?> - <?= htmlspecialchars($pedido['Menu'] ?? 'Menu') ?>
                                        </div>
                                        <div class="calendar-event-meta">
                                            <span class="badge <?= ($pedido['Estado'] ?? '') === 'Cancelado' ? 'danger' : (($pedido['Estado'] ?? '') === 'Entregado' ? 'success' : 'warning') ?>">
                                                <?= htmlspecialchars($pedido['Estado'] ?? '') ?>
                                            </span>
                                            <span>#<?= (int) ($pedido['Id'] ?? 0) ?></span>
                                        </div>
                                        <?php if (($pedido['Estado'] ?? '') === 'Cancelado' && !empty($pedido['motivo_cancelacion'])): ?>
                                            <div class="calendar-event-cancel">
                                                Motivo: <?= htmlspecialchars($pedido['motivo_cancelacion']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php else: ?>
                <?php foreach ($semanas as $semana): ?>
                    <tr>
                        <?php foreach ($semana as $diaMes): ?>
                            <td>
                                <?php if ($diaMes === null): ?>
                                    <div class="calendar-empty">-</div>
                                <?php else: ?>
                                    <?php
                                    $fechaKey = sprintf('%04d-%02d-%02d', $anioSeleccionado, $mesSeleccionado, $diaMes);
                                    $pedidosDia = $pedidosPorFecha[$fechaKey] ?? [];
                                    ?>
                                    <div class="calendar-day"><?= (int) $diaMes ?></div>
                                    <?php if (empty($pedidosDia)): ?>
                                        <div class="calendar-empty">Sin pedidos</div>
                                    <?php else: ?>
                                        <?php foreach ($pedidosDia as $pedido): ?>
                                            <div class="calendar-event">
                                                <div class="calendar-event-title">
                                                    <?= htmlspecialchars($pedido['Alumno'] ?? 'Alumno') ?> - <?= htmlspecialchars($pedido['Menu'] ?? 'Menu') ?>
                                                </div>
                                                <div class="calendar-event-meta">
                                                    <span class="badge <?= ($pedido['Estado'] ?? '') === 'Cancelado' ? 'danger' : (($pedido['Estado'] ?? '') === 'Entregado' ? 'success' : 'warning') ?>">
                                                        <?= htmlspecialchars($pedido['Estado'] ?? '') ?>
                                                    </span>
                                                    <span>#<?= (int) ($pedido['Id'] ?? 0) ?></span>
                                                </div>
                                                <?php if (($pedido['Estado'] ?? '') === 'Cancelado' && !empty($pedido['motivo_cancelacion'])): ?>
                                                    <div class="calendar-event-cancel">
                                                        Motivo: <?= htmlspecialchars($pedido['motivo_cancelacion']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
