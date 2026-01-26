<?php
require_once __DIR__ . '/../../controllers/admin_logsController.php';

$estadoBadgeClass = function ($estado) {
    $estado = strtolower(trim((string) $estado));
    if ($estado === 'enviado') {
        return 'success';
    }
    if ($estado === 'fallido') {
        return 'danger';
    }
    return '';
};

$usuarioOptionsHtml = '<option value="">Todos los usuarios</option>';
foreach ($usuariosFiltro as $usuario) {
    $usuarioIdOption = (int) ($usuario['Id'] ?? 0);
    $nombre = trim((string) ($usuario['Nombre'] ?? ''));
    $correo = trim((string) ($usuario['Correo'] ?? ''));
    $label = $nombre !== '' ? $nombre : ($correo !== '' ? $correo : 'Usuario ' . $usuarioIdOption);
    if ($correo !== '' && $nombre !== '') {
        $label .= ' (' . $correo . ')';
    }
    $selected = ($usuarioId ?? null) === $usuarioIdOption ? ' selected' : '';
    $usuarioOptionsHtml .= sprintf(
        '<option value="%s"%s>%s</option>',
        htmlspecialchars((string) $usuarioIdOption),
        $selected,
        htmlspecialchars($label)
    );
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
        .tabla-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filtro-logs {
            min-width: 220px;
            max-width: 360px;
        }

        .filtro-logs select {
            width: 100%;
        }

        .data-table td.wrap-text,
        .data-table th.wrap-text {
            white-space: normal;
            word-break: break-word;
        }

        .log-usuario {
            display: flex;
            flex-direction: column;
            gap: 2px;
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
                <div class="navbar-title">Logs</div>
            </header>

            <!-- CONTENIDO -->
            <section class="content">
                <div class="card">
                    <h2>Logs de correos</h2>
                    <p>Consulta los registros de envio de correos electronicos.</p>
                </div>

                <div class="card">
                    <div class="tabla-header">
                        <h3>Correos registrados</h3>
                        <form class="form-modern filtro-logs" method="get">
                            <div class="input-group">
                                <label for="usuario">Filtrar por usuario</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <select id="usuario" name="usuario">
                                        <?= $usuarioOptionsHtml ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-buttons">
                                <button class="btn btn-info" type="submit">Filtrar</button>
                                <a class="btn btn-cancelar" href="admin_logs.php">Limpiar</a>
                            </div>
                        </form>
                    </div>

                    <div class="tabla-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Correo</th>
                                    <th class="wrap-text">Asunto</th>
                                    <th>Template</th>
                                    <th>Estado</th>
                                    <th class="wrap-text">Error</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <?php
                                        $usuarioNombre = trim((string) ($log['UsuarioNombre'] ?? $log['Nombre'] ?? ''));
                                        $usuarioCorreo = trim((string) ($log['UsuarioCorreo'] ?? $log['Correo'] ?? ''));
                                        $usuarioLogin = trim((string) ($log['UsuarioLogin'] ?? ''));
                                        $estado = trim((string) ($log['Estado'] ?? ''));
                                        $fechaRaw = trim((string) ($log['Creado_En'] ?? ''));
                                        $fechaParts = $fechaRaw !== '' ? preg_split('/\s+/', $fechaRaw, 2) : [];
                                        $fechaTexto = $fechaParts[0] ?? '';
                                        $horaTexto = $fechaParts[1] ?? '';
                                        ?>
                                        <tr>
                                            <td><?= (int) ($log['Id'] ?? 0) ?></td>
                                            <td>
                                                <div class="log-usuario">
                                                    <strong><?= htmlspecialchars($usuarioNombre !== '' ? $usuarioNombre : 'Sin usuario') ?></strong>
                                                    <?php if ($usuarioCorreo !== ''): ?>
                                                        <span class="gform-helper" style="font-size: 0.85rem;">
                                                            <?= htmlspecialchars($usuarioCorreo) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($usuarioLogin !== ''): ?>
                                                        <span class="gform-helper" style="font-size: 0.85rem;">
                                                            <?= htmlspecialchars($usuarioLogin) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($log['Correo'] ?? '-') ?></td>
                                            <td class="wrap-text"><?= htmlspecialchars($log['Asunto'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($log['Template'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($estado !== ''): ?>
                                                    <span class="badge <?= htmlspecialchars($estadoBadgeClass($estado)) ?>">
                                                        <?= htmlspecialchars($estado) ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="wrap-text"><?= htmlspecialchars($log['Error'] ?? '-') ?></td>
                                            <td>
                                                <div><?= htmlspecialchars($fechaTexto) ?></div>
                                                <div class="gform-helper" style="font-size: 0.85rem;">
                                                    <?= htmlspecialchars($horaTexto) ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8">No hay logs de correos para mostrar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </div>
</body>

</html>
