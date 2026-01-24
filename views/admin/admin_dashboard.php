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
    </style>
</head>

<body>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <span class="material-icons logo-icon">dashboard</span>
                <span class="logo-text">AMPD</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li onclick="location.href='admin_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='admin_altaUsuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">person</span><span class="link-text">Alta usuarios</span>
                    </li>
                    <li onclick="location.href='admin_importarUsuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">upload_file</span><span class="link-text">Carga Masiva</span>
                    </li>
                    <li onclick="location.href='admin_pagoFacturas.php'">
                        <span class="material-icons" style="color: #5b21b6;">attach_money</span><span class="link-text">Pago Facturas</span>
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
                </div>

                <div class="card kpi-group-card">
                    <div class="kpi-group-header">
                        <h3 class="kpi-group-title">Resumen general</h3>
                        <div class="kpi-menu">
                            <button class="btn-icon kpi-menu-toggle" type="button" aria-label="Abrir menu" aria-expanded="false">
                                <span class="material-icons">more_horiz</span>
                            </button>
                            <div class="kpi-menu-panel" role="menu">
                                <form class="form-modern" id="kpi-filters-form">
                                    <div class="input-group">
                                        <label>Colegio</label>
                                        <div class="input-icon">
                                            <span class="material-icons">school</span>
                                            <select name="colegio" id="kpi-colegio">
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
                                            <select name="curso" id="kpi-curso">
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

                                    <div class="form-buttons">
                                        <button class="btn btn-aceptar" type="submit">Aplicar</button>
                                        <button class="btn btn-cancelar" type="button" id="kpi-clear">Limpiar</button>
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

            </section>

        </div>
    </div>
    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
    <script>
        const kpiMenuToggle = document.querySelector(".kpi-menu-toggle");
        const kpiMenuPanel = document.querySelector(".kpi-menu-panel");
        const kpiFiltersForm = document.getElementById("kpi-filters-form");
        const kpiClearBtn = document.getElementById("kpi-clear");
        const kpiColegioSelect = document.getElementById("kpi-colegio");
        const kpiCursoSelect = document.getElementById("kpi-curso");

        if (kpiMenuToggle && kpiMenuPanel) {
            kpiMenuToggle.addEventListener("click", (event) => {
                event.stopPropagation();
                const isOpen = kpiMenuPanel.classList.toggle("is-open");
                kpiMenuToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
            });

            kpiMenuPanel.addEventListener("click", (event) => {
                event.stopPropagation();
            });

            document.addEventListener("click", () => {
                if (kpiMenuPanel.classList.contains("is-open")) {
                    kpiMenuPanel.classList.remove("is-open");
                    kpiMenuToggle.setAttribute("aria-expanded", "false");
                }
            });
        }
    </script>
    <script>
        const kpiFields = document.querySelectorAll("[data-kpi]");

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

        const renderCursos = (cursos, selectedId) => {
            if (!kpiCursoSelect) {
                return;
            }
            const selectedValue = selectedId ? String(selectedId) : "";
            const options = [`<option value="">Todos</option>`];
            cursos.forEach((curso) => {
                const value = String(curso.Id);
                const isSelected = value === selectedValue ? "selected" : "";
                options.push(`<option value="${value}" ${isSelected}>${curso.Nombre ?? ""}</option>`);
            });
            kpiCursoSelect.innerHTML = options.join("");
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
            const data = await response.json();
            updateKpiCards(data);
            if (Array.isArray(data.cursos)) {
                renderCursos(data.cursos, data.cursoId);
            }
        };

        const scheduleFetch = () => {
            if (!kpiFiltersForm) {
                return;
            }
            if (window.kpiFetchTimer) {
                clearTimeout(window.kpiFetchTimer);
            }
            window.kpiFetchTimer = setTimeout(() => {
                fetchKpiData(new FormData(kpiFiltersForm));
            }, 150);
        };

        if (kpiFiltersForm) {
            kpiFiltersForm.addEventListener("submit", (event) => {
                event.preventDefault();
                scheduleFetch();
            });

            kpiFiltersForm.addEventListener("change", (event) => {
                if (event.target === kpiColegioSelect && kpiCursoSelect) {
                    kpiCursoSelect.value = "";
                }
                scheduleFetch();
            });
        }

        if (kpiClearBtn && kpiFiltersForm) {
            kpiClearBtn.addEventListener("click", () => {
                kpiFiltersForm.reset();
                scheduleFetch();
            });
        }

        
    </script>
</body>

</html>
