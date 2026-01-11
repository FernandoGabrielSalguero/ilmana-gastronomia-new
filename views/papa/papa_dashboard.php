<?php
// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controllers/papa_dashboardController.php';

// âš ï¸ ExpiraciÃ³n por inactividad (20 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: /index.php?expired=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time(); // Actualiza el tiempo de actividad



// ðŸ§¿ Control de sesiÃ³n activa
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['nombre'])) {
    header("Location: /index.php?expired=1");
    exit;
}

// ðŸ” ValidaciÃ³n estricta por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'papas') {
    die("ðŸš« Acceso restringido: esta secciÃ³n es solo para el rol 'papas'.");
}

// ðŸ“¦ AsignaciÃ³n de datos desde sesiÃ³n
$usuario_id = $_SESSION['usuario_id'];
$usuario = $_SESSION['usuario'] ?? 'Sin usuario';
$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
$correo = $_SESSION['correo'] ?? 'Sin correo';
$telefono = $_SESSION['telefono'] ?? 'Sin telÃ©fono';
$saldo = $_SESSION['saldo'] ?? '0.00';


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IlMana Gastronomia</title>

    <!-- Ãconos de Material Design -->
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
<style>
    /* Contenedor de tabla con scroll vertical */
    .tabla-wrapper {
        max-height: 450px;
        overflow-y: auto;
    }

    /* Tabla con ajuste dinÃ¡mico de columnas */
    .tabla-wrapper table {
        border-collapse: collapse;
        width: 100%;
        table-layout: auto;
        /* âš ï¸ CLAVE: que se adapte al contenido */
    }

    /* Cabecera fija */
    .tabla-wrapper thead th {
        position: sticky;
        top: 0;
        background-color: #fff;
        z-index: 2;
    }

    /* Altura de filas */
    .tabla-wrapper tbody tr {
        height: 44px;
    }

    /* Estilos generales de celdas */
    .data-table th,
    .data-table td {
        padding: 6px 8px;
        vertical-align: middle;
        overflow: hidden;
    }

    /* Si querÃ©s permitir quiebre de lÃ­nea en algunas columnas */
    .breakable {
        white-space: normal !important;
        word-wrap: break-word;
    }

    /* âœ… Clase opcional para limitar ancho mÃ¡ximo (solo si se aplica explÃ­citamente) */
    .max-150 {
        max-width: 150px;
        white-space: normal;
        overflow-wrap: break-word;
        word-break: break-word;
    }

    .max-80 {
        width: 80px;
        /* âœ… Lo fuerza como sugerencia */
        max-width: 80px;
        white-space: normal;
        overflow-wrap: break-word;
        word-break: break-word;
    }

    /* ancho boton de comprobante */
    .max-40 {
        width: 40px;
        max-width: 40px;
        text-align: center;
    }

    .badge {
        display: inline-block;
        max-width: 100%;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }
</style>

