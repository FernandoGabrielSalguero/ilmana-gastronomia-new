<?php
require_once __DIR__ . '/../../controllers/cuyo_placa_dashboardController.php';
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
</head>

<body>

    <!-- 🔲 CONTENEDOR PRINCIPAL -->
    <div class="layout">

        <!-- 🧭 SIDEBAR -->
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

        <!-- 🧱 MAIN -->
        <div class="main">

            <!-- 🟪 NAVBAR -->
            <header class="navbar">
                <button class="btn-icon" onclick="toggleSidebar()">
                    <span class="material-icons">menu</span>
                </button>
                <div class="navbar-title">Inicio</div>
            </header>

            <!-- 📦 CONTENIDO -->
            <section class="content">

                <!-- Bienvenida -->
                <div class="card">
                    <h2>Hola <?= htmlspecialchars($nombre) ?></h2>
                    <p>Selecciona un rango de fechas para ver la cantidad de pedidos realizados.</p>
                </div>

                <div class="card resumen-card">
                    <div class="resumen-header">
                        <div>
                            <h3>Resumen general</h3>
                            <p class="resumen-subtitle">Rango: <?= htmlspecialchars($rangoTexto) ?></p>
                        </div>
                        <div class="resumen-actions">
                            <button class="btn-icon" id="toggleFiltros" type="button" data-tooltip="<?= htmlspecialchars($tooltipFiltros) ?>">
                                <span class="material-icons">tune</span>
                            </button>
                            <div class="filtros-panel" id="panelFiltros">
                                <form class="form-modern" method="get" action="cuyo_placa_dashboard.php">
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

                                    <div class="input-group">
                                        <label>Planta</label>
                                        <div class="selector-list selector-compact">
                                            <label>
                                                <input type="checkbox" name="planta[]" value="todos" <?= $usarTodasLasPlantas ? 'checked' : '' ?>>
                                                <span>Todos</span>
                                            </label>
                                            <?php foreach ($plantasDisponibles as $planta): ?>
                                                <label>
                                                    <input type="checkbox" name="planta[]" value="<?= htmlspecialchars($planta) ?>"
                                                        <?= (!$usarTodasLasPlantas && in_array($planta, $plantasFiltro, true)) ? 'checked' : '' ?>>
                                                    <span><?= htmlspecialchars($planta) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="form-buttons">
                                        <button class="btn btn-aceptar" type="submit">Aplicar</button>
                                        <a class="btn btn-cancelar" href="cuyo_placa_dashboard.php">Limpiar</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="resumen-grid">
                        <div class="resumen-card-item resumen-total">
                            <div class="resumen-icon">
                                <span class="material-icons">receipt_long</span>
                            </div>
                            <div>
                                <div class="resumen-label">Total de pedidos</div>
                                <div class="resumen-value"><?= number_format($totalPedidos, 0, ',', '.') ?></div>
                                <div class="resumen-helper">Sumatoria de menus</div>
                            </div>
                        </div>

                        <?php foreach ($resumenPlantas as $planta => $detalle): ?>
                            <div class="resumen-card-item">
                                <div class="resumen-icon alt">
                                    <span class="material-icons">factory</span>
                                </div>
                                <div class="resumen-label"><?= htmlspecialchars($planta) ?></div>
                                <div class="resumen-menus">
                                    <?php if (empty($detalle['menus'])): ?>
                                        <div class="resumen-empty">Sin pedidos</div>
                                    <?php else: ?>
                                        <?php foreach ($detalle['menus'] as $menu => $cantidad): ?>
                                            <div class="resumen-menu">
                                                <span><?= htmlspecialchars($menu) ?></span>
                                                <strong><?= number_format($cantidad, 0, ',', '.') ?></strong>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </section>

        </div>
    </div>
    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);

        const toggleFiltros = document.getElementById('toggleFiltros');
        const panelFiltros = document.getElementById('panelFiltros');
        const filtroTodos = panelFiltros.querySelector('input[value="todos"]');
        const filtrosPlanta = Array.from(panelFiltros.querySelectorAll('input[name="planta[]"]')).filter(
            (input) => input.value !== 'todos'
        );

        toggleFiltros.addEventListener('click', () => {
            panelFiltros.classList.toggle('is-open');
        });

        document.addEventListener('click', (event) => {
            if (!panelFiltros.contains(event.target) && !toggleFiltros.contains(event.target)) {
                panelFiltros.classList.remove('is-open');
            }
        });

        filtroTodos.addEventListener('change', () => {
            if (filtroTodos.checked) {
                filtrosPlanta.forEach((input) => {
                    input.checked = false;
                });
            }
        });

        filtrosPlanta.forEach((input) => {
            input.addEventListener('change', () => {
                if (input.checked) {
                    filtroTodos.checked = false;
                } else if (!filtrosPlanta.some((item) => item.checked)) {
                    filtroTodos.checked = true;
                }
            });
        });
    </script>

    <style>
        .resumen-card {
            position: relative;
        }

        .resumen-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .resumen-header h3 {
            margin: 0;
        }

        .resumen-subtitle {
            margin-top: 4px;
            color: #6b7280;
        }

        .resumen-actions {
            position: relative;
            display: flex;
            gap: 8px;
        }

        .filtros-panel {
            position: absolute;
            right: 0;
            top: 46px;
            min-width: 280px;
            background: #ffffff;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
            opacity: 0;
            pointer-events: none;
            transform: translateY(8px);
            transition: all 0.2s ease;
            z-index: 10;
        }

        .filtros-panel.is-open {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .selector-compact {
            max-height: 180px;
            overflow: auto;
        }

        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 16px;
        }

        .resumen-card-item {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 16px;
            background: #ffffff;
        }

        .resumen-total {
            border: 1px solid #dbeafe;
            background: linear-gradient(120deg, #eff6ff, #ffffff);
        }

        .resumen-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #dcfce7;
            color: #15803d;
            margin-bottom: 12px;
        }

        .resumen-icon.alt {
            background: #e0e7ff;
            color: #3730a3;
        }

        .resumen-label {
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }

        .resumen-value {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .resumen-helper {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .resumen-menus {
            display: grid;
            gap: 8px;
        }

        .resumen-menu {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #374151;
        }

        .resumen-empty {
            font-size: 13px;
            color: #9ca3af;
        }

        @media (max-width: 640px) {
            .resumen-header {
                flex-direction: column;
                align-items: stretch;
            }

            .filtros-panel {
                right: auto;
                left: 0;
                width: 100%;
            }
        }
    </style>
</body>

</html>

