<?php
// Mostrar errores en pantalla (util en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesion y proteger acceso
session_start();
require_once __DIR__ . '/../../config.php';

// Expiracion por inactividad (20 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: /index.php?expired=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time(); // Actualiza el tiempo de actividad

// Proteccion de acceso general
if (!isset($_SESSION['usuario']) && !isset($_SESSION['usuario_id'])) {
    die("Acceso denegado. No has iniciado sesion.");
}

// Proteccion por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cocina') {
    die("Acceso restringido: esta pagina es solo para usuarios cocina.");
}

require_once __DIR__ . '/../../controllers/cocina_dashboardController.php';

// Datos del usuario en sesion
$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
$correo = $_SESSION['correo'] ?? 'Sin correo';
$usuario = $_SESSION['usuario'] ?? 'Sin usuario';
$telefono = $_SESSION['telefono'] ?? 'Sin telefono';
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
        .resumen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .resumen-subtitle {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .resumen-total-box {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            color: #0f172a;
        }

        .tabla-wrapper {
            max-height: 420px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 8px 10px;
            font-size: 14px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .data-table thead th {
            position: sticky;
            top: 0;
            background: #ffffff;
            z-index: 1;
        }

        .colegios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }

        .colegio-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 16px;
            background: #ffffff;
        }

        .colegio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .colegio-header h4 {
            margin: 0;
            font-size: 16px;
            color: #0f172a;
        }

        .colegio-total {
            font-size: 12px;
            font-weight: 600;
            color: #3730a3;
            background: #eef2ff;
            padding: 4px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .filtros-form {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 16px;
        }

        .filtros-form .input-group {
            min-width: 220px;
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
                    <li onclick="location.href='cocina_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
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
                    <h2>Hola <?= htmlspecialchars($nombre) ?></h2>
                    <p>Resumen diario de pedidos para cocina.</p>
                </div>

                <div class="card">
                    <div class="resumen-header">
                        <div>
                            <h3>Viandas por escuela y curso</h3>
                            <p class="resumen-subtitle">Fecha: <?= htmlspecialchars(date('d/m/Y', strtotime($fechaEntrega))) ?></p>
                        </div>
                        <div class="resumen-total-box">
                            <span class="material-icons">receipt_long</span>
                            <span>Total viandas: <?= number_format($totalViandas, 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <form class="form-modern filtros-form" method="get" action="cocina_dashboard.php">
                        <div class="input-group">
                            <label>Fecha de entrega</label>
                            <div class="input-icon">
                                <span class="material-icons">event</span>
                                <input type="date" name="fecha_entrega" value="<?= htmlspecialchars($fechaEntrega) ?>">
                            </div>
                        </div>
                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Aplicar</button>
                            <a class="btn btn-cancelar" href="cocina_dashboard.php">Limpiar</a>
                        </div>
                    </form>

                    <?php if (!empty($viandasPorColegio)): ?>
                        <div class="colegios-grid">
                            <?php foreach ($viandasPorColegio as $colegioNombre => $detalle): ?>
                                <div class="colegio-card">
                                    <div class="colegio-header">
                                        <h4><?= htmlspecialchars($colegioNombre) ?></h4>
                                        <span class="colegio-total">
                                            <?= number_format((int) ($detalle['total'] ?? 0), 0, ',', '.') ?> viandas
                                        </span>
                                    </div>
                                    <div class="tabla-wrapper">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Curso</th>
                                                    <th>Pedidos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (($detalle['cursos'] ?? []) as $cursoNombre => $cantidad): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($cursoNombre) ?></td>
                                                        <td><?= number_format((int) $cantidad, 0, ',', '.') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No hay pedidos de viandas para la fecha seleccionada.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="resumen-header">
                        <div>
                            <h3>Pedidos de Cuyo Placa</h3>
                            <p class="resumen-subtitle">Fecha: <?= htmlspecialchars(date('d/m/Y', strtotime($fechaEntrega))) ?></p>
                        </div>
                        <div class="resumen-total-box">
                            <span class="material-icons">inventory_2</span>
                            <span>Total unidades: <?= number_format($totalCuyoPlaca, 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <div class="tabla-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Planta</th>
                                    <th>Turno</th>
                                    <th>Menu</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($cuyoPlacaPedidos)): ?>
                                    <?php foreach ($cuyoPlacaPedidos as $pedido): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($pedido['pedido_id'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($pedido['fecha'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($pedido['usuario'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($pedido['planta'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($pedido['turno'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($pedido['menu'] ?? '')) ?></td>
                                            <td><?= number_format((int) ($pedido['cantidad'] ?? 0), 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">No hay pedidos de Cuyo Placa para la fecha seleccionada.</td>
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
</body>

</html>
