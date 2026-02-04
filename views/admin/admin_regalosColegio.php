<?php
require_once __DIR__ . '/../../controllers/admin_regalosColegioController.php';

$formatDate = function ($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '-';
    }
    $parts = explode('-', $value);
    if (count($parts) !== 3) {
        return htmlspecialchars($value);
    }
    return htmlspecialchars($parts[2] . '-' . $parts[1] . '-' . $parts[0]);
};
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IlMana Gastronomia</title>

    <!-- Iconos de Material Design -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

    <!-- Framework Success desde CDN -->
    <link rel="stylesheet" href="https://framework.impulsagroup.com/assets/css/framework.css">
    <script src="https://framework.impulsagroup.com/assets/javascript/framework.js" defer></script>

    <style>
        .tabla-wrapper {
            max-height: 700px;
            overflow-y: auto;
        }

        .tabla-wrapper table {
            border-collapse: collapse;
            width: 100%;
            table-layout: auto;
        }

        .tabla-wrapper thead th {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 2;
        }

        .data-table th,
        .data-table td {
            padding: 8px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .cell-muted {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
        }

        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 12px 14px;
            background: #fff;
        }

        .summary-label {
            font-size: 12px;
            color: #6b7280;
        }

        .summary-value {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .modal-content.modal-large {
            width: fit-content;
            max-width: 95vw;
        }

        .modal-content.modal-large .tabla-wrapper {
            max-height: none;
            overflow: visible;
        }

        .action-icon {
            cursor: pointer;
            font-size: 20px;
            color: #5b21b6;
            vertical-align: middle;
        }

        .action-icon + .action-icon {
            margin-left: 10px;
        }

        .action-icon.is-disabled {
            color: #9ca3af;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>

<body>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <span class="material-icons logo-icon">dashboard</span>
                <span class="logo-text">Il'Mana</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li onclick="location.href='admin_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='admin_viandasColegio.php'">
                        <span class="material-icons" style="color: #5b21b6;">restaurant_menu</span><span class="link-text">Menu</span>
                    </li>
                    <li onclick="location.href='admin_entregasColegios.php'">
                        <span class="material-icons" style="color: #5b21b6;">school</span><span class="link-text">Colegio</span>
                    </li>
                    <li onclick="location.href='admin_saldo.php'">
                        <span class="material-icons" style="color: #5b21b6;">paid</span><span class="link-text">Saldos</span>
                    </li>
                    <li onclick="location.href='admin_usuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">people</span><span class="link-text">Usuarios</span>
                    </li>
                    <li onclick="location.href='admin_cuyoPlacas.php'">
                        <span class="material-icons" style="color: #5b21b6;">factory</span><span class="link-text">Cuyo Placas</span>
                    </li>
                    <li onclick="location.href='admin_logs.php'">
                        <span class="material-icons" style="color: #5b21b6;">history</span><span class="link-text">Logs</span>
                    </li>
                    <li onclick="location.href='admin_regalosColegio.php'">
                        <span class="material-icons" style="color: #5b21b6;">card_giftcard</span><span class="link-text">Regalos</span>
                    </li>
                    <li onclick="location.href='../../../logout.php'">
                        <span class="material-icons" style="color: red;">logout</span><span class="link-text">Salir</span>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <button class="btn-icon" onclick="toggleSidebar()">
                    <span class="material-icons" id="collapseIcon">chevron_left</span>
                </button>
            </div>
        </aside>

        <!-- MAIN -->
        <div class="main">

            <!-- NAVBAR -->
            <header class="navbar">
                <button class="btn-icon" onclick="toggleSidebar()">
                    <span class="material-icons">menu</span>
                </button>
                <div class="navbar-title">Regalos</div>
            </header>

            <!-- CONTENIDO -->
            <section class="content">
                <div class="card">
                    <h2>Regalos por semana</h2>
                    <p>Filtra una semana (lunes a jueves) y revisa cuantas viandas pidio cada hijo para asignar premios.</p>
                </div>

                <?php if (!empty($errores)): ?>
                    <div class="card" style="border-left: 4px solid #dc2626;">
                        <p><strong>Hubo un problema:</strong></p>
                        <ul>
                            <?php foreach ($errores as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filtro semanal</h3>
                    </div>
                    <div class="card-body">
                        <form class="form-modern" method="get">
                            <div class="form-grid grid-4">
                                <div class="input-group">
                                    <label for="fecha_desde">Desde</label>
                                    <div class="input-icon input-icon-date">
                                        <input type="date" id="fecha_desde" name="fecha_desde"
                                            value="<?= htmlspecialchars($fechaDesde) ?>" required />
                                    </div>
                                </div>

                                <div class="input-group">
                                    <label for="fecha_hasta">Hasta</label>
                                    <div class="input-icon input-icon-date">
                                        <input type="date" id="fecha_hasta" name="fecha_hasta"
                                            value="<?= htmlspecialchars($fechaHasta) ?>" required />
                                    </div>
                                </div>

                                <div class="input-group">
                                    <label>Dias habiles</label>
                                    <div class="input-icon">
                                        <input type="text" value="<?= (int) $diasHabiles ?>" readonly />
                                    </div>
                                </div>
                            </div>

                            <div class="gform-helper" style="margin-top: 6px;">
                                Solo se cuentan pedidos no cancelados. Semana valida: lunes a jueves.
                            </div>

                            <div class="form-buttons">
                                <button class="btn btn-aceptar" type="submit">Filtrar</button>
                                <a class="btn btn-cancelar" href="admin_regalosColegio.php">Semana actual</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="summary-label">Rango</div>
                            <div class="summary-value"><?= $formatDate($fechaDesde) ?> → <?= $formatDate($fechaHasta) ?></div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Hijos con pedidos</div>
                            <div class="summary-value"><?= number_format($totalNinos, 0, ',', '.') ?></div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Total de viandas</div>
                            <div class="summary-value"><?= number_format($totalViandas, 0, ',', '.') ?></div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Semana completa</div>
                            <div class="summary-value"><?= number_format($totalCompletos, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Viandas por fecha de entrega</h3>
                    </div>
                    <div class="card-body">
                        <div class="tabla-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Fecha de entrega</th>
                                        <th>Viandas</th>
                                        <th>Hijos unicos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($resumenEntregas)): ?>
                                        <?php foreach ($resumenEntregas as $row): ?>
                                            <tr>
                                                <td><?= $formatDate($row['Fecha_Entrega'] ?? '') ?></td>
                                                <td><?= number_format((int) ($row['Total_Viandas'] ?? 0), 0, ',', '.') ?></td>
                                                <td><?= number_format((int) ($row['Hijos_Unicos'] ?? 0), 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3">Sin datos de entrega para el rango seleccionado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Regalos registrados</h3>
                    </div>
                    <div class="card-body">
                        <div class="tabla-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th>Colegio</th>
                                        <th>Curso</th>
                                        <th>Nivel</th>
                                        <th>Fecha entrega</th>
                                        <th>Menus por dia</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($regalos)): ?>
                                        <?php foreach ($regalos as $regalo): ?>
                                            <?php
                                            $menusSemana = [];
                                            $menusRaw = (string) ($regalo['Menus_Semana'] ?? '');
                                            $menusDecoded = json_decode($menusRaw, true);
                                            if (is_array($menusDecoded)) {
                                                foreach ($menusDecoded as $item) {
                                                    $fechaMenu = $formatDate($item['fecha'] ?? '');
                                                    $menuNombre = htmlspecialchars((string) ($item['menu'] ?? '-'));
                                                    $menusSemana[] = $fechaMenu . ' - ' . $menuNombre;
                                                }
                                            }
                                            $menusLabel = !empty($menusSemana) ? implode('<br>', $menusSemana) : '-';
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($regalo['Alumno_Nombre'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($regalo['Colegio_Nombre'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($regalo['Curso_Nombre'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($regalo['Nivel_Educativo'] ?? '-') ?></td>
                                                <td><?= $formatDate($regalo['Fecha_Entrega_Jueves'] ?? '') ?></td>
                                                <td><?= $menusLabel ?></td>
                                                <td>
                                                    <form method="post" onsubmit="return confirm('Eliminar regalo?');" style="display:inline;">
                                                        <input type="hidden" name="accion" value="eliminar_regalo" />
                                                        <input type="hidden" name="regalo_id"
                                                            value="<?= (int) ($regalo['Id'] ?? 0) ?>" />
                                                        <button type="submit" class="btn-icon" title="Eliminar">
                                                            <span class="material-icons">delete</span>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7">Sin regalos registrados en el rango.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Detalle por hijo</h3>
                    </div>
                    <div class="card-body">
                        <div class="gform-helper" style="margin-bottom: 10px;">
                            Semana completa = una entrega por cada dia habil (lunes a jueves).
                        </div>
                        <div class="tabla-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Hijo</th>
                                        <th>Colegio</th>
                                        <th>Curso</th>
                                        <th>Nivel</th>
                                        <th>Dias con entrega</th>
                                        <th>Dias de compra</th>
                                        <th>Viandas en semana</th>
                                        <th>Semana completa</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($registros)): ?>
                                        <?php foreach ($registros as $row): ?>
                                            <?php
                                            $diasConEntrega = (int) ($row['Dias_Con_Entrega'] ?? 0);
                                            $diasConCompra = (int) ($row['Dias_Con_Compra'] ?? 0);
                                            $totalPedidos = (int) ($row['Total_Pedidos'] ?? 0);
                                            $esCompleta = $diasHabiles > 0 && $diasConEntrega >= $diasHabiles;
                                            $badgeClass = $esCompleta ? 'success' : 'warning';
                                            $badgeLabel = $esCompleta ? 'Completa' : 'Incompleta';
                                            $detalleRaw = (string) ($row['Detalle_Entrega'] ?? '');
                                            $detalleItems = array_values(array_filter(array_map('trim', explode('||', $detalleRaw))));
                                            $detalleEntregas = [];
                                            foreach ($detalleItems as $item) {
                                                $parts = explode('|', $item, 2);
                                                $fechaItem = $parts[0] ?? '';
                                                $menuItem = $parts[1] ?? '';
                                                if ($fechaItem === '') {
                                                    continue;
                                                }
                                                $detalleEntregas[] = [
                                                    'fecha' => $fechaItem,
                                                    'menu' => $menuItem
                                                ];
                                            }
                                            $detalleEntregasJson = htmlspecialchars(json_encode($detalleEntregas, JSON_UNESCAPED_UNICODE));
                                            $alumnoNombre = htmlspecialchars((string) ($row['Hijo_Nombre'] ?? ''));
                                            $colegioNombre = htmlspecialchars((string) ($row['Colegio_Nombre'] ?? '-'));
                                            $cursoNombre = htmlspecialchars((string) ($row['Curso_Nombre'] ?? '-'));
                                            $nivelNombre = htmlspecialchars((string) ($row['Nivel_Educativo'] ?? '-'));
                                            $alumnoKey = strtolower(trim((string) ($row['Hijo_Nombre'] ?? ''))) . '|' . $juevesSemana;
                                            $tieneRegalo = !empty($regalosIndex[$alumnoKey]);
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['Hijo_Nombre'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($row['Colegio_Nombre'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($row['Curso_Nombre'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($row['Nivel_Educativo'] ?? '-') ?></td>
                                                <td>
                                                    <?= number_format($diasConEntrega, 0, ',', '.') ?>
                                                    <div class="cell-muted">
                                                        de <?= number_format($diasHabiles, 0, ',', '.') ?> habiles
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= number_format($diasConCompra, 0, ',', '.') ?>
                                                    <div class="cell-muted">
                                                        <?= $formatDate($row['Primera_Compra'] ?? '') ?> → <?= $formatDate($row['Ultima_Compra'] ?? '') ?>
                                                    </div>
                                                </td>
                                                <td><?= number_format($totalPedidos, 0, ',', '.') ?></td>
                                                <td>
                                                    <span class="badge <?= htmlspecialchars($badgeClass) ?>">
                                                        <?= htmlspecialchars($badgeLabel) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="material-icons action-icon"
                                                        title="Ver entregas por dia"
                                                        data-action="ver-entregas"
                                                        data-alumno="<?= $alumnoNombre ?>"
                                                        data-detalle='<?= $detalleEntregasJson ?>'>event</span>
                                                    <span class="material-icons action-icon <?= $tieneRegalo ? 'is-disabled' : '' ?>"
                                                        title="Agregar regalo"
                                                        data-action="agregar-regalo"
                                                        data-alumno="<?= $alumnoNombre ?>"
                                                        data-colegio="<?= $colegioNombre ?>"
                                                        data-curso="<?= $cursoNombre ?>"
                                                        data-nivel="<?= $nivelNombre ?>"
                                                        data-fecha-jueves="<?= htmlspecialchars($juevesSemana) ?>"
                                                        data-detalle='<?= $detalleEntregasJson ?>'>card_giftcard</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8">Sin datos para el rango seleccionado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div id="modalEntregas" class="modal hidden">
        <div class="modal-content modal-large">
            <h3 id="modalEntregasTitulo">Detalle de entregas</h3>
            <div id="modalEntregasResumen" class="gform-helper" style="margin: 8px 0;"></div>
            <div class="tabla-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Vianda</th>
                        </tr>
                    </thead>
                    <tbody id="modalEntregasBody"></tbody>
                </table>
            </div>
            <div class="form-buttons">
                <button class="btn btn-aceptar" type="button" onclick="cerrarModalEntregas()">Cerrar</button>
            </div>
        </div>
    </div>

    <div id="modalRegalo" class="modal hidden">
        <div class="modal-content modal-large">
            <h3>Registrar regalo</h3>
            <div class="gform-helper" style="margin: 6px 0 12px;">
                El regalo se entrega el ultimo jueves de la semana seleccionada.
            </div>
            <div class="card-grid grid-2" style="margin-bottom: 12px;">
                <div class="summary-card">
                    <div class="summary-label">Alumno</div>
                    <div class="summary-value" id="regaloAlumno">-</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Fecha entrega (jueves)</div>
                    <div class="summary-value" id="regaloFechaJueves">-</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Colegio</div>
                    <div class="summary-value" id="regaloColegio">-</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Curso / Nivel</div>
                    <div class="summary-value" id="regaloCursoNivel">-</div>
                </div>
            </div>

            <div class="tabla-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Menu</th>
                        </tr>
                    </thead>
                    <tbody id="modalRegaloBody"></tbody>
                </table>
            </div>

            <form method="post" style="margin-top: 16px;">
                <input type="hidden" name="accion" value="agregar_regalo" />
                <input type="hidden" name="alumno_nombre" id="inputAlumnoNombre" />
                <input type="hidden" name="colegio_nombre" id="inputColegioNombre" />
                <input type="hidden" name="curso_nombre" id="inputCursoNombre" />
                <input type="hidden" name="nivel_educativo" id="inputNivelEducativo" />
                <input type="hidden" name="fecha_entrega_jueves" id="inputFechaJueves" />
                <input type="hidden" name="menus_semana" id="inputMenusSemana" />

                <div class="form-buttons">
                    <button class="btn btn-aceptar" type="submit">Guardar regalo</button>
                    <button class="btn btn-cancelar" type="button" onclick="cerrarModalRegalo()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const alertMensaje = <?= json_encode($mensajeExito ?? '') ?>;
        if (alertMensaje && typeof window.showAlert === 'function') {
            if (window.showAlert.length <= 1) {
                window.showAlert({ type: 'success', message: alertMensaje });
            } else {
                window.showAlert('success', alertMensaje);
            }
        }

        const modalEntregas = document.getElementById('modalEntregas');
        const modalEntregasTitulo = document.getElementById('modalEntregasTitulo');
        const modalEntregasBody = document.getElementById('modalEntregasBody');
        const modalEntregasResumen = document.getElementById('modalEntregasResumen');
        const modalRegalo = document.getElementById('modalRegalo');
        const modalRegaloBody = document.getElementById('modalRegaloBody');
        const regaloAlumno = document.getElementById('regaloAlumno');
        const regaloFechaJueves = document.getElementById('regaloFechaJueves');
        const regaloColegio = document.getElementById('regaloColegio');
        const regaloCursoNivel = document.getElementById('regaloCursoNivel');
        const inputAlumnoNombre = document.getElementById('inputAlumnoNombre');
        const inputColegioNombre = document.getElementById('inputColegioNombre');
        const inputCursoNombre = document.getElementById('inputCursoNombre');
        const inputNivelEducativo = document.getElementById('inputNivelEducativo');
        const inputFechaJueves = document.getElementById('inputFechaJueves');
        const inputMenusSemana = document.getElementById('inputMenusSemana');

        function abrirModalEntregas(nombre, detalles) {
            modalEntregasTitulo.textContent = `Entregas de ${nombre}`;
            if (!Array.isArray(detalles) || detalles.length === 0) {
                modalEntregasResumen.textContent = 'Sin entregas en el rango seleccionado.';
                modalEntregasBody.innerHTML = `
                    <tr>
                        <td colspan="2">No hay registros.</td>
                    </tr>
                `;
            } else {
                const items = detalles.map((item) => {
                    const fecha = (item.fecha || '').split('-').reverse().join('-');
                    const menu = item.menu ? item.menu : '-';
                    return `
                        <tr>
                            <td><strong>${fecha}</strong></td>
                            <td>${menu}</td>
                        </tr>
                    `;
                }).join('');
                const diasUnicos = new Set(detalles.map((item) => item.fecha || '')).size;
                modalEntregasResumen.textContent = `Se entregaron viandas en ${diasUnicos} dia(s).`;
                modalEntregasBody.innerHTML = items;
            }
            modalEntregas.classList.remove('hidden');
        }

        function cerrarModalEntregas() {
            modalEntregas.classList.add('hidden');
        }

        function abrirModalRegalo(payload) {
            const detalles = payload.detalle || [];
            const items = detalles.map((item) => {
                const fecha = (item.fecha || '').split('-').reverse().join('-');
                const menu = item.menu ? item.menu : '-';
                return `
                    <tr>
                        <td><strong>${fecha}</strong></td>
                        <td>${menu}</td>
                    </tr>
                `;
            }).join('');

            modalRegaloBody.innerHTML = items || '<tr><td colspan="2">No hay registros.</td></tr>';
            regaloAlumno.textContent = payload.alumno || '-';
            regaloColegio.textContent = payload.colegio || '-';
            regaloCursoNivel.textContent = `${payload.curso || '-'} / ${payload.nivel || '-'}`;
            regaloFechaJueves.textContent = (payload.fechaJueves || '').split('-').reverse().join('-');

            inputAlumnoNombre.value = payload.alumno || '';
            inputColegioNombre.value = payload.colegio || '';
            inputCursoNombre.value = payload.curso || '';
            inputNivelEducativo.value = payload.nivel || '';
            inputFechaJueves.value = payload.fechaJueves || '';
            inputMenusSemana.value = JSON.stringify(detalles);

            modalRegalo.classList.remove('hidden');
        }

        function cerrarModalRegalo() {
            modalRegalo.classList.add('hidden');
        }

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            const accion = target.dataset.action;
            if (accion === 'ver-entregas') {
                const nombre = target.dataset.alumno || 'Alumno';
                const detalle = target.dataset.detalle ? JSON.parse(target.dataset.detalle) : [];
                abrirModalEntregas(nombre, detalle);
            }
            if (accion === 'agregar-regalo') {
                const detalle = target.dataset.detalle ? JSON.parse(target.dataset.detalle) : [];
                abrirModalRegalo({
                    alumno: target.dataset.alumno || '',
                    colegio: target.dataset.colegio || '',
                    curso: target.dataset.curso || '',
                    nivel: target.dataset.nivel || '',
                    fechaJueves: target.dataset.fechaJueves || '',
                    detalle
                });
            }
        });
    </script>
</body>

</html>
