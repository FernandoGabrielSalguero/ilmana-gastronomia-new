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

        .curso-download {
            color: #5b21b6;
        }

        .curso-card.is-capturing .curso-download {
            display: none;
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
            z-index: 200000 !important;
        }

        .curso-alumnos li:last-child {
            border-bottom: none;
        }

        .curso-empty {
            color: #9ca3af;
            font-size: 14px;
        }

        .alumnos-curso-card {
            margin-top: 20px;
            border-top: 1px dashed #e5e7eb;
            padding-top: 16px;
        }

        .alumnos-table-wrapper {
            max-height: 320px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        .alumnos-table {
            width: 100%;
            border-collapse: collapse;
        }

        .alumnos-table th,
        .alumnos-table td {
            padding: 8px 10px;
            font-size: 14px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .alumnos-table thead th {
            position: sticky;
            top: 0;
            background: #ffffff;
            z-index: 1;
        }

        .alumnos-select {
            width: 100%;
            min-width: 160px;
        }

        .alumnos-empty {
            padding: 12px;
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
                                        <button class="btn-icon curso-download" type="button"
                                            data-descargar-curso
                                            data-curso="<?= htmlspecialchars($curso['nombre']) ?>"
                                            data-curso-id="<?= htmlspecialchars((string) $curso['id']) ?>"
                                            data-fecha="<?= htmlspecialchars($fechaEntrega) ?>"
                                            data-tooltip="Descargar PDF">
                                            <span class="material-icons">download</span>
                                        </button>
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
                                                            title="<?= htmlspecialchars($alumno['motivo'] ?: 'Sin motivo') ?>">help_outline</span>
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

                    <div class="alumnos-curso-card">
                        <div class="resumen-header">
                            <div>
                                <h3 class="resumen-title">Actualizar cursos de alumnos</h3>
                                <p class="resumen-subtitle">Edita el curso y se actualiza en toda la pagina.</p>
                            </div>
                        </div>
                        <div class="alumnos-table-wrapper">
                            <table class="alumnos-table">
                                <thead>
                                    <tr>
                                        <th>ID alumno</th>
                                        <th>Nombre</th>
                                        <th>Curso actual</th>
                                        <th>Nuevo curso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($alumnosCursos)): ?>
                                        <?php foreach ($alumnosCursos as $alumno): ?>
                                            <?php
                                            $cursoActualNombre = trim((string) ($alumno['Curso'] ?? ''));
                                            if ($cursoActualNombre === '') {
                                                $cursoActualNombre = 'Sin curso asignado';
                                            }
                                            $cursoActualIdRaw = $alumno['Curso_Id'] ?? null;
                                            $cursoActualId = $cursoActualIdRaw ? (string) $cursoActualIdRaw : 'sin_curso';
                                            ?>
                                            <tr data-hijo-id="<?= (int) ($alumno['Id'] ?? 0) ?>">
                                                <td><?= (int) ($alumno['Id'] ?? 0) ?></td>
                                                <td><?= htmlspecialchars($alumno['Nombre'] ?? '') ?></td>
                                                <td class="curso-actual"><?= htmlspecialchars($cursoActualNombre) ?></td>
                                                <td>
                                                    <select class="alumnos-select" data-curso-select
                                                        data-hijo-id="<?= (int) ($alumno['Id'] ?? 0) ?>"
                                                        data-prev="<?= htmlspecialchars((string) $cursoActualId) ?>">
                                                        <option value="sin_curso" <?= $cursoActualId === 'sin_curso' ? 'selected' : '' ?>>
                                                            Sin curso asignado
                                                        </option>
                                                        <?php foreach ($cursosDisponibles as $curso): ?>
                                                            <option value="<?= (int) $curso['Id'] ?>"
                                                                <?= (string) $curso['Id'] === (string) $cursoActualId ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($curso['Nombre'] ?? '') ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="alumnos-empty">No hay alumnos disponibles.</td>
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
                if (!panelResumenFiltros.classList.contains('is-open')) {
                    const rect = toggleResumenFiltros.getBoundingClientRect();
                    const panelWidth = panelResumenFiltros.offsetWidth || 240;
                    const top = rect.bottom + 8;
                    const left = Math.max(16, rect.right - panelWidth);
                    panelResumenFiltros.style.top = `${top}px`;
                    panelResumenFiltros.style.left = `${left}px`;
                }
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
                        bindDescargaCursos(cursosGrid);
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

        const cargarLogoEmpresa = (() => {
            let cache = null;
            return async () => {
                if (cache) return cache;
                const logoUrl = '/assets/marca%20-%20120x118.webp';
                try {
                    const response = await fetch(logoUrl);
                    if (!response.ok) throw new Error('No se pudo cargar el logo.');
                    const blob = await response.blob();
                    const img = await createImageBitmap(blob);
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    cache = canvas.toDataURL('image/png');
                    return cache;
                } catch (err) {
                    console.error('Error cargando logo:', err);
                    return null;
                }
            };
        })();

        const descargarPdfCurso = async (boton) => {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('No se encontro la libreria para exportar el PDF.');
                return;
            }
            if (!boton) return;
            const cursoId = boton.dataset.cursoId || '';
            const fecha = boton.dataset.fecha || '';

            if (!cursoId) return;

            const params = new URLSearchParams({
                ajax: 'curso_detalle',
                curso_id: cursoId,
                fecha_entrega: fecha
            });

            let data;
            try {
                const res = await fetch(`representante_dashboard.php?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) {
                    const body = await res.text();
                    console.error('Error cargando detalle:', {
                        status: res.status,
                        statusText: res.statusText,
                        body
                    });
                    throw new Error('Error cargando detalle');
                }
                data = await res.json();
            } catch (err) {
                console.error('Error de conexion detalle curso:', err);
                return;
            }

            if (!data || data.ok !== true) {
                alert('No se pudo obtener el detalle del curso.');
                return;
            }

            const cursoNombre = data.curso || boton.dataset.curso || 'Curso';
            const colegioNombre = data.colegio || 'Colegio';
            const fechaEntrega = data.fecha || fecha;
            const viandas = Number(data.viandas || 0);
            const alumnos = Array.isArray(data.alumnos) ? data.alumnos : [];

            const pdf = new window.jspdf.jsPDF({
                orientation: 'portrait',
                unit: 'pt',
                format: 'a4'
            });

            const marginX = 36;
            let cursorY = 36;

            const logoDataUrl = await cargarLogoEmpresa();
            if (logoDataUrl) {
                const logoWidth = 90;
                const logoHeight = 88;
                pdf.addImage(logoDataUrl, 'PNG', marginX, cursorY, logoWidth, logoHeight);
            }

            const headerX = logoDataUrl ? marginX + 110 : marginX;
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(16);
            pdf.text(colegioNombre || 'Colegio', headerX, cursorY + 18);
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(12);
            pdf.text(`Curso: ${cursoNombre}`, headerX, cursorY + 38);
            pdf.text(`Fecha: ${fechaEntrega}`, headerX, cursorY + 56);
            pdf.setFontSize(12);
            pdf.text(`Viandas: ${viandas}`, headerX, cursorY + 74);

            cursorY += 110;

            const rows = alumnos.map((alumno) => ([
                alumno.nombre || '',
                alumno.estado || '',
                alumno.menu || '',
                alumno.preferencias || '',
                alumno.estado === 'Cancelado' ? (alumno.motivo || 'Sin motivo') : ''
            ]));

            if (typeof pdf.autoTable === 'function') {
                pdf.autoTable({
                    startY: cursorY,
                    head: [[
                        'Alumno',
                        'Estado',
                        'Pedido',
                        'Preferencias',
                        'Cancelacion'
                    ]],
                    body: rows,
                    styles: {
                        fontSize: 9,
                        cellPadding: 4,
                        overflow: 'linebreak'
                    },
                    headStyles: {
                        fillColor: [91, 33, 182]
                    },
                    columnStyles: {
                        0: { cellWidth: 140 },
                        1: { cellWidth: 70 },
                        2: { cellWidth: 140 },
                        3: { cellWidth: 140 },
                        4: { cellWidth: 120 }
                    }
                });
            } else {
                pdf.setFontSize(10);
                pdf.text('No se pudo generar la tabla.', marginX, cursorY);
            }

            const safeCurso = String(cursoNombre).replace(/[^\w\-]+/g, '_');
            const safeFecha = String(fechaEntrega).replace(/[^0-9\-]/g, '');
            const filename = `${safeCurso}_${safeFecha}.pdf`;
            pdf.save(filename);
        };

        const bindDescargaCursos = (scope) => {
            const botones = (scope || document).querySelectorAll('[data-descargar-curso]');
            botones.forEach((boton) => {
                if (boton.dataset.bound === '1') return;
                boton.dataset.bound = '1';
                boton.addEventListener('click', async () => {
                    boton.disabled = true;
                    await descargarPdfCurso(boton);
                    boton.disabled = false;
                });
            });
        };

        const bindCursoSelects = (scope) => {
            const selects = (scope || document).querySelectorAll('[data-curso-select]');
            selects.forEach((select) => {
                if (select.dataset.bound === '1') return;
                select.dataset.bound = '1';
                select.addEventListener('change', async () => {
                    const hijoId = select.dataset.hijoId || '';
                    const cursoId = select.value;
                    const prev = select.dataset.prev || '';
                    if (!hijoId) return;

                    const formData = new FormData();
                    formData.append('ajax', 'actualizar_curso');
                    formData.append('hijo_id', hijoId);
                    formData.append('curso_id', cursoId);

                    let data;
                    try {
                        const res = await fetch('representante_dashboard.php', {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        });
                        if (!res.ok) {
                            const body = await res.text();
                            console.error('Error actualizando curso:', {
                                status: res.status,
                                statusText: res.statusText,
                                body
                            });
                            throw new Error('Error actualizando curso');
                        }
                        data = await res.json();
                    } catch (err) {
                        console.error('Error de conexion actualizando curso:', err);
                        select.value = prev;
                        return;
                    }

                    if (!data || data.ok !== true) {
                        select.value = prev;
                        return;
                    }

                    select.dataset.prev = cursoId;
                    const row = select.closest('tr');
                    const cursoCell = row ? row.querySelector('.curso-actual') : null;
                    if (cursoCell) {
                        cursoCell.textContent = data.cursoNombre || 'Sin curso asignado';
                    }

                    if (fechaEntregaInput && fechaEntregaInput.value) {
                        cargarResumenAjax(fechaEntregaInput.value);
                    }
                });
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

        bindDescargaCursos(document);
        bindCursoSelects(document);

        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>
