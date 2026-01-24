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
                    <p>Selecciona la fecha para cargar o modificar pedidos.</p>
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
                        <form class="form-modern pedido-fecha" method="get" action="cuyo_placa_pedidos.php">
                            <div class="input-group">
                                <label>Fecha</label>
                                <div class="input-icon">
                                    <span class="material-icons">event</span>
                                    <input type="date" name="fecha" value="<?= htmlspecialchars($fechaSeleccionada) ?>" required>
                                </div>
                            </div>
                            <div class="form-buttons">
                                <button class="btn btn-info" type="submit">Cargar fecha</button>
                            </div>
                        </form>
                    </div>

                    <?php if (!empty($alerta)): ?>
                        <div class="pedido-alerta <?= $alerta['tipo'] === 'success' ? 'ok' : 'error' ?>">
                            <?= htmlspecialchars($alerta['mensaje']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($fechaSeleccionada): ?>
                        <form method="post" action="cuyo_placa_pedidos.php">
                            <input type="hidden" name="fecha" value="<?= htmlspecialchars($fechaSeleccionada) ?>">
                            <input type="hidden" name="accion" value="<?= htmlspecialchars($accion) ?>">

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
                                    <?= $accion === 'actualizar' ? 'Actualizar pedidos' : 'Guardar pedidos' ?>
                                </button>
                                <?php if ($bloqueoEdicion): ?>
                                    <span class="pedido-bloqueo">
                                        Las modificaciones para hoy se cierran a las 10:00 (hora Argentina).
                                    </span>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="pedido-alerta error">
                            Selecciona una fecha valida para cargar los pedidos.
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>

    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>

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

        .pedido-fecha .form-buttons {
            margin-top: 12px;
            justify-content: flex-end;
        }

        .pedido-alerta {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .pedido-alerta.ok {
            background: #ecfdf3;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .pedido-alerta.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
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

        .pedido-bloqueo {
            font-size: 13px;
            color: #b91c1c;
        }

        @media (max-width: 900px) {
            .pedido-header {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</body>

</html>
