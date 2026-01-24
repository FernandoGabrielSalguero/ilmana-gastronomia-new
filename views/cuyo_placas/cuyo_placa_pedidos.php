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
                <div class="navbar-title">Pedidos</div>
            </header>

            <!-- 📦 CONTENIDO -->
            <section class="content">
                <div class="card">
                    <h2>Hola <?= htmlspecialchars($nombre) ?></h2>
                    <p>Selecciona la fecha para cargar pedidos.</p>
                </div>

                <div class="card">
                    <div class="pedido-header">
                        <div>
                            <h3>Pedidos de Viandas - Cuyo Placa</h3>
                            <p class="pedido-subtitle">
                                Fecha seleccionada: <?= htmlspecialchars($fechaSeleccionada ?: 'Sin fecha') ?>
                                <?= $pedidoExistente ? "(Pedido #" . htmlspecialchars($pedidoExistente['id']) . ")" : '' ?>
                            </p>
                        </div>
                    </div>
                    <div class="pedido-semana-label">Semana</div>
                    <div class="pedido-semana">
                        <a class="week-nav" href="cuyo_placa_pedidos.php?fecha=<?= htmlspecialchars($semanaAnterior->format('Y-m-d')) ?>">
                            <span class="material-icons">chevron_left</span>
                        </a>
                        <div class="week-grid">
                            <?php foreach ($semanaDias as $dia): ?>
                                <?php
                                $pedido = $dia['pedido'] ?? null;
                                $estadoClase = '';
                                if ($pedido) {
                                    $estadoClase = $dia['puedeModificar'] ? 'day-modificable' : 'day-bloqueado';
                                }
                                ?>
                                <a class="week-day <?= $estadoClase ?> <?= $dia['seleccionada'] ? 'day-seleccionada' : '' ?>"
                                    href="cuyo_placa_pedidos.php?fecha=<?= htmlspecialchars($dia['fecha']) ?>">
                                    <span class="day-name"><?= htmlspecialchars($dia['label']) ?></span>
                                    <span class="day-date"><?= htmlspecialchars($dia['fecha']) ?></span>
                                    <?php if ($pedido): ?>
                                        <span class="day-remito">Remito Digital #<?= htmlspecialchars($pedido['id']) ?></span>
                                    <?php else: ?>
                                        <span class="day-remito day-vacio">Sin pedido</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <a class="week-nav" href="cuyo_placa_pedidos.php?fecha=<?= htmlspecialchars($semanaSiguiente->format('Y-m-d')) ?>">
                            <span class="material-icons">chevron_right</span>
                        </a>
                    </div>

                    <?php if ($fechaSeleccionada): ?>
                        <form method="post" action="cuyo_placa_pedidos.php">
                            <input type="hidden" name="fecha" value="<?= htmlspecialchars($fechaSeleccionada) ?>">

                            <div class="pedido-table-wrapper">
                                <table class="pedido-table">
                                    <thead>
                                        <tr>
                                            <th rowspan="2">Planta</th>
                                            <?php foreach ($menuGrupos as $grupo): ?>
                                                <th colspan="<?= count($grupo['menus']) ?>"><?= htmlspecialchars($grupo['label']) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                        <tr>
                                            <?php foreach ($menuGrupos as $grupo): ?>
                                                <?php foreach ($grupo['menus'] as $menu): ?>
                                                    <th><?= htmlspecialchars($menu['label']) ?></th>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($plantas as $planta): ?>
                                            <tr>
                                                <td class="pedido-planta"><?= htmlspecialchars($planta['label']) ?></td>
                                                <?php foreach ($menuGrupos as $grupo): ?>
                                                    <?php foreach ($grupo['menus'] as $menu): ?>
                                                        <?php
                                                        $valor = $detalleMap[$planta['key']][$menu['key']] ?? 0;
                                                        ?>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                name="pedido[<?= htmlspecialchars($planta['key']) ?>][<?= htmlspecialchars($menu['key']) ?>]"
                                                                value="<?= htmlspecialchars((string) $valor) ?>"
                                                                min="0"
                                                                step="1"
                                                                class="pedido-input"
                                                                <?= $bloqueoEdicion ? 'disabled' : '' ?>
                                                            />
                                                        </td>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-buttons pedido-actions">
                                <button class="btn btn-aceptar" type="submit" <?= $bloqueoEdicion ? 'disabled' : '' ?>>
                                    Guardar pedidos
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>

    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>
    <script>
        function showAlertSafe(type, message, options = {}) {
            if (typeof window.showAlert === 'function') {
                try {
                    if (window.showAlert.length <= 1) {
                        window.showAlert(Object.assign({ type, message }, options));
                    } else {
                        window.showAlert(type, message, options);
                    }
                    return;
                } catch (err) {
                    console.warn('showAlert failed, falling back to alert.', err);
                }
            }
            alert(message);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const alerta = <?= json_encode($alerta) ?>;
            if (alerta && alerta.mensaje) {
                showAlertSafe(alerta.tipo || 'info', alerta.mensaje);
            }
        });
    </script>

    <style>
        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .pedido-subtitle {
            margin-top: 4px;
            color: #6b7280;
        }

        .pedido-semana-label {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 6px;
        }

        .pedido-semana {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .week-nav {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #0f172a;
            background: #ffffff;
            text-decoration: none;
        }

        .week-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(120px, 1fr));
            gap: 10px;
        }

        .week-day {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 12px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: #0f172a;
            background: #ffffff;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            min-height: 110px;
        }

        .week-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .week-day.day-seleccionada {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2);
        }

        .week-day.day-modificable {
            background: #fff7ed;
            border-color: #fdba74;
        }

        .week-day.day-bloqueado {
            background: #ecfdf3;
            border-color: #86efac;
        }

        .day-name {
            font-weight: 600;
        }

        .day-date {
            font-size: 13px;
            color: #64748b;
        }

        .day-remito {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
        }

        .day-remito.day-vacio {
            font-weight: 500;
            color: #94a3b8;
        }

        .pedido-table-wrapper {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }

        .pedido-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
            background: #ffffff;
        }

        .pedido-table th,
        .pedido-table td {
            padding: 10px;
            border: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
        }

        .pedido-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #0f172a;
        }

        .pedido-planta {
            font-weight: 600;
            color: #1f2937;
            text-align: left;
            min-width: 160px;
        }

        .pedido-input {
            width: 64px;
            padding: 6px;
            border: 1px solid #cbd5f5;
            border-radius: 8px;
            text-align: center;
            background: #f8fafc;
        }

        .pedido-actions {
            margin-top: 16px;
            align-items: center;
            gap: 12px;
        }

        @media (max-width: 900px) {
            .pedido-header {
                flex-direction: column;
                align-items: stretch;
            }

            .pedido-semana {
                grid-template-columns: auto 1fr auto;
            }

            .week-grid {
                grid-template-columns: repeat(7, minmax(90px, 1fr));
            }
        }
    </style>
</body>

</html>
