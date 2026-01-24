<?php
require_once __DIR__ . '/../../controllers/admin_saldoController.php';

$estadoOpciones = [
    '' => 'Todos',
    'Pendiente de aprobacion' => 'Pendiente de aprobacion',
    'Aprobado' => 'Aprobado',
    'Cancelado' => 'Cancelado'
];

$badgeClass = function ($estado) {
    if ($estado === 'Aprobado') {
        return 'success';
    }
    if ($estado === 'Cancelado') {
        return 'danger';
    }
    if ($estado === 'Pendiente de aprobacion') {
        return 'warning';
    }
    return '';
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
        .saldo-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .saldo-table thead th {
            text-align: left;
            font-size: 13px;
            color: #6b7280;
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .saldo-table tbody td {
            padding: 12px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .saldo-table-wrap {
            overflow-x: auto;
        }

        .saldo-actions {
            display: inline-flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .saldo-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .saldo-pill .material-icons {
            font-size: 18px;
        }

        .saldo-empty {
            text-align: center;
            color: #6b7280;
            padding: 12px 0;
        }

        .saldo-user {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .saldo-user small {
            color: #6b7280;
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
                    <li onclick="location.href='admin_saldo.php'">
                        <span class="material-icons" style="color: #5b21b6;">paid</span><span class="link-text">Saldos</span>
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
                <div class="navbar-title">Saldos</div>
            </header>

            <!-- CONTENIDO -->
            <section class="content">
                <div class="card">
                    <h2>Gestion de saldos</h2>
                    <p>Revisa, aprueba o cancela las solicitudes de saldo enviadas por los usuarios.</p>
                </div>

                <?php if (!empty($mensaje)): ?>
                    <div class="card" style="border-left: 4px solid #16a34a;">
                        <p><?= htmlspecialchars($mensaje) ?></p>
                    </div>
                <?php endif; ?>

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
                    <h3>Filtros</h3>
                    <form class="form-modern" method="get" id="saldo-filter-form">
                        <div class="form-grid grid-4">
                            <div class="input-group">
                                <label>Colegio</label>
                                <div class="input-icon">
                                    <span class="material-icons">school</span>
                                    <select name="colegio" class="saldo-colegio">
                                        <option value="">Todos</option>
                                        <?php foreach ($colegios as $colegio): ?>
                                            <option value="<?= (int) $colegio['Id'] ?>" <?= $colegioId === (int) $colegio['Id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($colegio['Nombre'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="input-group">
                                <label>Curso</label>
                                <div class="input-icon">
                                    <span class="material-icons">class</span>
                                    <select name="curso" class="saldo-curso">
                                        <option value="">Todos</option>
                                        <?php foreach ($cursos as $curso): ?>
                                            <option value="<?= (int) $curso['Id'] ?>" <?= $cursoId === (int) $curso['Id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($curso['Nombre'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="input-group">
                                <label>Estado</label>
                                <div class="input-icon">
                                    <span class="material-icons">flag</span>
                                    <select name="estado" class="saldo-estado">
                                        <?php foreach ($estadoOpciones as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= $estado === $value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="input-group">
                                <label>Fecha desde</label>
                                <div class="input-icon">
                                    <span class="material-icons">event</span>
                                    <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde) ?>">
                                </div>
                            </div>

                            <div class="input-group">
                                <label>Fecha hasta</label>
                                <div class="input-icon">
                                    <span class="material-icons">event</span>
                                    <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Aplicar filtros</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Solicitudes</h3>
                    </div>
                    <div class="card-body">
                        <div class="saldo-table-wrap">
                            <table class="saldo-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Observaciones</th>
                                        <th>Comprobante</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="saldo-table-body">
                                    <?php if (!empty($solicitudes)): ?>
                                        <?php foreach ($solicitudes as $solicitud): ?>
                                            <?php
                                            $estadoActual = $solicitud['Estado'] ?? '';
                                            $comprobante = $solicitud['Comprobante'] ?? '';
                                            $comprobanteFile = $comprobante ? basename((string) $comprobante) : '';
                                            ?>
                                            <tr data-id="<?= (int) ($solicitud['Id'] ?? 0) ?>"
                                                data-observaciones="<?= htmlspecialchars($solicitud['Observaciones'] ?? '') ?>"
                                                data-estado="<?= htmlspecialchars($estadoActual) ?>">
                                                <td><?= (int) ($solicitud['Id'] ?? 0) ?></td>
                                                <td>
                                                    <div class="saldo-user">
                                                        <span><?= htmlspecialchars($solicitud['UsuarioNombre'] ?? '') ?></span>
                                                        <small><?= htmlspecialchars($solicitud['UsuarioCorreo'] ?? $solicitud['UsuarioLogin'] ?? '') ?></small>
                                                    </div>
                                                </td>
                                                <td>$<?= number_format((float) ($solicitud['Saldo'] ?? 0), 2, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($estadoActual !== ''): ?>
                                                        <span class="badge <?= htmlspecialchars($badgeClass($estadoActual)) ?>">
                                                            <?= htmlspecialchars($estadoActual) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($solicitud['Fecha_pedido'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($solicitud['Observaciones'] ?? '') ?></td>
                                                <td>
                                                    <?php if ($comprobanteFile): ?>
                                                        <a href="../../uploads/comprobantes_inbox/<?= htmlspecialchars($comprobanteFile) ?>" target="_blank">Ver</a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($estadoActual === 'Pendiente de aprobacion'): ?>
                                                        <div class="saldo-actions">
                                                            <button type="button" class="btn btn-small btn-aceptar" data-action="aprobar">
                                                                <span class="saldo-pill"><span class="material-icons">check_circle</span>Aprobar</span>
                                                            </button>
                                                            <button type="button" class="btn btn-small btn-cancelar" data-action="cancelar">
                                                                <span class="saldo-pill"><span class="material-icons">cancel</span>Cancelar</span>
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="saldo-empty">Sin solicitudes para mostrar.</td>
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

    <script>
        const saldoEndpoint = 'admin_saldo.php';
        const tableBody = document.getElementById('saldo-table-body');
        const filterForm = document.getElementById('saldo-filter-form');

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function showAlertSafe(type, message) {
            if (typeof window.showAlert === 'function') {
                try {
                    if (window.showAlert.length <= 1) {
                        window.showAlert({ type, message });
                    } else {
                        window.showAlert(type, message);
                    }
                    return;
                } catch (err) {
                    console.warn('showAlert failed, falling back to alert.', err);
                }
            }
            alert(message);
        }

        function estadoBadge(estado) {
            if (estado === 'Aprobado') {
                return 'success';
            }
            if (estado === 'Cancelado') {
                return 'danger';
            }
            if (estado === 'Pendiente de aprobacion') {
                return 'warning';
            }
            return '';
        }

        function renderRows(items) {
            if (!tableBody) return;
            if (!items || items.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" class="saldo-empty">Sin solicitudes para mostrar.</td></tr>';
                return;
            }
            tableBody.innerHTML = items.map((item) => {
                const estado = item.Estado || '';
                const comprobante = item.Comprobante ? String(item.Comprobante) : '';
                const comprobanteFile = comprobante ? comprobante.split(/[\\/]/).pop() : '';
                const comprobanteHtml = comprobanteFile
                    ? `<a href="../../uploads/comprobantes_inbox/${escapeHtml(comprobanteFile)}" target="_blank">Ver</a>`
                    : '-';
                const acciones = estado === 'Pendiente de aprobacion'
                    ? `<div class="saldo-actions">
                            <button type="button" class="btn btn-small btn-aceptar" data-action="aprobar">
                                <span class="saldo-pill"><span class="material-icons">check_circle</span>Aprobar</span>
                            </button>
                            <button type="button" class="btn btn-small btn-cancelar" data-action="cancelar">
                                <span class="saldo-pill"><span class="material-icons">cancel</span>Cancelar</span>
                            </button>
                        </div>`
                    : '-';

                return `
                    <tr data-id="${escapeHtml(item.Id)}"
                        data-observaciones="${escapeHtml(item.Observaciones)}"
                        data-estado="${escapeHtml(estado)}">
                        <td>${escapeHtml(item.Id)}</td>
                        <td>
                            <div class="saldo-user">
                                <span>${escapeHtml(item.UsuarioNombre)}</span>
                                <small>${escapeHtml(item.UsuarioCorreo || item.UsuarioLogin || '')}</small>
                            </div>
                        </td>
                        <td>$${Number(item.Saldo || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td>${estado ? `<span class="badge ${estadoBadge(estado)}">${escapeHtml(estado)}</span>` : ''}</td>
                        <td>${escapeHtml(item.Fecha_pedido || '')}</td>
                        <td>${escapeHtml(item.Observaciones || '')}</td>
                        <td>${comprobanteHtml}</td>
                        <td>${acciones}</td>
                    </tr>`;
            }).join('');
        }

        async function fetchSolicitudes() {
            if (!filterForm) return;
            const params = new URLSearchParams(new FormData(filterForm));
            params.set('action', 'list');
            params.set('ajax', '1');
            const response = await fetch(`${saldoEndpoint}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) {
                showAlertSafe('error', 'No se pudo actualizar el listado.');
                return;
            }
            const data = await response.json();
            if (data.ok) {
                renderRows(data.items || []);
            }
        }

        if (tableBody) {
            tableBody.addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;
                const row = button.closest('tr');
                if (!row) return;
                const action = button.dataset.action;
                const pedidoId = row.dataset.id;
                if (!pedidoId) return;

                const mensajeBase = action === 'aprobar'
                    ? 'Observaciones (opcional) para aprobar:'
                    : 'Observaciones (opcional) para cancelar:';
                const observaciones = prompt(mensajeBase, row.dataset.observaciones || '');
                if (observaciones === null) {
                    return;
                }

                const formData = new FormData();
                formData.set('action', action);
                formData.set('id', pedidoId);
                formData.set('observaciones', observaciones);
                formData.set('ajax', '1');

                try {
                    const response = await fetch(saldoEndpoint, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.ok) {
                        showAlertSafe('success', data.mensaje || 'Solicitud actualizada.');
                        fetchSolicitudes();
                    } else {
                        const errores = data.errores || [];
                        showAlertSafe('error', errores.join(' ') || 'No se pudo actualizar la solicitud.');
                    }
                } catch (error) {
                    showAlertSafe('error', 'No se pudo actualizar la solicitud.');
                }
            });
        }
    </script>
</body>

</html>
