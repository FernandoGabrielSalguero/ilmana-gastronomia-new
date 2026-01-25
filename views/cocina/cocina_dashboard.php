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

function renderViandasResumenBody($nivelesList, $totalPedidosDia, $totalesPorNivel, $menusResumenList)
{
    ?>
    <div class="resumen-body">
        <div class="resumen-lateral">
            <div class="card curso-card resumen-total-card is-primary">
                <div class="curso-card-header">
                    <h4>Pedidos del dia</h4>
                </div>
                <div class="curso-meta">
                    <span class="curso-count is-total">
                        Total <?= number_format($totalPedidosDia, 0, ',', '.') ?>
                    </span>
                </div>
                <div class="resumen-metrics">
                    <?php if (!empty($menusResumenList)): ?>
                        <?php foreach ($menusResumenList as $menuResumen): ?>
                            <div class="resumen-metric resumen-metric-highlight">
                                <span>Total <?= htmlspecialchars($menuResumen['nombre']) ?></span>
                                <span><?= number_format((int) ($menuResumen['total'] ?? 0), 0, ',', '.') ?></span>
                            </div>
                        <?php endforeach; ?>

                        <?php
                        $nivelesOrden = ['Inicial', 'Primaria', 'Secundaria'];
                        foreach ($nivelesOrden as $nivelNombre):
                            $tituloNivel = $nivelNombre === 'Inicial' ? 'Nivel inicial' : $nivelNombre;
                            ?>
                                <div class="resumen-section">
                                    <div class="resumen-section-title"><?= htmlspecialchars($tituloNivel) ?></div>
                                    <?php foreach ($menusResumenList as $menuResumen): ?>
                                        <?php $nivelCantidad = (int) ($menuResumen['niveles'][$nivelNombre] ?? 0); ?>
                                        <?php if ($nivelCantidad > 0): ?>
                                            <?php $prefList = $menuResumen['niveles_prefs'][$nivelNombre] ?? []; ?>
                                            <div class="resumen-metric">
                                                <span class="resumen-metric-label">
                                                    <?= htmlspecialchars($menuResumen['nombre']) ?>
                                                    <?php if (!empty($prefList)): ?>
                                                        <span class="resumen-pref"><?= htmlspecialchars(implode(', ', $prefList)) ?></span>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="resumen-metric-value">
                                                    <?= number_format($nivelCantidad, 0, ',', '.') ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="resumen-metric resumen-metric-highlight">
                            <span>Sin menus</span>
                            <span>0</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div>
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
    </div>
    <?php
}

