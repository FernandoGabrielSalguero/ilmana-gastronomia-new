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

    <!-- Descarga de consolidado (no se usa directamente aquÃ­, pero se deja por consistencia) -->
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <!-- PDF: html2canvas + jsPDF (CDN gratuitos) -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Tablas con saltos de pÃ¡gina prolijos (autoTable) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
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

                <div class="card">
                    <form class="form-modern" method="get" action="cuyo_placa_dashboard.php">
                        <div class="input-group">
                            <label>Desde</label>
                            <div class="input-icon">
                                <span class="material-icons">event</span>
                                <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde) ?>">
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Hasta</label>
                            <div class="input-icon">
                                <span class="material-icons">event</span>
                                <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta) ?>">
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Aplicar</button>
                            <a class="btn btn-cancelar" href="cuyo_placa_dashboard.php">Limpiar</a>
                        </div>
                    </form>
                </div>

                <div class="card-grid grid-2">
                    <div class="card">
                        <h3>Pedidos realizados</h3>
                        <p style="font-size: 28px; font-weight: 700;">
                            <?= number_format($totalPedidos, 0, ',', '.') ?>
                        </p>
                        <p style="color: #6b7280;">Rango: <?= htmlspecialchars($rangoTexto) ?></p>
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
</body>

</html>

