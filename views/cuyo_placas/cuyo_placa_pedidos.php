<?php
require_once __DIR__ . '/../../controllers/cuyo_placa_pedidosController.php';
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
                    <li onclick="location.href='cuyo_placa_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='cuyo_placa_pedidos.php'">
                        <span class="material-icons" style="color: #5b21b6;">receipt_long</span><span class="link-text">Pedidos</span>
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
                <div class="navbar-title">Pedidos</div>
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
                            <button class="btn-icon" type="button" data-tooltip="<?= htmlspecialchars($tooltipFiltros) ?>">
                                <span class="material-icons">help_outline</span>
                            </button>
                            <button class="btn-icon" id="descargarExcel" type="button" data-tooltip="Descargar Excel">
                                <span class="material-icons">download</span>
                            </button>
                            <button class="btn-icon" id="toggleFiltros" type="button">
                                <span class="material-icons">tune</span>
                            </button>
                            <div class="filtros-panel" id="panelFiltros">
                                <form class="form-modern" method="get" action="cuyo_placa_pedidos.php">
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
                                        <a class="btn btn-cancelar" href="cuyo_placa_pedidos.php">Limpiar</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="resumen-grid">
                        <div class="resumen-card-item resumen-total">
                            <button class="btn-icon resumen-remito" type="button"
                                data-tooltip="<?= htmlspecialchars("Remito digital:\n" . (empty($remitosPorPlanta['total']) ? 'Sin pedidos' : implode("\n", $remitosPorPlanta['total']))) ?>">
                                <span class="material-icons">receipt</span>
                            </button>
                            <div class="resumen-icon">
                                <span class="material-icons">receipt_long</span>
                            </div>
                            <div>
                                <div class="resumen-label">Total de pedidos</div>
                                <div class="resumen-value"><?= number_format($totalPedidos, 0, ',', '.') ?></div>
                            </div>
                            <div class="resumen-menus">
                                <?php
                                $menusMostrados = [];
                                foreach ($menuGrupos as $grupo) :
                                    ?>
                                    <div class="resumen-grupo"><?= htmlspecialchars($grupo['label']) ?></div>
                                    <?php foreach ($grupo['menus'] as $menu): ?>
                                        <div class="resumen-menu">
                                            <span><?= htmlspecialchars($menu) ?></span>
                                            <strong><?= number_format($totalMenus[$menu] ?? 0, 0, ',', '.') ?></strong>
                                        </div>
                                        <?php $menusMostrados[] = $menu; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                <?php
                                $menusRestantes = array_diff(array_keys($totalMenus), $menusMostrados);
                                ?>
                                <?php if (!empty($menusRestantes)): ?>
                                    <div class="resumen-grupo">Otros</div>
                                    <?php foreach ($menusRestantes as $menu): ?>
                                        <div class="resumen-menu">
                                            <span><?= htmlspecialchars($menu) ?></span>
                                            <strong><?= number_format($totalMenus[$menu] ?? 0, 0, ',', '.') ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php foreach ($resumenPlantas as $planta => $detalle): ?>
                            <div class="resumen-card-item">
                                <button class="btn-icon resumen-remito" type="button"
                                    data-tooltip="<?= htmlspecialchars("Remito digital:\n" . (empty($remitosPorPlanta[$planta]) ? 'Sin pedidos' : implode("\n", $remitosPorPlanta[$planta]))) ?>">
                                    <span class="material-icons">receipt</span>
                                </button>
                                <div class="resumen-icon alt">
                                    <span class="material-icons">factory</span>
                                </div>
                                <div class="resumen-label"><?= htmlspecialchars($planta) ?></div>
                                <div class="resumen-menus">
                                    <?php if (empty($detalle['menus'])): ?>
                                        <div class="resumen-empty">Sin pedidos</div>
                                    <?php else: ?>
                                        <?php
                                        $menusMostrados = [];
                                        foreach ($menuGrupos as $grupo) :
                                            ?>
                                            <div class="resumen-grupo"><?= htmlspecialchars($grupo['label']) ?></div>
                                            <?php foreach ($grupo['menus'] as $menu): ?>
                                                <div class="resumen-menu">
                                                    <span><?= htmlspecialchars($menu) ?></span>
                                                    <strong><?= number_format($detalle['menus'][$menu] ?? 0, 0, ',', '.') ?></strong>
                                                </div>
                                                <?php $menusMostrados[] = $menu; ?>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                        <?php
                                        $menusRestantes = array_diff(array_keys($detalle['menus']), $menusMostrados);
                                        ?>
                                        <?php if (!empty($menusRestantes)): ?>
                                            <div class="resumen-grupo">Otros</div>
                                            <?php foreach ($menusRestantes as $menu): ?>
                                                <div class="resumen-menu">
                                                    <span><?= htmlspecialchars($menu) ?></span>
                                                    <strong><?= number_format($detalle['menus'][$menu] ?? 0, 0, ',', '.') ?></strong>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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

        const detallePedidosExcel = <?php echo json_encode($detallePedidosExcel); ?>;
        const filtrosExcel = <?php
        echo json_encode([
            'fecha_desde' => $fechaDesde ?: '',
            'fecha_hasta' => $fechaHasta ?: '',
            'plantas' => $usarTodasLasPlantas ? 'Todas' : $plantasFiltro,
        ]);
        ?>;

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

        const botonDescarga = document.getElementById('descargarExcel');
        botonDescarga.addEventListener('click', () => {
            if (!Array.isArray(detallePedidosExcel) || detallePedidosExcel.length === 0) {
                alert('No hay datos para exportar con los filtros actuales.');
                return;
            }

            const filas = detallePedidosExcel.map((fila) => ({
                'Pedido ID': fila.pedido_id ?? '',
                'Fecha entrega': fila.fecha_entrega ?? '',
                'Fecha creacion': fila.fecha_creacion ?? '',
                'Usuario ID': fila.usuario_id ?? '',
                'Usuario': fila.usuario_nombre ?? '',
                'Planta': fila.planta ?? '',
                'Turno': fila.turno ?? '',
                'Menu': fila.menu ?? '',
                'Cantidad': Number(fila.cantidad ?? 0),
            }));

            const hoja = XLSX.utils.json_to_sheet(filas);
            hoja['!cols'] = [
                { wch: 12 },
                { wch: 14 },
                { wch: 20 },
                { wch: 12 },
                { wch: 24 },
                { wch: 16 },
                { wch: 12 },
                { wch: 26 },
                { wch: 10 },
            ];

            const libro = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(libro, hoja, 'Pedidos y plantas');

            const rangoDesde = filtrosExcel.fecha_desde ? filtrosExcel.fecha_desde.replace(/-/g, '') : 'inicio';
            const rangoHasta = filtrosExcel.fecha_hasta ? filtrosExcel.fecha_hasta.replace(/-/g, '') : 'hoy';
            const nombreArchivo = `cuyo_placa_pedidos_${rangoDesde}_${rangoHasta}.xlsx`;
            XLSX.writeFile(libro, nombreArchivo);
        });
    </script>

    <style>
        .resumen-card {
            position: relative;
            overflow: visible;
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
            z-index: 5;
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
            position: relative;
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
            margin-top: 12px;
        }

        .resumen-menu {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #374151;
        }

        .resumen-grupo {
            margin-top: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .resumen-remito {
            position: absolute;
            top: 12px;
            right: 12px;
        }

        [data-tooltip]::after {
            white-space: pre-line;
            max-width: 220px;
            text-align: left;
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