$fechaEntregaTexto = date('d/m/Y', strtotime($fechaEntrega));
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    ob_start();
    renderViandasResumenBody($nivelesList, $totalPedidosDia, $totalesPorNivel, $menusResumenList);
    $bodyHtml = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'bodyHtml' => $bodyHtml,
        'fechaTexto' => $fechaEntregaTexto
    ]);
    exit;
}
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

        .resumen-actions {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .resumen-panel {
            position: fixed;
            min-width: 240px;
            background: #ffffff;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
            opacity: 0;
            pointer-events: none;
            transform: translateY(8px);
            transition: all 0.2s ease;
            z-index: 200000 !important;
        }

        .resumen-panel.is-open {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .resumen-panel,
        [data-tooltip]::after {
            z-index: 200000 !important;
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

        .curso-count.is-total {
            font-size: 18px;
            padding: 8px 16px;
            background: #1d4ed8;
            color: #ffffff;
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

        .resumen-total-center {
            align-self: center;
            text-align: center;
            width: 100%;
        }

        .resumen-body {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 16px;
            align-items: stretch;
        }

        .resumen-lateral {
            height: 100%;
        }

        .resumen-lateral .resumen-total-card {
            height: 100%;
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

        .resumen-metric-highlight {
            font-weight: 700;
            color: #ffffff;
            background: #2563eb;
            border-radius: 999px;
            padding: 4px 10px;
        }

        .resumen-section {
            margin-top: 8px;
            display: grid;
            gap: 6px;
        }

        .resumen-section-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #ffffff;
            background: #1e40af;
            border-radius: 999px;
            padding: 4px 10px;
            display: inline-flex;
            align-self: flex-start;
        }

        .resumen-metric-label {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 11px;
            color: #0f172a;
            opacity: 1;
        }

        .resumen-pref {
            font-size: 12px;
            font-weight: 700;
            color: #dc2626;
            text-transform: none;
            letter-spacing: 0;
            margin-left: 6px;
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

        @media (max-width: 960px) {
            .resumen-body {
                grid-template-columns: 1fr;
            }
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
                            <p class="resumen-subtitle" id="viandas-fecha-texto">
                                Fecha: <?= htmlspecialchars($fechaEntregaTexto) ?>
                            </p>
                        </div>
                        <div class="resumen-actions">
                            <button class="btn-icon" id="toggleViandasFiltros" type="button" data-tooltip="Filtros">
                                <span class="material-icons">tune</span>
                            </button>
                            <div class="resumen-panel" id="panelViandasFiltros">
                                <form class="form-modern" method="get" action="cocina_dashboard.php" id="viandas-filtros-form">
                                    <div class="input-group">
                                        <label>Fecha de entrega</label>
                                        <div class="input-icon">
                                            <span class="material-icons">event</span>
                                            <input type="date" name="fecha_entrega" id="viandas-fecha-input"
                                                value="<?= htmlspecialchars($fechaEntrega) ?>">
                                        </div>
                                    </div>
                                    <div class="form-buttons">
                                        <button class="btn btn-aceptar" type="submit">Aplicar</button>
                                        <a class="btn btn-cancelar" href="cocina_dashboard.php">Limpiar</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div id="viandas-resumen-body">
                        <?php renderViandasResumenBody($nivelesList, $totalPedidosDia, $totalesPorNivel, $menusResumenList); ?>
                    </div>
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
    <script>
        const toggleViandasFiltros = document.getElementById('toggleViandasFiltros');
        const panelViandasFiltros = document.getElementById('panelViandasFiltros');
        const viandasForm = document.getElementById('viandas-filtros-form');
        const viandasFechaInput = document.getElementById('viandas-fecha-input');
        const viandasBody = document.getElementById('viandas-resumen-body');
        const viandasFechaTexto = document.getElementById('viandas-fecha-texto');

        const togglePanelViandas = () => {
            if (!panelViandasFiltros || !toggleViandasFiltros) return;
            if (!panelViandasFiltros.classList.contains('is-open')) {
                const rect = toggleViandasFiltros.getBoundingClientRect();
                const panelWidth = panelViandasFiltros.offsetWidth || 240;
                const top = rect.bottom + 8;
                const left = Math.max(16, rect.right - panelWidth);
                panelViandasFiltros.style.top = `${top}px`;
                panelViandasFiltros.style.left = `${left}px`;
            }
            panelViandasFiltros.classList.toggle('is-open');
        };

        if (toggleViandasFiltros && panelViandasFiltros) {
            toggleViandasFiltros.addEventListener('click', togglePanelViandas);
            document.addEventListener('click', (event) => {
                if (!panelViandasFiltros.contains(event.target) && !toggleViandasFiltros.contains(event.target)) {
                    panelViandasFiltros.classList.remove('is-open');
                }
            });
        }

        const cargarViandasAjax = (fecha) => {
            if (!fecha) return;
            const params = new URLSearchParams({
                ajax: '1',
                fecha_entrega: fecha
            });

            fetch(`cocina_dashboard.php?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(async (res) => {
                    if (!res.ok) {
                        const body = await res.text();
                        console.error('Error cargando viandas:', {
                            status: res.status,
                            statusText: res.statusText,
                            body
                        });
                        throw new Error('Error cargando viandas');
                    }
                    return res.json();
                })
                .then((data) => {
                    if (!data || data.ok !== true) {
                        throw new Error('Respuesta invalida');
                    }
                    if (viandasBody && typeof data.bodyHtml === 'string') {
                        viandasBody.innerHTML = data.bodyHtml;
                    }
                    if (viandasFechaTexto && typeof data.fechaTexto === 'string') {
                        viandasFechaTexto.textContent = `Fecha: ${data.fechaTexto}`;
                    }
                    if (panelViandasFiltros) {
                        panelViandasFiltros.classList.remove('is-open');
                    }
                })
                .catch((err) => {
                    console.error('Error cargando viandas:', err);
                });
        };

        if (viandasForm) {
            viandasForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const fecha = viandasFechaInput ? viandasFechaInput.value : '';
                cargarViandasAjax(fecha);
            });
        }
    </script>
</body>

</html>
