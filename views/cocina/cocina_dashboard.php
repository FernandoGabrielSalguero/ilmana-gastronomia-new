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
        .resumen-general {
            position: relative;
        }

        .resumen-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .resumen-title {
            margin: 0 0 4px;
        }

        .resumen-subtitle {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .cursos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 16px;
            overflow-x: hidden;
        }

        .curso-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            min-height: 190px;
            min-width: 0;
            overflow-x: hidden;
        }

        .curso-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .curso-card h4 {
            margin: 0;
            font-size: 16px;
            color: #0f172a;
        }

        .curso-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #fef9c3;
            color: #a16207;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .curso-count {
            font-size: 12px;
            font-weight: 600;
            color: #3730a3;
            background: #eef2ff;
            padding: 4px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .curso-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .curso-menus {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 180px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .curso-menus li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 14px;
            color: #374151;
            word-break: break-word;
        }

        .curso-menus li:last-child {
            border-bottom: none;
        }

        .menu-count {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            background: #e0f2fe;
            padding: 2px 8px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .curso-empty {
            color: #9ca3af;
            font-size: 14px;
        }

        .resumen-total-card {
            justify-content: center;
            align-items: flex-start;
            gap: 8px;
        }

        .resumen-total-card.is-primary {
            position: relative;
            background: linear-gradient(135deg, #fff7ed 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            overflow: hidden;
        }

        .resumen-total-card.is-primary::after {
            content: "";
            position: absolute;
            inset: -40px;
            background: radial-gradient(circle at top left, rgba(14, 165, 233, 0.28), transparent 60%);
            filter: blur(18px);
            z-index: 0;
        }

        .resumen-total-card.is-primary > * {
            position: relative;
            z-index: 1;
        }

        .resumen-total-number {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
        }

        .resumen-metrics {
            display: grid;
            gap: 6px;
            margin-top: 6px;
        }

        .resumen-metric {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 13px;
            color: #0f172a;
        }

        .resumen-metric-label {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 11px;
            color: #64748b;
        }

        .resumen-metric-value {
            font-weight: 700;
        }

        .nivel-section {
            margin-top: 16px;
        }

        .nivel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 4px 0 12px;
        }

        .nivel-title {
            margin: 0;
            font-size: 16px;
            color: #0f172a;
        }

        .nivel-total {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            background: #dbeafe;
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

                <div class="card resumen-general">
                    <div class="resumen-header">
                        <div>
                            <h3 class="resumen-title">Viandas por escuela y curso</h3>
                            <p class="resumen-subtitle">Fecha: <?= htmlspecialchars(date('d/m/Y', strtotime($fechaEntrega))) ?></p>
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

                    <div class="cursos-grid">
                        <div class="card curso-card resumen-total-card is-primary">
                            <div class="curso-card-header">
                                <h4>Pedidos del dia</h4>
                            </div>
                            <div class="curso-meta">
                                <span class="curso-icon">
                                    <span class="material-icons">receipt_long</span>
                                </span>
                                <span class="curso-count">Total</span>
                            </div>
                            <div class="resumen-total-number">
                                <?= number_format($totalPedidosDia, 0, ',', '.') ?>
                            </div>
                            <div class="resumen-metrics">
                                <div class="resumen-metric">
                                    <span class="resumen-metric-label">Total</span>
                                    <span class="resumen-metric-value">
                                        <?= number_format($totalPedidosDia, 0, ',', '.') ?>
                                    </span>
                                </div>
                                <div class="resumen-metric">
                                    <span class="resumen-metric-label">Inicial</span>
                                    <span class="resumen-metric-value">
                                        <?= number_format((int) ($totalesPorNivel['Inicial'] ?? 0), 0, ',', '.') ?>
                                    </span>
                                </div>
                                <div class="resumen-metric">
                                    <span class="resumen-metric-label">Primaria</span>
                                    <span class="resumen-metric-value">
                                        <?= number_format((int) ($totalesPorNivel['Primaria'] ?? 0), 0, ',', '.') ?>
                                    </span>
                                </div>
                                <div class="resumen-metric">
                                    <span class="resumen-metric-label">Secundaria</span>
                                    <span class="resumen-metric-value">
                                        <?= number_format((int) ($totalesPorNivel['Secundaria'] ?? 0), 0, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($nivelesList)): ?>
                        <?php foreach ($nivelesList as $nivelData): ?>
                            <div class="nivel-section">
                                <div class="nivel-header">
                                    <h4 class="nivel-title"><?= htmlspecialchars($nivelData['nivel'] ?? '') ?></h4>
                                    <span class="nivel-total">
                                        <?= number_format((int) ($nivelData['total'] ?? 0), 0, ',', '.') ?> viandas
                                    </span>
                                </div>
                                <div class="cursos-grid">
                                    <?php if (!empty($nivelData['menus'])): ?>
                                        <?php foreach ($nivelData['menus'] as $menu): ?>
                                            <div class="card curso-card">
                                                <div class="curso-card-header">
                                                    <h4><?= htmlspecialchars($menu['nombre']) ?></h4>
                                                </div>
                                                <div class="curso-meta">
                                                    <span class="curso-icon">
                                                        <span class="material-icons">restaurant</span>
                                                    </span>
                                                    <span class="curso-count">
                                                        <?= number_format((int) ($menu['total'] ?? 0), 0, ',', '.') ?> menus
                                                    </span>
                                                </div>
                                                <?php if (!empty($menu['cursos'])): ?>
                                                    <ul class="curso-menus">
                                                        <?php foreach ($menu['cursos'] as $curso): ?>
                                                            <li>
                                                                <span><?= htmlspecialchars($curso['nombre'] ?? '') ?></span>
                                                                <span class="menu-count">
                                                                    <?= number_format((int) ($curso['cantidad'] ?? 0), 0, ',', '.') ?>
                                                                </span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <div class="curso-empty">Sin cursos para este menu.</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="curso-empty">Sin menus para este nivel.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="curso-empty">No hay cursos con pedidos para la fecha seleccionada.</div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="resumen-header">
                        <div>
                            <h3>Cuyo Placas</h3>
                        </div>
                    </div>
                </div>

            </section>

        </div>
    </div>

    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>
</body>

</html>
