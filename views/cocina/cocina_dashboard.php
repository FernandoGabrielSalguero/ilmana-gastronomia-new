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
    $nivelesOrden = ['Inicial', 'Primaria', 'Secundaria'];
    $menusResumenOrden = $menusResumenList;
    usort($menusResumenOrden, function ($a, $b) {
        return (int) ($b['total'] ?? 0) <=> (int) ($a['total'] ?? 0);
    });

    $preferenciasResumen = [];
    foreach ($nivelesOrden as $nivel) {
        $preferenciasResumen[$nivel] = [
            'total_pref' => 0,
            'sin' => 0,
            'prefs' => []
        ];
    }
    $totalConPreferencias = 0;
    $totalSinPreferencias = 0;

    foreach ($menusResumenList as $menuResumen) {
        foreach ($nivelesOrden as $nivel) {
            $prefInfo = $menuResumen['niveles_pref_counts'][$nivel] ?? null;
            if (!$prefInfo) {
                continue;
            }
            $sin = (int) ($prefInfo['sin'] ?? 0);
            $totalSinPreferencias += $sin;
            $preferenciasResumen[$nivel]['sin'] += $sin;

            if (!empty($prefInfo['prefs'])) {
                foreach ($prefInfo['prefs'] as $prefNombre => $prefCantidad) {
                    $prefCantidad = (int) $prefCantidad;
                    if (!isset($preferenciasResumen[$nivel]['prefs'][$prefNombre])) {
                        $preferenciasResumen[$nivel]['prefs'][$prefNombre] = 0;
                    }
                    $preferenciasResumen[$nivel]['prefs'][$prefNombre] += $prefCantidad;
                    $preferenciasResumen[$nivel]['total_pref'] += $prefCantidad;
                    $totalConPreferencias += $prefCantidad;
                }
            }
        }
    }

    foreach ($nivelesOrden as $nivel) {
        if (!empty($preferenciasResumen[$nivel]['prefs'])) {
            arsort($preferenciasResumen[$nivel]['prefs']);
        }
    }

    $menuPrefsResumen = [];
    $menuPrefsPorNivel = [];
    foreach ($nivelesOrden as $nivel) {
        $menuPrefsPorNivel[$nivel] = [];
    }

    foreach ($menusResumenList as $menuResumen) {
        $menuNombre = (string) ($menuResumen['nombre'] ?? '');
        if ($menuNombre === '') {
            $menuNombre = 'Menu sin nombre';
        }

        if (!isset($menuPrefsResumen[$menuNombre])) {
            $menuPrefsResumen[$menuNombre] = [
                'sin' => 0,
                'prefs' => [],
                'total_pref' => 0,
                'has_pref' => false
            ];
        }

        foreach ($nivelesOrden as $nivel) {
            $prefInfo = $menuResumen['niveles_pref_counts'][$nivel] ?? null;
            if (!$prefInfo) {
                continue;
            }
            if (!isset($menuPrefsPorNivel[$nivel][$menuNombre])) {
                $menuPrefsPorNivel[$nivel][$menuNombre] = [
                    'sin' => 0,
                    'prefs' => [],
                    'total_pref' => 0,
                    'has_pref' => false
                ];
            }

            $sin = (int) ($prefInfo['sin'] ?? 0);
            $menuPrefsResumen[$menuNombre]['sin'] += $sin;
            $menuPrefsPorNivel[$nivel][$menuNombre]['sin'] += $sin;

            if (!empty($prefInfo['prefs'])) {
                foreach ($prefInfo['prefs'] as $prefNombre => $prefCantidad) {
                    $prefCantidad = (int) $prefCantidad;
                    if (!isset($menuPrefsResumen[$menuNombre]['prefs'][$prefNombre])) {
                        $menuPrefsResumen[$menuNombre]['prefs'][$prefNombre] = 0;
                    }
                    $menuPrefsResumen[$menuNombre]['prefs'][$prefNombre] += $prefCantidad;
                    $menuPrefsResumen[$menuNombre]['total_pref'] += $prefCantidad;

                    if (!isset($menuPrefsPorNivel[$nivel][$menuNombre]['prefs'][$prefNombre])) {
                        $menuPrefsPorNivel[$nivel][$menuNombre]['prefs'][$prefNombre] = 0;
                    }
                    $menuPrefsPorNivel[$nivel][$menuNombre]['prefs'][$prefNombre] += $prefCantidad;
                    $menuPrefsPorNivel[$nivel][$menuNombre]['total_pref'] += $prefCantidad;
                }
            }
        }

        if (!empty($menuPrefsResumen[$menuNombre]['prefs'])) {
            arsort($menuPrefsResumen[$menuNombre]['prefs']);
            $menuPrefsResumen[$menuNombre]['has_pref'] = true;
        }
    }

    foreach ($nivelesOrden as $nivel) {
        if (empty($menuPrefsPorNivel[$nivel])) {
            continue;
        }
        foreach ($menuPrefsPorNivel[$nivel] as $menuNombre => $prefData) {
            if (!empty($prefData['prefs'])) {
                arsort($menuPrefsPorNivel[$nivel][$menuNombre]['prefs']);
                $menuPrefsPorNivel[$nivel][$menuNombre]['has_pref'] = true;
            }
        }
    }
    ?>
    <div class="resumen-body">
        <div class="resumen-lateral">
            <div class="card curso-card resumen-total-card is-primary">
                <div class="curso-card-header">
                    <button class="btn-icon resumen-icon-button" type="button" data-resumen-modal="open"
                        data-tooltip="Ver resumen">
                        <span class="material-icons">summarize</span>
                    </button>
                    <h4>Resumen de pedidos del día</h4>
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
                        foreach ($nivelesOrden as $nivelNombre):
                            $tituloNivel = $nivelNombre === 'Inicial' ? 'Nivel inicial' : $nivelNombre;
                            ?>
                                <div class="resumen-section">
                                    <div class="resumen-section-title"><?= htmlspecialchars($tituloNivel) ?></div>
                                    <?php foreach ($menusResumenList as $menuResumen): ?>
                                        <?php $nivelCantidad = (int) ($menuResumen['niveles'][$nivelNombre] ?? 0); ?>
                                        <?php if ($nivelCantidad > 0): ?>
                                            <?php $prefInfo = $menuResumen['niveles_pref_counts'][$nivelNombre] ?? null; ?>
                                            <div class="resumen-metric">
                                                <span class="resumen-metric-label"><?= htmlspecialchars($menuResumen['nombre']) ?></span>
                                                <span class="resumen-metric-value">
                                                    <?= number_format($nivelCantidad, 0, ',', '.') ?>
                                                </span>
                                            </div>
                                            <?php if ($prefInfo && !empty($prefInfo['has_pref'])): ?>
                                                <div class="resumen-pref-list">
                                                    <div class="resumen-pref-item is-none">
                                                        <span>Sin preferencias</span>
                                                        <span><?= number_format((int) ($prefInfo['sin'] ?? 0), 0, ',', '.') ?></span>
                                                    </div>
                                                    <?php if (!empty($prefInfo['prefs'])): ?>
                                                        <?php foreach ($prefInfo['prefs'] as $prefNombre => $prefCantidad): ?>
                                                            <div class="resumen-pref-item is-pref">
                                                                <span><?= htmlspecialchars($prefNombre) ?></span>
                                                                <span><?= number_format((int) $prefCantidad, 0, ',', '.') ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
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

    <div class="resumen-modal" id="resumenModal" aria-hidden="true">
        <div class="resumen-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="resumenModalTitle">
            <div class="resumen-modal-header">
                <div>
                    <h3 id="resumenModalTitle">Resumen claro del dia</h3>
                    <p class="resumen-modal-subtitle">Guia rapida para cocinar y armar cajas.</p>
                </div>
                <button class="btn-icon" type="button" data-resumen-modal="close" aria-label="Cerrar">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div class="resumen-modal-body">
                <div class="resumen-modal-grid">
                    <div class="resumen-modal-card is-total">
                        <div class="resumen-modal-label">Total de menus a preparar</div>
                        <div class="resumen-modal-number"><?= number_format($totalPedidosDia, 0, ',', '.') ?></div>
                    </div>
                    <div class="resumen-modal-card is-pref">
                        <div class="resumen-modal-label">Menus con preferencias</div>
                        <div class="resumen-modal-number"><?= number_format($totalConPreferencias, 0, ',', '.') ?></div>
                    </div>
                    <div class="resumen-modal-card is-plain">
                        <div class="resumen-modal-label">Menus sin preferencias</div>
                        <div class="resumen-modal-number"><?= number_format($totalSinPreferencias, 0, ',', '.') ?></div>
                    </div>
                </div>

                <div class="resumen-modal-section">
                    <h4>Menus por tipo</h4>
                    <?php if (!empty($menusResumenOrden)): ?>
                        <ul class="resumen-modal-list">
                            <?php foreach ($menusResumenOrden as $menuResumen): ?>
                                <?php
                                $menuNombre = (string) ($menuResumen['nombre'] ?? '');
                                if ($menuNombre === '') {
                                    $menuNombre = 'Menu sin nombre';
                                }
                                $menuPrefData = $menuPrefsResumen[$menuNombre] ?? null;
                                ?>
                                <li class="resumen-modal-menu">
                                    <div class="resumen-modal-menu-row">
                                        <span><?= htmlspecialchars($menuNombre) ?></span>
                                        <span class="resumen-modal-pill">
                                            <?= number_format((int) ($menuResumen['total'] ?? 0), 0, ',', '.') ?>
                                        </span>
                                    </div>
                                    <?php if ($menuPrefData && !empty($menuPrefData['has_pref'])): ?>
                                        <ul class="resumen-modal-pref-list">
                                            <li class="is-none">
                                                <span>Sin preferencias</span>
                                                <span class="resumen-modal-pill is-none">
                                                    <?= number_format((int) ($menuPrefData['sin'] ?? 0), 0, ',', '.') ?>
                                                </span>
                                            </li>
                                            <?php foreach ($menuPrefData['prefs'] as $prefNombre => $prefCantidad): ?>
                                                <li class="is-pref">
                                                    <span><?= htmlspecialchars($prefNombre) ?></span>
                                                    <span class="resumen-modal-pill is-danger">
                                                        <?= number_format((int) $prefCantidad, 0, ',', '.') ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="curso-empty">Sin menus registrados.</div>
                    <?php endif; ?>
                </div>

                <div class="resumen-modal-section">
                    <h4>Preferencias por nivel</h4>
                    <div class="resumen-modal-niveles">
                        <?php foreach ($nivelesOrden as $nivelNombre): ?>
                            <?php
                            $nivelTitulo = $nivelNombre === 'Inicial' ? 'Nivel inicial' : $nivelNombre;
                            $nivelData = $preferenciasResumen[$nivelNombre];
                            $nivelMenusPref = $menuPrefsPorNivel[$nivelNombre] ?? [];
                            ?>
                            <div class="resumen-modal-nivel">
                                <div class="resumen-modal-nivel-header">
                                    <span><?= htmlspecialchars($nivelTitulo) ?></span>
                                    <span class="resumen-modal-pill is-strong">
                                        <?= number_format((int) ($nivelData['total_pref'] ?? 0), 0, ',', '.') ?> con preferencias
                                    </span>
                                </div>
                                <?php
                                $nivelTienePreferencias = false;
                                foreach ($nivelMenusPref as $menuNombre => $prefData) {
                                    if (!empty($prefData['has_pref'])) {
                                        $nivelTienePreferencias = true;
                                        break;
                                    }
                                }
                                ?>
                                <?php if ($nivelTienePreferencias): ?>
                                    <div class="resumen-modal-menu-list">
                                        <?php foreach ($nivelMenusPref as $menuNombre => $prefData): ?>
                                            <?php if (empty($prefData['has_pref'])): ?>
                                                <?php continue; ?>
                                            <?php endif; ?>
                                            <div class="resumen-modal-menu">
                                                <div class="resumen-modal-menu-row">
                                                    <span><?= htmlspecialchars($menuNombre) ?></span>
                                                    <span class="resumen-modal-pill is-danger">
                                                        <?= number_format((int) ($prefData['total_pref'] ?? 0), 0, ',', '.') ?>
                                                    </span>
                                                </div>
                                                <ul class="resumen-modal-pref-list">
                                                    <li class="is-none">
                                                        <span>Sin preferencias</span>
                                                        <span class="resumen-modal-pill is-none">
                                                            <?= number_format((int) ($prefData['sin'] ?? 0), 0, ',', '.') ?>
                                                        </span>
                                                    </li>
                                                    <?php foreach ($prefData['prefs'] as $prefNombre => $prefCantidad): ?>
                                                        <li class="is-pref">
                                                            <span><?= htmlspecialchars($prefNombre) ?></span>
                                                            <span class="resumen-modal-pill is-danger">
                                                                <?= number_format((int) $prefCantidad, 0, ',', '.') ?>
                                                            </span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="curso-empty">Sin preferencias registradas.</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
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

        .resumen-icon-button {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: #ffffff;
            color: #1e3a8a;
            box-shadow: 0 10px 20px rgba(30, 64, 175, 0.15);
        }

        .resumen-icon-button .material-icons {
            font-size: 20px;
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

        .resumen-modal {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.55);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 200000 !important;
        }

        .resumen-modal.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .resumen-modal-dialog {
            width: min(980px, 100%);
            max-height: 90vh;
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            overflow: auto;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.3);
        }

        .resumen-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }

        .resumen-modal-header h3 {
            margin: 0 0 4px;
        }

        .resumen-modal-subtitle {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
        }

        .resumen-modal-body {
            display: grid;
            gap: 18px;
        }

        .resumen-modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .resumen-modal-card {
            border-radius: 16px;
            padding: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            display: grid;
            gap: 8px;
        }

        .resumen-modal-card.is-total {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .resumen-modal-card.is-pref {
            background: #fef9c3;
            border-color: #fde68a;
        }

        .resumen-modal-card.is-plain {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .resumen-modal-label {
            font-size: 13px;
            font-weight: 700;
            color: #1f2937;
        }

        .resumen-modal-number {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
        }

        .resumen-modal-section h4 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .resumen-modal-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 6px;
        }

        .resumen-modal-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .resumen-modal-menu {
            display: grid;
            gap: 6px;
        }

        .resumen-modal-menu-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .resumen-modal-menu-list {
            display: grid;
            gap: 12px;
        }

        .resumen-modal-pref-list {
            list-style: none;
            margin: 0;
            padding: 0 0 0 14px;
            display: grid;
            gap: 6px;
            color: #b91c1c;
        }

        .resumen-modal-pref-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .resumen-modal-pref-list li.is-none {
            color: #15803d;
        }

        .resumen-modal-pref-list li.is-pref {
            color: #b91c1c;
        }

        .resumen-modal-list.is-compact li {
            font-size: 13px;
        }

        .resumen-modal-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            border-radius: 999px;
            background: #dbeafe;
            font-weight: 700;
            font-size: 12px;
            color: #1e3a8a;
            white-space: nowrap;
        }

        .resumen-modal-pill.is-strong {
            background: #1d4ed8;
            color: #ffffff;
        }

        .resumen-modal-pill.is-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .resumen-modal-pill.is-none {
            background: #dcfce7;
            color: #166534;
        }

        .resumen-modal-niveles {
            display: grid;
            gap: 12px;
        }

        .resumen-modal-nivel {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 12px;
            background: #ffffff;
            display: grid;
            gap: 10px;
        }

        .resumen-modal-nivel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-weight: 700;
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

        .curso-card-header h4 {
            order: 1;
        }

        .resumen-icon-button {
            order: 2;
            margin-left: auto;
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
            grid-template-columns: 475px 1fr;
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

        .resumen-pref-list {
            display: grid;
            gap: 4px;
            margin: 4px 0 6px;
            padding-left: 10px;
        }

        .resumen-pref-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .resumen-pref-item.is-none {
            color: #16a34a;
        }

        .resumen-pref-item.is-pref {
            color: #dc2626;
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

        const getResumenModal = () => document.getElementById('resumenModal');

        const openResumenModal = () => {
            const modal = getResumenModal();
            if (!modal) return;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        };

        const closeResumenModal = () => {
            const modal = getResumenModal();
            if (!modal) return;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        };

        document.addEventListener('click', (event) => {
            const openTrigger = event.target.closest('[data-resumen-modal="open"]');
            if (openTrigger) {
                event.preventDefault();
                openResumenModal();
                return;
            }

            const closeTrigger = event.target.closest('[data-resumen-modal="close"]');
            if (closeTrigger) {
                event.preventDefault();
                closeResumenModal();
                return;
            }

            const modal = getResumenModal();
            if (modal && event.target === modal) {
                closeResumenModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeResumenModal();
            }
        });
    </script>
</body>

</html>