<body>

    <!-- ðŸ”² CONTENEDOR PRINCIPAL -->
    <div class="layout">

        <!-- ðŸ§­ SIDEBAR -->
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

        <!-- ðŸ§± MAIN -->
        <div class="main">

            <!-- ðŸŸª NAVBAR -->
            <header class="navbar">
                <button class="btn-icon" onclick="toggleSidebar()">
                    <span class="material-icons">menu</span>
                </button>
                <div class="navbar-title">Inicio</div>
            </header>

            <!-- ðŸ“¦ CONTENIDO -->
            <section class="content">

                                <!-- Bienvenida -->
                <div class="card">
                    <h2>Hola <?= htmlspecialchars($nombre) ?></h2>
                    <p>En esta pagina, vas a poder visualizar el nombre de tus hijos junto con su informacion, los pedidos de saldo y de comida</p>
                </div>

                <?php
                $saldoValor = (float)str_replace(',', '.', preg_replace('/[^0-9,\.\-]/', '', (string)$saldo));
                $saldoColor = $saldoValor < 0 ? 'red' : 'green';
                ?>

                <!-- Tarjetas de hijos y saldo -->
                <div class="card-grid grid-3">
                    <div class="card">
                        <h3>Saldo disponible</h3>
                        <p style="color: <?= $saldoColor ?>;">
                            $<?= number_format($saldoValor, 2, ',', '.') ?>
                        </p>
                        <a class="btn" href="papa_saldo_view.php">Cargar saldo</a>
                    </div>
                    <?php if (!empty($hijosDetalle)): ?>
                        <?php foreach ($hijosDetalle as $hijo): ?>
                            <?php
                            $preferencias = trim($hijo['Preferencia'] ?? '');
                            $colegio = trim($hijo['Colegio'] ?? '');
                            $curso = trim($hijo['Curso'] ?? '');
                            ?>
                            <div class="card">
                                <h3><?= htmlspecialchars($hijo['Nombre']) ?></h3>
                                <p><strong>Preferencias alimenticias:</strong> <?= $preferencias !== '' ? htmlspecialchars($preferencias) : 'Sin preferencias' ?></p>
                                <p><strong>Nombre del colegio:</strong> <?= $colegio !== '' ? htmlspecialchars($colegio) : 'Sin colegio' ?></p>
                                <p><strong>Curso:</strong> <?= $curso !== '' ? htmlspecialchars($curso) : 'Sin curso' ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card">
                            <p>No hay hijos asociados a este usuario.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tablas de resultados -->
                <div>
                    <!-- Pedidos de Comida -->
                    <div class="card tabla-card">
                        <h2>Pedidos de comida</h2>
                        <div class="tabla-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>AcciÃ³n</th>
                                        <th class="max-150">Alumno</th>
                                        <th class="max-150">MenÃº</th>
                                        <th>Fecha de entrega</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pedidosComida)): ?>
                                        <?php foreach ($pedidosComida as $pedido): ?>
                                            <tr>
                                                <td><?= $pedido['Id'] ?></td>
                                                <td><button class="btn btn-small">Cancelar</button></td>
                                                <td class="max-150 breakable"><?= htmlspecialchars($pedido['Alumno']) ?></td>
                                                <td class="max-150 breakable"><?= htmlspecialchars($pedido['Menu']) ?></td>
                                                <td><?= $pedido['Fecha_entrega'] ?></td>
                                                <td>
                                                    <span class="badge <?= $pedido['Estado'] === 'Procesando' ? 'success' : 'danger' ?>">
                                                        <?= $pedido['Estado'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">No hay pedidos de comida.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pedidos de Saldo -->
                    <div class="card tabla-card" style="margin-top: 16px;">
                        <h2>Pedidos de saldo</h2>
                        <div class="tabla-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th class="max-80">Saldo</th>
                                        <th class="max-80">Estado</th>
                                        <th>Comprobante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pedidosSaldo)): ?>
                                        <?php foreach ($pedidosSaldo as $saldo): ?>
                                            <tr>
                                                <td class="max-80"><?= $saldo['Id'] ?></td>
                                                <td>$<?= number_format($saldo['Saldo'], 2, ',', '.') ?></td>
                                                <td class="max-80">
                                                    <span class="badge <?= $saldo['Estado'] === 'Aprobado' ? 'success' : ($saldo['Estado'] === 'Cancelado' ? 'danger' : 'warning') ?>">
                                                        <?= $saldo['Estado'] ?>
                                                    </span>
                                                </td>
                                                <td class="max-40">
                                                    <?php if (!empty($saldo['Comprobante'])): ?>
                                                        <a href="/sistema/uploads/tax_invoices/<?= urlencode($saldo['Comprobante']) ?>"
                                                            target="_blank"
                                                            title="Ver comprobante"
                                                            style="display: inline-block; padding: 0; margin: 0; text-decoration: none;">
                                                            <span class="material-icons" style="font-size: 20px; color: #5b21b6;">visibility</span>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">â€”</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4">No hay pedidos de saldo.</td>
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

    <!-- Spinner Global -->
    <script src="../partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>





