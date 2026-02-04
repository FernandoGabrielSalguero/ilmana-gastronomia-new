<?php
require_once __DIR__ . '/../../controllers/admin_regalosColegioController.php';

$formatDate = function ($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '-';
    }
    $parts = explode('-', $value);
    if (count($parts) !== 3) {
        return htmlspecialchars($value);
    }
    return htmlspecialchars($parts[2] . '-' . $parts[1] . '-' . $parts[0]);
};
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
        .tabla-wrapper {
            max-height: 700px;
            overflow-y: auto;
        }

        .tabla-wrapper table {
            border-collapse: collapse;
            width: 100%;
            table-layout: auto;
        }

        .tabla-wrapper thead th {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 2;
        }

        .data-table th,
        .data-table td {
            padding: 8px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .cell-muted {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
        }

        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 12px 14px;
            background: #fff;
        }

        .summary-label {
            font-size: 12px;
            color: #6b7280;
        }

        .summary-value {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
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
                    <li onclick="location.href='admin_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='admin_viandasColegio.php'">
                        <span class="material-icons" style="color: #5b21b6;">restaurant_menu</span><span class="link-text">Menu</span>
                    </li>
                    <li onclick="location.href='admin_entregasColegios.php'">
                        <span class="material-icons" style="color: #5b21b6;">school</span><span class="link-text">Colegio</span>
                    </li>
                    <li onclick="location.href='admin_saldo.php'">
                        <span class="material-icons" style="color: #5b21b6;">paid</span><span class="link-text">Saldos</span>
                    </li>
                    <li onclick="location.href='admin_usuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">people</span><span class="link-text">Usuarios</span>
                    </li>
                    <li onclick="location.href='admin_cuyoPlacas.php'">
                        <span class="material-icons" style="color: #5b21b6;">factory</span><span class="link-text">Cuyo Placas</span>
                    </li>
                    <li onclick="location.href='admin_logs.php'">
                        <span class="material-icons" style="color: #5b21b6;">history</span><span class="link-text">Logs</span>
                    </li>
                    <li onclick="location.href='admin_regalosColegio.php'">
                        <span class="material-icons" style="color: #5b21b6;">card_giftcard</span><span class="link-text">Regalos</span>
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
                <div class="navbar-title">Regalos</div>
            </header>

            <!-- CONTENIDO -->
            <section class="content">
                <div class="card">
                    <h2>Regalos por semana</h2>
                    <p>Filtra una semana y revisa cuantas viandas pidio cada hijo para asignar premios.</p>
                </div>

                <?php if (!empty($errores)): ?>
                    <div class="card" style="border-left: 4px solid #dc2626;">
                        <p><strong>Hubo un problema:</strong></p>
                        <ul>
                            <?php foreach ($errores as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filtro semanal</h3>
                    </div>
                    <div class="card-body">
                        <form class="form-modern" method="get">
                            <div class="form-grid grid-4">
                                <div class="input-group">
                                    <label for="fecha_desde">Desde</label>
                                    <div class="input-icon input-icon-date">
                                        <input type="date" id="fecha_desde" name="fecha_desde"
                                            value="<?= htmlspecialchars($fechaDesde) ?>" required />
                                    </div>
                                </div>

                                <div class="input-group">
                                    <label for="fecha_hasta">Hasta</label>
                                    <div class="input-icon input-icon-date">
                                        <input type="date" id="fecha_hasta" name="fecha_hasta"
                                            value="<?= htmlspecialchars($fechaHasta) ?>" required />
                                    </div>
                                </div>

                                <div class="input-group">
                                    <label>Dias habiles</label>
                                    <div class="input-icon">
                                        <input type="text" value="<?= (int) $diasHabiles ?>" readonly />
                                    </div>
                                </div>
                            </div>

                            <div class="gform-helper" style="margin-top: 6px;">
                                Solo se cuentan pedidos no cancelados.
                            </div>

                            <div class="form-buttons">
                                <button class="btn btn-aceptar" type="submit">Filtrar</button>
                                <a class="btn btn-cancelar" href="admin_regalosColegio.php">Semana actual</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="summary-label">Rango</div>
                            <div class="summary-value"><?= $formatDate($fechaDesde) ?> → <?= $formatDate($fechaHasta) ?></div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Hijos con pedidos</div>
                            <div class="summary-value"><?= number_format($totalNinos, 0, ',', '.') ?></div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Total de viandas</div>
                            <div class="summary-value"><?= number_format($totalViandas, 0, ',', '.') ?></div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Semana completa</div>
                            <div class="summary-value"><?= number_format($totalCompletos, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Detalle por hijo</h3>
                    </div>
                    <div class="card-body">
                        <div class="tabla-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Hijo</th>
                                        <th>Colegio</th>
                                        <th>Curso</th>
                                        <th>Nivel</th>
                                        <th>Dias con compra</th>
                                        <th>Viandas en semana</th>
                                        <th>Semana completa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($registros)): ?>
                                        <?php foreach ($registros as $row): ?>
                                            <?php
                                            $diasConCompra = (int) ($row['Dias_Con_Compra'] ?? 0);
                                            $totalPedidos = (int) ($row['Total_Pedidos'] ?? 0);
                                            $esCompleta = $diasHabiles > 0 && $diasConCompra >= $diasHabiles;
                                            $badgeClass = $esCompleta ? 'success' : 'warning';
                                            $badgeLabel = $esCompleta ? 'Completa' : 'Incompleta';
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['Hijo_Nombre'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($row['Colegio_Nombre'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($row['Curso_Nombre'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($row['Nivel_Educativo'] ?? '-') ?></td>
                                                <td>
                                                    <?= number_format($diasConCompra, 0, ',', '.') ?>
                                                    <div class="cell-muted">
                                                        de <?= number_format($diasHabiles, 0, ',', '.') ?> habiles
                                                    </div>
                                                </td>
                                                <td><?= number_format($totalPedidos, 0, ',', '.') ?></td>
                                                <td>
                                                    <span class="badge <?= htmlspecialchars($badgeClass) ?>">
                                                        <?= htmlspecialchars($badgeLabel) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7">Sin datos para el rango seleccionado.</td>
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
</body>

</html>
