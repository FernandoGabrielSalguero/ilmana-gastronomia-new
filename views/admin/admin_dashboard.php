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

                <div class="card">
                    <form class="form-modern" method="get">
                        <div class="input-group">
                            <label>Colegio</label>
                            <div class="input-icon">
                                <span class="material-icons">school</span>
                                <select name="colegio">
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
                                <select name="curso">
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
                            <button class="btn btn-aceptar" type="submit">Filtrar</button>
                            <a class="btn btn-cancelar" href="admin_dashboard.php">Limpiar</a>
                        </div>
                    </form>
                </div>

                <div class="card-grid grid-4">
                    <div class="card">
                        <h3>Pedidos realizados</h3>
                        <p><?= number_format($totalPedidos, 0, ',', '.') ?></p>
                    </div>
                    <div class="card">
                        <h3>Usuarios registrados</h3>
                        <p><?= number_format($totalUsuarios, 0, ',', '.') ?></p>
                    </div>
                    <div class="card">
                        <h3>Pedidos de saldo por aprobar</h3>
                        <p><?= number_format($totalSaldoPendiente, 0, ',', '.') ?></p>
                    </div>
                    <div class="card">
                        <h3>Saldo aprobado</h3>
                        <p>$<?= number_format($totalSaldoAprobado, 2, ',', '.') ?></p>
                    </div>
                </div>

                <div class="card">
                    <h3>Resumen de KPI</h3>
                    <canvas id="kpi-chart" height="110"></canvas>
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
        const kpiChartEl = document.getElementById("kpi-chart");
        if (kpiChartEl) {
            const kpiData = {
                labels: [
                    "Pedidos realizados",
                    "Usuarios registrados",
                    "Saldo por aprobar",
                    "Saldo aprobado"
                ],
                datasets: [{
                    label: "Totales",
                    data: [
                        <?= (int) $totalPedidos ?>,
                        <?= (int) $totalUsuarios ?>,
                        <?= (int) $totalSaldoPendiente ?>,
                        <?= json_encode(round($totalSaldoAprobado, 2)) ?>
                    ],
                    backgroundColor: ["#5b21b6", "#0f766e", "#f59e0b", "#16a34a"],
                    borderRadius: 6
                }]
            };

            new Chart(kpiChartEl, {
                type: "bar",
                data: kpiData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>
