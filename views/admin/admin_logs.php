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
$ajustarHora = function ($fechaRaw) {
    $fechaRaw = trim((string) $fechaRaw);
    if ($fechaRaw === '') {
        return ['', ''];
    }
    $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaRaw);
    if (!$fecha) {
        return [$fechaRaw, ''];
    }
    $fecha->modify('-3 hours');
    return [$fecha->format('Y-m-d'), $fecha->format('H:i:s')];
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

        .filtro-logs input {
            width: 100%;
        }

        .tabla-wrapper.logs-scroll {
            max-height: 420px;
            overflow: auto;
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
                        <div class="form-modern filtro-logs">
                            <div class="input-group">
                                <label for="logs-search">Filtrar por usuario</label>
                                <div class="input-icon input-icon-name">
                                    <input type="text" id="logs-search" name="logs-search" placeholder="Escribe un nombre o correo" autocomplete="off" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tabla-wrapper logs-scroll">
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
                            <tbody id="logs-table-body">
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <?php
                                        $usuarioNombre = trim((string) ($log['UsuarioNombre'] ?? $log['Nombre'] ?? ''));
                                        $usuarioCorreo = trim((string) ($log['UsuarioCorreo'] ?? $log['Correo'] ?? ''));
                                        $usuarioLogin = trim((string) ($log['UsuarioLogin'] ?? ''));
                                        $estado = trim((string) ($log['Estado'] ?? ''));
                                        $fechaRaw = trim((string) ($log['Creado_En'] ?? ''));
                                        [$fechaTexto, $horaTexto] = $ajustarHora($fechaRaw);
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

                <div class="card">
                    <div class="tabla-header">
                        <h3>Monitor de actividades</h3>
                        <div class="form-modern filtro-logs">
                            <div class="input-group">
                                <label for="auditoria-search">Filtrar por usuario</label>
                                <div class="input-icon input-icon-name">
                                    <input type="text" id="auditoria-search" name="auditoria-search" placeholder="Escribe un nombre o correo" autocomplete="off" />
                                </div>
                            </div>
                        </div>
                        <div class="form-modern filtro-logs">
                            <div class="input-group">
                                <label for="auditoria-fecha-desde">Fecha desde</label>
                                <div class="input-icon">
                                    <span class="material-icons">event</span>
                                    <input type="date" id="auditoria-fecha-desde" name="auditoria-fecha-desde" />
                                </div>
                            </div>
                        </div>
                        <div class="form-modern filtro-logs">
                            <div class="input-group">
                                <label for="auditoria-fecha-hasta">Fecha hasta</label>
                                <div class="input-icon">
                                    <span class="material-icons">event</span>
                                    <input type="date" id="auditoria-fecha-hasta" name="auditoria-fecha-hasta" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tabla-wrapper logs-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Evento</th>
                                    <th>Modulo</th>
                                    <th>Entidad</th>
                                    <th>Estado</th>
                                    <th>IP</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody id="auditoria-table-body">
                                <?php if (!empty($auditoria)): ?>
                                    <?php foreach ($auditoria as $item): ?>
                                        <?php
                                        $usuarioNombre = trim((string) ($item['UsuarioNombre'] ?? ''));
                                        $usuarioCorreo = trim((string) ($item['UsuarioCorreo'] ?? ''));
                                        $usuarioLogin = trim((string) ($item['UsuarioLogin'] ?? $item['Usuario_Login'] ?? ''));
                                        $rol = trim((string) ($item['Rol'] ?? ''));
                                        $evento = trim((string) ($item['Evento'] ?? ''));
                                        $modulo = trim((string) ($item['Modulo'] ?? ''));
                                        $entidad = trim((string) ($item['Entidad'] ?? ''));
                                        $estado = trim((string) ($item['Estado'] ?? ''));
                                        $ip = trim((string) ($item['Ip'] ?? ''));
                                        $fechaRaw = trim((string) ($item['Creado_En'] ?? ''));
                                        [$fechaTexto, $horaTexto] = $ajustarHora($fechaRaw);
                                        ?>
                                        <tr>
                                            <td><?= (int) ($item['Id'] ?? 0) ?></td>
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
                                            <td><?= htmlspecialchars($rol !== '' ? $rol : '-') ?></td>
                                            <td><?= htmlspecialchars($evento !== '' ? $evento : '-') ?></td>
                                            <td><?= htmlspecialchars($modulo !== '' ? $modulo : '-') ?></td>
                                            <td><?= htmlspecialchars($entidad !== '' ? $entidad : '-') ?></td>
                                            <td><?= htmlspecialchars($estado !== '' ? $estado : '-') ?></td>
                                            <td><?= htmlspecialchars($ip !== '' ? $ip : '-') ?></td>
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
                                        <td colspan="9">No hay eventos de auditoria para mostrar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </div>

    <script>
        const logsSearchInput = document.getElementById('logs-search');
        const logsTableBody = document.getElementById('logs-table-body');
        const auditoriaSearchInput = document.getElementById('auditoria-search');
        const auditoriaTableBody = document.getElementById('auditoria-table-body');
        const auditoriaFechaDesde = document.getElementById('auditoria-fecha-desde');
        const auditoriaFechaHasta = document.getElementById('auditoria-fecha-hasta');

        const escapeHtml = (value) => {
            const text = String(value ?? '');
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const badgeClass = (estado) => {
            const value = String(estado || '').toLowerCase().trim();
            if (value === 'enviado') return 'success';
            if (value === 'fallido') return 'danger';
            return '';
        };

        const ajustarHora = (fechaRaw) => {
            const value = String(fechaRaw || '').trim();
            if (!value) return {
                fecha: '',
                hora: ''
            };
            const parts = value.split(/\s+/, 2);
            if (parts.length < 2) {
                return {
                    fecha: value,
                    hora: ''
                };
            }
            const [fechaParte, horaParte] = parts;
            const [y, m, d] = fechaParte.split('-').map((n) => parseInt(n, 10));
            const [hh, mm, ss] = horaParte.split(':').map((n) => parseInt(n, 10));
            const date = new Date(y, (m || 1) - 1, d || 1, hh || 0, mm || 0, ss || 0);
            if (Number.isNaN(date.getTime())) {
                return {
                    fecha: value,
                    hora: ''
                };
            }
            date.setHours(date.getHours() - 3);
            const pad = (num) => String(num).padStart(2, '0');
            return {
                fecha: `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`,
                hora: `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
            };
        };

        const buildLogRow = (log) => {
            const usuarioNombre = String(log.UsuarioNombre || log.Nombre || '').trim();
            const usuarioCorreo = String(log.UsuarioCorreo || log.Correo || '').trim();
            const usuarioLogin = String(log.UsuarioLogin || '').trim();
            const estado = String(log.Estado || '').trim();
            const { fecha: fechaTexto, hora: horaTexto } = ajustarHora(log.Creado_En || '');

            return `
                <tr>
                    <td>${escapeHtml(log.Id || 0)}</td>
                    <td>
                        <div class="log-usuario">
                            <strong>${escapeHtml(usuarioNombre !== '' ? usuarioNombre : 'Sin usuario')}</strong>
                            ${usuarioCorreo !== '' ? `<span class="gform-helper" style="font-size: 0.85rem;">${escapeHtml(usuarioCorreo)}</span>` : ''}
                            ${usuarioLogin !== '' ? `<span class="gform-helper" style="font-size: 0.85rem;">${escapeHtml(usuarioLogin)}</span>` : ''}
                        </div>
                    </td>
                    <td>${escapeHtml(log.Correo || '-')}</td>
                    <td class="wrap-text">${escapeHtml(log.Asunto || '-')}</td>
                    <td>${escapeHtml(log.Template || '-')}</td>
                    <td>${estado !== '' ? `<span class="badge ${badgeClass(estado)}">${escapeHtml(estado)}</span>` : '-'}</td>
                    <td class="wrap-text">${escapeHtml(log.Error || '-')}</td>
                    <td>
                        <div>${escapeHtml(fechaTexto)}</div>
                        <div class="gform-helper" style="font-size: 0.85rem;">${escapeHtml(horaTexto)}</div>
                    </td>
                </tr>
            `;
        };

        const renderLogsTable = (logs) => {
            if (!logsTableBody) return;
            if (!Array.isArray(logs) || logs.length === 0) {
                logsTableBody.innerHTML = '<tr><td colspan="8">No hay logs de correos para mostrar.</td></tr>';
                return;
            }
            logsTableBody.innerHTML = logs.map((log) => buildLogRow(log)).join('');
        };

        const buildAuditoriaRow = (item) => {
            const usuarioNombre = String(item.UsuarioNombre || '').trim();
            const usuarioCorreo = String(item.UsuarioCorreo || '').trim();
            const usuarioLogin = String(item.UsuarioLogin || item.Usuario_Login || '').trim();
            const rol = String(item.Rol || '').trim();
            const evento = String(item.Evento || '').trim();
            const modulo = String(item.Modulo || '').trim();
            const entidad = String(item.Entidad || '').trim();
            const estado = String(item.Estado || '').trim();
            const ip = String(item.Ip || '').trim();
            const { fecha: fechaTexto, hora: horaTexto } = ajustarHora(item.Creado_En || '');

            return `
                <tr>
                    <td>${escapeHtml(item.Id || 0)}</td>
                    <td>
                        <div class="log-usuario">
                            <strong>${escapeHtml(usuarioNombre !== '' ? usuarioNombre : 'Sin usuario')}</strong>
                            ${usuarioCorreo !== '' ? `<span class="gform-helper" style="font-size: 0.85rem;">${escapeHtml(usuarioCorreo)}</span>` : ''}
                            ${usuarioLogin !== '' ? `<span class="gform-helper" style="font-size: 0.85rem;">${escapeHtml(usuarioLogin)}</span>` : ''}
                        </div>
                    </td>
                    <td>${escapeHtml(rol !== '' ? rol : '-')}</td>
                    <td>${escapeHtml(evento !== '' ? evento : '-')}</td>
                    <td>${escapeHtml(modulo !== '' ? modulo : '-')}</td>
                    <td>${escapeHtml(entidad !== '' ? entidad : '-')}</td>
                    <td>${escapeHtml(estado !== '' ? estado : '-')}</td>
                    <td>${escapeHtml(ip !== '' ? ip : '-')}</td>
                    <td>
                        <div>${escapeHtml(fechaTexto)}</div>
                        <div class="gform-helper" style="font-size: 0.85rem;">${escapeHtml(horaTexto)}</div>
                    </td>
                </tr>
            `;
        };

        const renderAuditoriaTable = (items) => {
            if (!auditoriaTableBody) return;
            if (!Array.isArray(items) || items.length === 0) {
                auditoriaTableBody.innerHTML = '<tr><td colspan="9">No hay eventos de auditoria para mostrar.</td></tr>';
                return;
            }
            auditoriaTableBody.innerHTML = items.map((item) => buildAuditoriaRow(item)).join('');
        };

        const fetchLogs = (termino) => {
            const formData = new FormData();
            formData.append('action', 'buscar');
            formData.append('termino', termino);
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.ok) {
                        renderLogsTable(data.logs || []);
                    }
                })
                .catch(() => {
                    renderLogsTable([]);
                });
        };

        const fetchAuditoria = (termino) => {
            const formData = new FormData();
            formData.append('action', 'buscar_auditoria');
            formData.append('termino', termino);
            formData.append('ajax', '1');
            if (auditoriaFechaDesde && auditoriaFechaDesde.value) {
                formData.append('fecha_desde', auditoriaFechaDesde.value);
            }
            if (auditoriaFechaHasta && auditoriaFechaHasta.value) {
                formData.append('fecha_hasta', auditoriaFechaHasta.value);
            }

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.ok) {
                        renderAuditoriaTable(data.auditoria || []);
                    }
                })
                .catch(() => {
                    renderAuditoriaTable([]);
                });
        };

        if (logsSearchInput) {
            let searchTimeout = null;
            let lastSearchValue = '';

            logsSearchInput.addEventListener('input', () => {
                const termino = logsSearchInput.value.trim();
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                if (termino.length >= 3 || (termino.length === 0 && lastSearchValue.length >= 3)) {
                    searchTimeout = setTimeout(() => {
                        fetchLogs(termino);
                        lastSearchValue = termino;
                    }, 300);
                    return;
                }
                if (termino.length === 0) {
                    lastSearchValue = '';
                }
            });
        }

        if (auditoriaSearchInput) {
            let searchTimeout = null;
            let lastSearchValue = '';

            auditoriaSearchInput.addEventListener('input', () => {
                const termino = auditoriaSearchInput.value.trim();
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                if (termino.length >= 3 || (termino.length === 0 && lastSearchValue.length >= 3)) {
                    searchTimeout = setTimeout(() => {
                        fetchAuditoria(termino);
                        lastSearchValue = termino;
                    }, 300);
                    return;
                }
                if (termino.length === 0) {
                    lastSearchValue = '';
                }
            });
        }

        const handleAuditoriaFechaChange = () => {
            const termino = auditoriaSearchInput ? auditoriaSearchInput.value.trim() : '';
            if (termino.length >= 3 || termino.length === 0) {
                fetchAuditoria(termino);
            }
        };

        if (auditoriaFechaDesde) {
            auditoriaFechaDesde.addEventListener('change', handleAuditoriaFechaChange);
        }
        if (auditoriaFechaHasta) {
            auditoriaFechaHasta.addEventListener('change', handleAuditoriaFechaChange);
        }
    </script>
</body>

</html>
