<?php
// Mostrar errores en pantalla (útil en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión y proteger acceso
session_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controllers/representante_dashboardController.php';

// ⚠️ Expiración por inactividad (20 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: /index.php?expired=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time(); // Actualiza el tiempo de actividad

// 🚧 Protección de acceso general
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['nombre'])) {
    die("⚠️ Acceso denegado. No has iniciado sesión.");
}

// 🔐 Protección por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'representante') {
    die("🚫 Acceso restringido: esta pagina es solo para usuarios Representante.");
}

// Datos del usuario en sesión
$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
$correo = $_SESSION['correo'] ?? 'Sin correo';
$usuario = $_SESSION['usuario'] ?? 'Sin usuario';
$telefono = $_SESSION['telefono'] ?? 'Sin teléfono';


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
        .resumen-general {
            position: relative;
        }

        .resumen-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
            padding-right: 52px;
        }

        .resumen-title {
            margin: 0 0 4px;
        }

        .resumen-subtitle {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .resumen-total-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
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
            position: absolute;
            right: 0;
            top: 40px;
            min-width: 240px;
            background: #ffffff;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
            opacity: 0;
            pointer-events: none;
            transform: translateY(8px);
            transition: all 0.2s ease;
            z-index: 200;
        }

        .resumen-panel.is-open {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .resumen-total-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #ecfccb;
            color: #3f6212;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .resumen-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .resumen-count {
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
        }

        .resumen-total-box {
            margin-right: 48px;
        }

        .resumen-empty {
            color: #9ca3af;
            font-size: 14px;
        }

        .cursos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 16px;
        }

        .curso-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            min-height: 210px;
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
            padding: 2px 10px;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
            font-size: 12px;
            font-weight: 600;
        }

        .curso-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .curso-alumnos {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 220px;
            overflow-y: auto;
        }

        .curso-alumnos li {
            padding: 6px 0;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 14px;
            color: #374151;
        }

        .curso-alumnos li.is-cancelado {
            text-decoration: line-through;
            color: #9ca3af;
        }

        .cancelacion-icon {
            font-size: 18px;
            color: #dc2626;
            margin-left: 6px;
            vertical-align: middle;
        }

        .resumen-panel,
        [data-tooltip]::after {
            z-index: 300;
        }

        .curso-alumnos li:last-child {
            border-bottom: none;
        }

        .curso-empty {
            color: #9ca3af;
            font-size: 14px;
        }

        @media (max-width: 640px) {
            .resumen-header {
                flex-direction: column;
                align-items: stretch;
            }

            .resumen-panel {
                right: auto;
                left: 0;
                width: 100%;
            }
        }
    </style>
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
                    <li onclick="location.href='representante_dashboard.php'">
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
                    <p>Resumen de pedidos de viandas para el dia.</p>
                </div>

                <div class="card resumen-general">
                    <div class="resumen-header">
                        <div>
                            <h3 class="resumen-title">Resumen del dia</h3>
                            <p class="resumen-subtitle" id="resumen-fecha-texto">
                                Fecha: <?= htmlspecialchars(date('d/m/Y', strtotime($fechaEntrega))) ?>
                            </p>
                        </div>
                        <div class="resumen-total-box">
                            <div class="resumen-total-icon">
                                <span class="material-icons">receipt_long</span>
                            </div>
                            <div>
                                <div class="resumen-label">Pedidos del dia</div>
                                <div class="resumen-count" id="resumen-total-count">
                                    <?= number_format($totalPedidosDia, 0, ',', '.') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="resumen-actions">
                        <button class="btn-icon" id="toggleResumenFiltros" type="button" data-tooltip="Filtros">
                            <span class="material-icons">tune</span>
                        </button>
                        <div class="resumen-panel" id="panelResumenFiltros">
                            <form class="form-modern" method="get" action="representante_dashboard.php" id="resumen-filtros-form">
                                <div class="input-group">
                                    <label>Fecha de entrega</label>
                                    <div class="input-icon">
                                        <span class="material-icons">event</span>
                                        <input type="date" name="fecha_entrega" id="fecha-entrega-input"
                                            value="<?= htmlspecialchars($fechaEntrega) ?>">
                                    </div>
                                </div>
                                <div class="form-buttons">
                                    <button class="btn btn-aceptar" type="submit">Aplicar</button>
                                    <a class="btn btn-cancelar" href="representante_dashboard.php">Limpiar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="cursos-grid" id="cursos-grid">
                        <?php if (!empty($cursosTarjetas)): ?>
                            <?php foreach ($cursosTarjetas as $curso): ?>
                                <div class="card curso-card">
                                    <div class="curso-card-header">
                                        <h4><?= htmlspecialchars($curso['nombre']) ?></h4>
                                    </div>
                                    <div class="curso-meta">
                                        <span class="curso-icon">
                                            <span class="material-icons">restaurant</span>
                                        </span>
                                        <span class="curso-count"><?= count($curso['alumnos']) ?> alumnos</span>
                                    </div>
                                    <?php if (!empty($curso['alumnos'])): ?>
                                        <ul class="curso-alumnos">
                                            <?php foreach ($curso['alumnos'] as $alumno): ?>
                                                <li class="<?= !empty($alumno['cancelado']) ? 'is-cancelado' : '' ?>">
                                                    <?= htmlspecialchars($alumno['nombre']) ?>
                                                    <?php if (!empty($alumno['cancelado'])): ?>
                                                        <span class="cancelacion-icon material-icons"
                                                            data-tooltip="<?= htmlspecialchars($alumno['motivo'] ?: 'Sin motivo') ?>">help_outline</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="curso-empty">Sin alumnos con pedidos.</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="curso-empty">No hay cursos con pedidos para el dia.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

        </div>
    </div>
    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        const toggleResumenFiltros = document.getElementById('toggleResumenFiltros');
        const panelResumenFiltros = document.getElementById('panelResumenFiltros');
        const resumenForm = document.getElementById('resumen-filtros-form');
        const fechaEntregaInput = document.getElementById('fecha-entrega-input');
        const cursosGrid = document.getElementById('cursos-grid');
        const resumenTotal = document.getElementById('resumen-total-count');
        const resumenFecha = document.getElementById('resumen-fecha-texto');

        if (toggleResumenFiltros && panelResumenFiltros) {
            toggleResumenFiltros.addEventListener('click', () => {
                panelResumenFiltros.classList.toggle('is-open');
            });

            document.addEventListener('click', (event) => {
                if (!panelResumenFiltros.contains(event.target) && !toggleResumenFiltros.contains(event.target)) {
                    panelResumenFiltros.classList.remove('is-open');
                }
            });
        }

        const cargarResumenAjax = (fecha) => {
            if (!fecha) return;
            const params = new URLSearchParams({
                ajax: '1',
                fecha_entrega: fecha
            });

            fetch(`representante_dashboard.php?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(async (res) => {
                    if (!res.ok) {
                        const body = await res.text();
                        console.error('Error cargando resumen:', {
                            status: res.status,
                            statusText: res.statusText,
                            body
                        });
                        throw new Error('Error cargando resumen');
                    }
                    return res.json();
                })
                .then((data) => {
                    if (cursosGrid && typeof data.cursosGridHtml === 'string') {
                        cursosGrid.innerHTML = data.cursosGridHtml;
                    }
                    if (resumenTotal) {
                        const total = Number(data.totalPedidos || 0);
                        resumenTotal.textContent = total.toLocaleString('es-AR');
                    }
                    if (resumenFecha && typeof data.fechaTexto === 'string') {
                        resumenFecha.textContent = `Fecha: ${data.fechaTexto}`;
                    }
                    if (panelResumenFiltros) {
                        panelResumenFiltros.classList.remove('is-open');
                    }
                })
                .catch((err) => {
                    console.error('Error de conexion cargando resumen:', err);
                });
        };

        if (resumenForm) {
            resumenForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const fecha = fechaEntregaInput ? fechaEntregaInput.value : '';
                cargarResumenAjax(fecha);
            });
        }

        if (fechaEntregaInput) {
            fechaEntregaInput.addEventListener('change', () => {
                cargarResumenAjax(fechaEntregaInput.value);
            });
        }

        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>
