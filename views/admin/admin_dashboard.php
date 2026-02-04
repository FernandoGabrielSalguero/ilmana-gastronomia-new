<?php
require_once __DIR__ . '/../../controllers/admin_dashboardController.php';
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

    <!-- Descarga de consolidado (no se usa directamente aqui, pero se deja por consistencia) -->
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <!-- PDF: html2canvas + jsPDF (CDN gratuitos) -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Tablas con saltos de pagina prolijos (autoTable) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <!-- Graficos (Chart.js) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        .kpi-group-card {
            padding: 22px;
            overflow: visible;
            position: relative;
        }

        .kpi-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .kpi-group-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .kpi-menu {
            position: relative;
        }

        .kpi-help {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .kpi-menu-panel {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            min-width: 260px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            padding: 8px 0;
            display: none;
            z-index: 1000;
        }

        .kpi-menu-panel.is-open {
            display: block;
        }

        .kpi-menu-panel button {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            padding: 10px 14px;
            font-size: 14px;
            cursor: pointer;
        }

        .kpi-menu-panel button:hover {
            background: #f8fafc;
        }

        .kpi-menu-panel .form-modern {
            padding: 10px 14px 4px;
        }

        .kpi-menu-panel .input-group {
            margin-bottom: 12px;
        }

        .kpi-menu-panel .form-buttons {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .kpi-help-tooltip {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            min-width: 220px;
            background: #111827;
            color: #ffffff;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.4;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-4px);
            transition: opacity 0.12s ease, transform 0.12s ease;
            z-index: 1000;
        }

        .kpi-help-tooltip.is-open {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 16px;
        }

        .kpi-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
            background: #eef2ff;
            flex-shrink: 0;
        }

        .kpi-icon.success {
            color: #15803d;
            background: #dcfce7;
        }

        .kpi-icon.warning {
            color: #b45309;
            background: #fef3c7;
        }

        .kpi-icon.info {
            color: #0f766e;
            background: #ccfbf1;
        }

        .kpi-icon.neutral {
            color: #334155;
            background: #e2e8f0;
        }

        .kpi-label {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .welcome-actions {
            margin-top: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toggle-switch {
            position: relative;
            width: 46px;
            height: 24px;
            display: inline-block;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            inset: 0;
            background: #e2e8f0;
            border-radius: 999px;
            transition: background 0.2s ease;
        }

        .toggle-slider::before {
            content: "";
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            top: 3px;
            background: #ffffff;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
            transition: transform 0.2s ease;
        }

        .toggle-switch input:checked + .toggle-slider {
            background: #4f46e5;
        }

        .toggle-switch input:checked + .toggle-slider::before {
            transform: translateX(22px);
        }

        .toggle-label {
            font-size: 13px;
            color: #334155;
            font-weight: 600;
        }

        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
        }

        .kpi-table thead th {
            text-align: left;
            font-size: 13px;
            color: #6b7280;
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .kpi-table tbody td {
            padding: 12px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .kpi-table tbody tr:last-child td {
            border-bottom: none;
        }

        .kpi-table-count {
            font-weight: 600;
            color: #111827;
        }

        .kpi-table-wrap {
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
        }

        .sparkline {
            width: 140px;
            height: 34px;
            display: block;
        }

        .kpi-tooltip {
            position: fixed;
            pointer-events: none;
            background: #111827;
            color: #ffffff;
            padding: 6px 8px;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.2;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.1s ease;
            z-index: 2000;
            transform: translate(-50%, -100%);
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
                <div class="navbar-title">Inicio</div>
            </header>

            <!-- CONTENIDO -->
            <section class="content">

                <!-- Bienvenida -->
                <div class="card">
                    <h2>Hola</h2>
                    <p>En esta pagina, vamos a tener KPI.</p>
                    <div class="welcome-actions">
                        <label class="toggle-switch" aria-label="Vincular filtros">
                            <input type="checkbox" id="kpi-link-filters" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label">Vincular filtros</span>
                    </div>
                </div>

                <div class="card kpi-group-card">
                    <div class="kpi-group-header">
                        <h3 class="kpi-group-title">Resumen general</h3>
                        <div class="kpi-menu">
                            <div class="kpi-help">
                                <button class="btn-icon kpi-help-toggle" type="button" aria-label="Ver filtros aplicados">
                                    <span class="material-icons">help_outline</span>
                                </button>
                                <div class="kpi-help-tooltip"></div>
                            </div>
                            <button class="btn-icon kpi-menu-toggle" type="button" aria-label="Abrir menu" aria-expanded="false">
                                <span class="material-icons">more_horiz</span>
                            </button>
                            <div class="kpi-menu-panel" role="menu">
                                <form class="form-modern kpi-filters" data-filter-scope="global">
                                    <div class="input-group">
                                        <label>Colegio</label>
                                        <div class="input-icon">
                                            <span class="material-icons">school</span>
                                            <select name="colegio" class="kpi-colegio">
                                                <option value="">Todos</option>
                                                <?php foreach ($colegios as $colegio): ?>
                                                    <option value="<?= (int) $colegio['Id'] ?>" <?= $colegioId === (int) $colegio['Id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($colegio['Nombre'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <label>Curso</label>
                                        <div class="input-icon">
                                            <span class="material-icons">class</span>
                                            <select name="curso" class="kpi-curso">
                                                <option value="">Todos</option>
                                                <?php foreach ($cursos as $curso): ?>
                                                    <option value="<?= (int) $curso['Id'] ?>" <?= $cursoId === (int) $curso['Id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($curso['Nombre'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <label>Fecha desde</label>
                                        <div class="input-icon">
                                            <span class="material-icons">event</span>
                                            <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde) ?>">
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <label>Fecha hasta</label>
                                        <div class="input-icon">
                                            <span class="material-icons">event</span>
                                            <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta) ?>">
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div class="kpi-icon success">
                                <span class="material-icons">paid</span>
                            </div>
                            <div>
                                <div class="kpi-label">Saldo aprobado</div>
                                <div class="kpi-value" data-kpi="totalSaldoAprobado" data-type="currency">$<?= number_format($totalSaldoAprobado, 2, ',', '.') ?></div>
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icon warning">
                                <span class="material-icons">pending_actions</span>
                            </div>
                            <div>
                                <div class="kpi-label">Saldo pendiente para aprobar</div>
                                <div class="kpi-value" data-kpi="saldoPendiente" data-type="currency">$<?= number_format($saldoPendiente, 2, ',', '.') ?></div>
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icon neutral">
                                <span class="material-icons">receipt_long</span>
                            </div>
                            <div>
                                <div class="kpi-label">Pedidos de saldo</div>
                                <div class="kpi-value" data-kpi="totalPedidosSaldo" data-type="count"><?= number_format($totalPedidosSaldo, 0, ',', '.') ?></div>
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icon info">
                                <span class="material-icons">restaurant</span>
                            </div>
                            <div>
                                <div class="kpi-label">Pedidos de comida</div>
                                <div class="kpi-value" data-kpi="totalPedidosComida" data-type="count"><?= number_format($totalPedidosComida, 0, ',', '.') ?></div>
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icon">
                                <span class="material-icons">groups</span>
                            </div>
                            <div>
                                <div class="kpi-label">Usuarios rol "papas"</div>
                                <div class="kpi-value" data-kpi="totalPapas" data-type="count"><?= number_format($totalPapas, 0, ',', '.') ?></div>
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icon neutral">
                                <span class="material-icons">child_care</span>
                            </div>
                            <div>
                                <div class="kpi-label">Hijos registrados</div>
                                <div class="kpi-value" data-kpi="totalHijos" data-type="count"><?= number_format($totalHijos, 0, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card kpi-group-card">
                    <div class="kpi-group-header">
                        <h3 class="kpi-group-title">Pedidos por curso</h3>
                        <div class="kpi-menu">
                            <div class="kpi-help">
                                <button class="btn-icon kpi-help-toggle" type="button" aria-label="Ver filtros aplicados">
                                    <span class="material-icons">help_outline</span>
                                </button>
                                <div class="kpi-help-tooltip"></div>
                            </div>
                            <button class="btn-icon kpi-menu-toggle" type="button" aria-label="Abrir menu" aria-expanded="false">
                                <span class="material-icons">more_horiz</span>
                            </button>
                            <div class="kpi-menu-panel" role="menu">
                                <form class="form-modern kpi-filters" data-filter-scope="table">
                                    <div class="input-group">
                                        <label>Colegio</label>
                                        <div class="input-icon">
                                            <span class="material-icons">school</span>
                                            <select name="colegio" class="kpi-colegio">
                                                <option value="">Todos</option>
                                                <?php foreach ($colegios as $colegio): ?>
                                                    <option value="<?= (int) $colegio['Id'] ?>" <?= $colegioId === (int) $colegio['Id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($colegio['Nombre'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <label>Curso</label>
                                        <div class="input-icon">
                                            <span class="material-icons">class</span>
                                            <select name="curso" class="kpi-curso">
                                                <option value="">Todos</option>
                                                <?php foreach ($cursos as $curso): ?>
                                                    <option value="<?= (int) $curso['Id'] ?>" <?= $cursoId === (int) $curso['Id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($curso['Nombre'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <label>Fecha desde</label>
                                        <div class="input-icon">
                                            <span class="material-icons">event</span>
                                            <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde) ?>">
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <label>Fecha hasta</label>
                                        <div class="input-icon">
                                            <span class="material-icons">event</span>
                                            <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta) ?>">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-table-wrap">
                        <table class="kpi-table">
                            <thead>
                                <tr>
                                    <th>Colegio</th>
                                    <th>Curso</th>
                                    <th>Pedidos</th>
                                    <th>Por dia</th>
                                </tr>
                            </thead>
                            <tbody id="kpi-table-body">
                                <?php if (!empty($tablaPedidos)): ?>
                                    <?php foreach ($tablaPedidos as $index => $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['colegio'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row['curso'] ?? '') ?></td>
                                            <td class="kpi-table-count" data-kpi-table="count"><?= number_format((int) ($row['total'] ?? 0), 0, ',', '.') ?></td>
                                            <td>
                                                <canvas class="sparkline" data-series='<?= htmlspecialchars(json_encode($row['series'] ?? [])) ?>' aria-label="Pedidos por dia"></canvas>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">Sin datos para mostrar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

        </div>
    </div>
    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
    <script>
        const kpiMenus = document.querySelectorAll(".kpi-menu");
        kpiMenus.forEach((menu) => {
            const toggle = menu.querySelector(".kpi-menu-toggle");
            const panel = menu.querySelector(".kpi-menu-panel");
            if (!toggle || !panel) {
                return;
            }
            toggle.addEventListener("click", (event) => {
                event.stopPropagation();
                const isOpen = panel.classList.toggle("is-open");
                toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
            });

            panel.addEventListener("click", (event) => {
                event.stopPropagation();
            });

            document.addEventListener("click", () => {
                if (panel.classList.contains("is-open")) {
                    panel.classList.remove("is-open");
                    toggle.setAttribute("aria-expanded", "false");
                }
            });
        });
    </script>
    <script>
        const kpiFields = document.querySelectorAll("[data-kpi]");
        const kpiTableBody = document.getElementById("kpi-table-body");
        const kpiFilterForms = document.querySelectorAll(".kpi-filters");
        const kpiLinkFilters = document.getElementById("kpi-link-filters");
        const kpiHelpToggles = document.querySelectorAll(".kpi-help-toggle");

        const formatValue = (value, type) => {
            if (type === "currency") {
                return `$${value.toLocaleString("es-AR", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }
            return value.toLocaleString("es-AR");
        };

        const animateValue = (element, start, end, type) => {
            const duration = 700;
            const startTime = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - startTime) / duration, 1);
                const current = start + (end - start) * progress;
                const displayValue = type === "currency" ? Number(current.toFixed(2)) : Math.round(current);
                element.textContent = formatValue(displayValue, type);
                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            };

            requestAnimationFrame(tick);
        };

        const updateKpiCards = (data) => {
            kpiFields.forEach((field) => {
                const key = field.dataset.kpi;
                if (!(key in data)) {
                    return;
                }
                const type = field.dataset.type || "count";
                const currentText = field.textContent.replace(/[^0-9.,-]/g, "");
                const currentValue = currentText ? Number(currentText.replace(/\./g, "").replace(",", ".")) : 0;
                const nextValue = Number(data[key]) || 0;
                animateValue(field, currentValue, nextValue, type);
            });
        };

        const renderCursos = (cursos, selectedId, targetForms) => {
            const selectedValue = selectedId ? String(selectedId) : "";
            targetForms.forEach((form) => {
                const cursoSelect = form.querySelector(".kpi-curso");
                if (!cursoSelect) {
                    return;
                }
                const options = [`<option value="">Todos</option>`];
                cursos.forEach((curso) => {
                    const value = String(curso.Id);
                    const isSelected = value === selectedValue ? "selected" : "";
                    options.push(`<option value="${value}" ${isSelected}>${curso.Nombre ?? ""}</option>`);
                });
                cursoSelect.innerHTML = options.join("");
            });
        };

        const getOrCreateTooltip = (canvas) => {
            const tooltipId = "kpi-tooltip";
            let tooltipEl = document.getElementById(tooltipId);
            if (!tooltipEl) {
                tooltipEl = document.createElement("div");
                tooltipEl.id = tooltipId;
                tooltipEl.className = "kpi-tooltip";
                document.body.appendChild(tooltipEl);
            }
            return tooltipEl;
        };

        const buildFilterSummary = (form) => {
            const colegioSelect = form.querySelector(".kpi-colegio");
            const cursoSelect = form.querySelector(".kpi-curso");
            const fechaDesde = form.querySelector("input[name='fecha_desde']");
            const fechaHasta = form.querySelector("input[name='fecha_hasta']");

            const colegioText = colegioSelect?.selectedOptions?.[0]?.textContent?.trim() || "Todos";
            const cursoText = cursoSelect?.selectedOptions?.[0]?.textContent?.trim() || "Todos";
            const desdeText = fechaDesde?.value || "Sin fecha";
            const hastaText = fechaHasta?.value || "Sin fecha";

            return `Colegio: ${colegioText}<br>Curso: ${cursoText}<br>Desde: ${desdeText}<br>Hasta: ${hastaText}`;
        };

        const updateHelpTooltip = (toggle) => {
            const card = toggle.closest(".card");
            const form = card?.querySelector(".kpi-filters");
            const tooltip = toggle.parentElement?.querySelector(".kpi-help-tooltip");
            if (!form || !tooltip) {
                return;
            }
            tooltip.innerHTML = buildFilterSummary(form);
        };

        kpiHelpToggles.forEach((toggle) => {
            const tooltip = toggle.parentElement?.querySelector(".kpi-help-tooltip");
            const openTooltip = () => {
                updateHelpTooltip(toggle);
                tooltip?.classList.add("is-open");
            };
            const closeTooltip = () => tooltip?.classList.remove("is-open");

            toggle.addEventListener("mouseenter", openTooltip);
            toggle.addEventListener("focus", openTooltip);
            toggle.addEventListener("mouseleave", closeTooltip);
            toggle.addEventListener("blur", closeTooltip);
            toggle.addEventListener("click", (event) => {
                event.stopPropagation();
                updateHelpTooltip(toggle);
                tooltip?.classList.toggle("is-open");
            });
        });

        const renderSparkline = (canvas, series) => {
            const labels = series.map((item) => item.dia);
            const values = series.map((item) => item.total);
            if (canvas.chartInstance) {
                canvas.chartInstance.destroy();
            }
            canvas.chartInstance = new Chart(canvas, {
                type: "line",
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        borderColor: "#6366f1",
                        backgroundColor: "rgba(99, 102, 241, 0.15)",
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 2,
                        pointHoverRadius: 3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: false,
                            external: (context) => {
                                const { chart, tooltip } = context;
                                const tooltipEl = getOrCreateTooltip(chart.canvas);
                                if (tooltip.opacity === 0) {
                                    tooltipEl.style.opacity = 0;
                                    return;
                                }
                                const title = tooltip.title?.[0] ?? "";
                                const value = tooltip.dataPoints?.[0]?.formattedValue ?? "0";
                                tooltipEl.innerHTML = `${title}<br><strong>Pedidos: ${value}</strong>`;
                                const canvasRect = chart.canvas.getBoundingClientRect();
                                const point = tooltip.dataPoints?.[0]?.element;
                                const pointX = point ? point.x : tooltip.caretX;
                                const pointY = point ? point.y : tooltip.caretY;
                                tooltipEl.style.opacity = 1;
                                tooltipEl.style.left = `${canvasRect.left + pointX}px`;
                                tooltipEl.style.top = `${canvasRect.top + pointY - 8}px`;
                            }
                        }
                    },
                    scales: {
                        x: { display: false },
                        y: { display: false, beginAtZero: true }
                    }
                }
            });
        };

        const renderTablaPedidos = (rows) => {
            if (!kpiTableBody) {
                return;
            }
            if (!rows.length) {
                kpiTableBody.innerHTML = "<tr><td colspan=\"4\">Sin datos para mostrar.</td></tr>";
                return;
            }
            const html = rows.map((row) => {
                const seriesJson = JSON.stringify(row.series || []);
                return `
                    <tr>
                        <td>${row.colegio ?? ""}</td>
                        <td>${row.curso ?? ""}</td>
                        <td class="kpi-table-count" data-kpi-table="count">${Number(row.total || 0).toLocaleString("es-AR")}</td>
                        <td><canvas class="sparkline" data-series='${seriesJson.replace(/'/g, "&#39;")}' aria-label="Pedidos por dia"></canvas></td>
                    </tr>
                `;
            }).join("");
            kpiTableBody.innerHTML = html;
            kpiTableBody.querySelectorAll(".sparkline").forEach((canvas) => {
                const series = JSON.parse(canvas.dataset.series || "[]");
                renderSparkline(canvas, series);
            });
            kpiTableBody.querySelectorAll("[data-kpi-table]").forEach((cell) => {
                const nextValue = Number(cell.textContent.replace(/\./g, "").replace(",", ".")) || 0;
                animateValue(cell, 0, nextValue, "count");
            });
        };

        const fetchKpiData = async (formData) => {
            const params = new URLSearchParams(formData);
            params.set("ajax", "1");
            const response = await fetch(`admin_dashboard.php?${params.toString()}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });
            if (!response.ok) {
                return;
            }
            return response.json();
        };

        const isLinked = () => (kpiLinkFilters ? kpiLinkFilters.checked : true);

        const updateFromResponse = (data, scope, sourceForm) => {
            const linked = isLinked();
            if (linked || scope === "global") {
                updateKpiCards(data);
            }
            if (linked || scope === "table") {
                if (Array.isArray(data.tablaPedidos)) {
                    renderTablaPedidos(data.tablaPedidos);
                }
            }
            if (Array.isArray(data.cursos)) {
                const targets = linked ? Array.from(kpiFilterForms) : [sourceForm];
                renderCursos(data.cursos, data.cursoId, targets);
            }
        };

        const scheduleFetch = (form) => {
            if (!form) {
                return;
            }
            if (!window.kpiFetchTimers) {
                window.kpiFetchTimers = new Map();
            }
            const timers = window.kpiFetchTimers;
            if (timers.get(form)) {
                clearTimeout(timers.get(form));
            }
            timers.set(form, setTimeout(async () => {
                const data = await fetchKpiData(new FormData(form));
                if (data) {
                    updateFromResponse(data, form.dataset.filterScope || "global", form);
                }
            }, 150));
        };

        const syncForms = (sourceForm) => {
            if (!isLinked()) {
                return;
            }
            const data = new FormData(sourceForm);
            kpiFilterForms.forEach((form) => {
                if (form === sourceForm) {
                    return;
                }
                form.querySelectorAll("input, select").forEach((field) => {
                    if (!field.name) {
                        return;
                    }
                    const value = data.get(field.name) ?? "";
                    field.value = value;
                });
            });
        };

        kpiFilterForms.forEach((form) => {
            const colegioSelect = form.querySelector(".kpi-colegio");
            const cursoSelect = form.querySelector(".kpi-curso");
            const clearBtn = form.querySelector(".kpi-clear");

            form.addEventListener("submit", (event) => {
                event.preventDefault();
                syncForms(form);
                scheduleFetch(form);
            });

            form.addEventListener("change", (event) => {
                if (event.target === colegioSelect && cursoSelect) {
                    if (isLinked()) {
                        kpiFilterForms.forEach((otherForm) => {
                            const otherCurso = otherForm.querySelector(".kpi-curso");
                            if (otherCurso) {
                                otherCurso.value = "";
                            }
                        });
                    } else {
                        cursoSelect.value = "";
                    }
                }
                syncForms(form);
                scheduleFetch(form);
                kpiHelpToggles.forEach(updateHelpTooltip);
            });

            form.addEventListener("input", (event) => {
                if (event.target.type === "date") {
                    syncForms(form);
                    scheduleFetch(form);
                    kpiHelpToggles.forEach(updateHelpTooltip);
                }
            });

            if (clearBtn) {
                clearBtn.addEventListener("click", () => {
                    form.reset();
                    syncForms(form);
                    scheduleFetch(form);
                    kpiHelpToggles.forEach(updateHelpTooltip);
                });
            }
        });

        if (kpiLinkFilters) {
            kpiLinkFilters.addEventListener("change", () => {
                if (isLinked() && kpiFilterForms[0]) {
                    syncForms(kpiFilterForms[0]);
                    scheduleFetch(kpiFilterForms[0]);
                }
                kpiHelpToggles.forEach(updateHelpTooltip);
            });
        }

        document.querySelectorAll(".sparkline").forEach((canvas) => {
            const series = JSON.parse(canvas.dataset.series || "[]");
            renderSparkline(canvas, series);
        });

        document.querySelectorAll("[data-kpi-table]").forEach((cell) => {
            const nextValue = Number(cell.textContent.replace(/\./g, "").replace(",", ".")) || 0;
            animateValue(cell, 0, nextValue, "count");
        });
    </script>
</body>

</html>
