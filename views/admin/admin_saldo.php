<?php
require_once __DIR__ . '/../../controllers/admin_saldoController.php';

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

$estadoLabel = function ($estado) {
    if ($estado === 'Pendiente de aprobacion') {
        return 'Pendiente';
    }
    return $estado;
};

$observacionesLabel = function ($texto) {
    $texto = trim((string) $texto);
    return $texto === '' ? '-' : $texto;
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
                    <div class="card-header">
                        <h3 class="card-title">Solicitudes</h3>
                    </div>
                    <div class="card-body">
                        <div class="tabla-wrapper">
                            <table class="data-table">
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
                                                        <div><?= htmlspecialchars($solicitud['UsuarioNombre'] ?? '') ?></div>
                                                        <div class="gform-helper"><?= htmlspecialchars($solicitud['UsuarioCorreo'] ?? $solicitud['UsuarioLogin'] ?? '') ?></div>
                                                        <div class="gform-helper">Cel: <?= htmlspecialchars($solicitud['UsuarioTelefono'] ?? '-') ?></div>
                                                    </div>
                                                </td>
                                                <td>$<?= number_format((float) ($solicitud['Saldo'] ?? 0), 2, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($estadoActual !== ''): ?>
                                                        <span class="badge <?= htmlspecialchars($badgeClass($estadoActual)) ?>">
                                                            <?= htmlspecialchars($estadoLabel($estadoActual)) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($solicitud['Fecha_pedido'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($observacionesLabel($solicitud['Observaciones'] ?? '')) ?></td>
                                                <td>
                                                    <?php if ($comprobanteFile): ?>
                                                        <a href="../../uploads/comprobantes_inbox/<?= htmlspecialchars($comprobanteFile) ?>" target="_blank" title="Ver comprobante" style="color: #2196f3;">
                                                            <span class="material-icons">visibility</span>
                                                        </a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($estadoActual === 'Pendiente de aprobacion'): ?>
                                                        <div class="gform-actions">
                                                            <a href="#" data-action="aprobar" title="Aprobar" style="color: #5b21b6;">
                                                                <span class="material-icons">task_alt</span>
                                                            </a>
                                                            <a href="#" data-action="cancelar" title="Cancelar" style="color: #5b21b6;">
                                                                <span class="material-icons">cancel</span>
                                                            </a>
                                                            <?php
                                                            $telefonoRaw = $solicitud['UsuarioTelefono'] ?? '';
                                                            $telefonoWhatsapp = preg_replace('/\D+/', '', (string) $telefonoRaw);
                                                            ?>
                                                            <?php if ($telefonoWhatsapp !== ''): ?>
                                                                <a href="https://wa.me/<?= htmlspecialchars($telefonoWhatsapp) ?>" target="_blank" title="Enviar WhatsApp" style="color: #5b21b6;">
                                                                    <span class="material-icons">chat</span>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <?php
                                                        $telefonoRaw = $solicitud['UsuarioTelefono'] ?? '';
                                                        $telefonoWhatsapp = preg_replace('/\D+/', '', (string) $telefonoRaw);
                                                        ?>
                                                        <?php if ($telefonoWhatsapp !== ''): ?>
                                                            <a href="https://wa.me/<?= htmlspecialchars($telefonoWhatsapp) ?>" target="_blank" title="Enviar WhatsApp" style="color: #5b21b6;">
                                                                <span class="material-icons">chat</span>
                                                            </a>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="gform-helper">Sin solicitudes para mostrar.</td>
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

    <div class="modal hidden" id="saldo-cancel-modal" role="dialog" aria-modal="true" aria-labelledby="saldo-cancel-title">
        <div class="modal-content">
            <h3 id="saldo-cancel-title">Cancelar solicitud</h3>
            <p>Indicá el motivo de cancelación.</p>
            <form id="saldo-cancel-form">
                <div class="input-group">
                    <label for="saldo-cancel-reason">Motivo de cancelación</label>
                    <div class="input-icon input-icon-comment">
                        <textarea id="saldo-cancel-reason" required></textarea>
                    </div>
                </div>
                <br>
                <div class="form-buttons">
                    <button type="button" class="btn btn-cancelar" id="saldo-cancel-close">Cerrar</button>
                    <button type="submit" class="btn btn-aceptar" id="saldo-cancel-confirm">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal hidden" id="saldo-approve-modal" role="dialog" aria-modal="true" aria-labelledby="saldo-approve-title">
        <div class="modal-content">
            <h3 id="saldo-approve-title">Solicitud aprobada</h3>
            <p id="saldo-approve-text">Saldo final del usuario: $0,00</p>
            <div class="form-buttons">
                <button type="button" class="btn btn-aceptar" id="saldo-approve-close">Listo</button>
            </div>
        </div>
    </div>

    <script>
        const saldoEndpoint = 'admin_saldo.php';
        const tableBody = document.getElementById('saldo-table-body');
        const filterForm = document.getElementById('saldo-filter-form');
        const cancelModal = document.getElementById('saldo-cancel-modal');
        const cancelReasonInput = document.getElementById('saldo-cancel-reason');
        const cancelCloseButton = document.getElementById('saldo-cancel-close');
        const approveModal = document.getElementById('saldo-approve-modal');
        const approveText = document.getElementById('saldo-approve-text');
        const approveCloseButton = document.getElementById('saldo-approve-close');
        let pendingCancelId = null;
        let pendingCancelObservaciones = '';

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

        function estadoLabel(estado) {
            if (estado === 'Pendiente de aprobacion') {
                return 'Pendiente';
            }
            return estado;
        }

        function observacionesLabel(texto) {
            const cleaned = String(texto || '').trim();
            return cleaned === '' ? '-' : cleaned;
        }

        function whatsappLinkHtml(telefono) {
            const digits = String(telefono || '').replace(/\D+/g, '');
            if (!digits) return '-';
            return `<a href="https://wa.me/${digits}" target="_blank" title="Enviar WhatsApp" style="color: #5b21b6;">
                        <span class="material-icons">chat</span>
                    </a>`;
        }

        function renderRows(items) {
            if (!tableBody) return;
            if (!items || items.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" class="gform-helper">Sin solicitudes para mostrar.</td></tr>';
                return;
            }
            tableBody.innerHTML = items.map((item) => {
                const estado = item.Estado || '';
                const comprobante = item.Comprobante ? String(item.Comprobante) : '';
                const comprobanteFile = comprobante ? comprobante.split(/[\\/]/).pop() : '';
                const comprobanteHtml = comprobanteFile
                    ? `<a href="../../uploads/comprobantes_inbox/${escapeHtml(comprobanteFile)}" target="_blank" title="Ver comprobante" style="color: #2196f3;">
                            <span class="material-icons">visibility</span>
                       </a>`
                    : '-';
                const acciones = estado === 'Pendiente de aprobacion'
                    ? `<div class="gform-actions">
                            <a href="#" data-action="aprobar" title="Aprobar" style="color: #58b621;">
                                <span class="material-icons">task_alt</span>
                            </a>
                            <a href="#" data-action="cancelar" title="Cancelar" style="color: #b62121;">
                                <span class="material-icons">cancel</span>
                            </a>
                            ${whatsappLinkHtml(item.UsuarioTelefono)}
                        </div>`
                    : whatsappLinkHtml(item.UsuarioTelefono);

                return `
                    <tr data-id="${escapeHtml(item.Id)}"
                        data-observaciones="${escapeHtml(item.Observaciones)}"
                        data-estado="${escapeHtml(estado)}">
                        <td>${escapeHtml(item.Id)}</td>
                        <td>
                            <div class="saldo-user">
                                <div>${escapeHtml(item.UsuarioNombre)}</div>
                                <div class="gform-helper">${escapeHtml(item.UsuarioCorreo || item.UsuarioLogin || '')}</div>
                                <div class="gform-helper">Cel: ${escapeHtml(item.UsuarioTelefono || '-')}</div>
                            </div>
                        </td>
                        <td>$${Number(item.Saldo || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td>${estado ? `<span class="badge ${estadoBadge(estado)}">${escapeHtml(estadoLabel(estado))}</span>` : ''}</td>
                        <td>${escapeHtml(item.Fecha_pedido || '')}</td>
                        <td>${escapeHtml(observacionesLabel(item.Observaciones))}</td>
                        <td>${comprobanteHtml}</td>
                        <td>${acciones}</td>
                    </tr>`;
            }).join('');
        }

        async function fetchSolicitudes() {
            const params = filterForm ? new URLSearchParams(new FormData(filterForm)) : new URLSearchParams();
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

        function formatMoney(value) {
            return Number(value || 0).toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function openCancelDialog(pedidoId, observaciones) {
            pendingCancelId = pedidoId;
            pendingCancelObservaciones = observaciones || '';
            if (cancelReasonInput) {
                cancelReasonInput.value = pendingCancelObservaciones;
                cancelReasonInput.focus();
            }
            if (cancelModal) {
                cancelModal.classList.remove('hidden');
            }
        }

        function closeCancelModal() {
            if (cancelModal) {
                cancelModal.classList.add('hidden');
            }
        }

        function resetCancelState() {
            pendingCancelId = null;
            pendingCancelObservaciones = '';
        }

        function closeApproveModal() {
            if (approveModal) {
                approveModal.classList.add('hidden');
            }
        }

        async function sendAction(action, pedidoId, observaciones) {
            const formData = new FormData();
            formData.set('action', action);
            formData.set('id', pedidoId);
            formData.set('observaciones', observaciones || '');
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
                    if (action === 'aprobar' && approveModal && approveText) {
                        const saldoFinal = data.saldo_final;
                        if (saldoFinal !== null && saldoFinal !== undefined) {
                            approveText.textContent = `Saldo final del usuario: $${formatMoney(saldoFinal)}`;
                            approveModal.classList.remove('hidden');
                        }
                    }
                    fetchSolicitudes();
                } else {
                    const errores = data.errores || [];
                    showAlertSafe('error', errores.join(' ') || 'No se pudo actualizar la solicitud.');
                }
            } catch (error) {
                showAlertSafe('error', 'No se pudo actualizar la solicitud.');
            }
        }

        if (tableBody) {
            tableBody.addEventListener('click', async (event) => {
                const actionEl = event.target.closest('[data-action]');
                if (!actionEl) return;
                event.preventDefault();
                const row = actionEl.closest('tr');
                if (!row) return;
                const action = actionEl.dataset.action;
                const pedidoId = row.dataset.id;
                if (!pedidoId) return;

                if (action === 'cancelar') {
                    openCancelDialog(pedidoId, row.dataset.observaciones || '');
                    return;
                }

                const observaciones = prompt('Observaciones (opcional) para aprobar:', row.dataset.observaciones || '');
                if (observaciones === null) return;
                sendAction(action, pedidoId, observaciones);
            });
        }

        if (cancelCloseButton && cancelModal) {
            cancelCloseButton.addEventListener('click', () => {
                closeCancelModal();
                resetCancelState();
            });
        }

        if (cancelModal) {
            cancelModal.addEventListener('click', (event) => {
                if (event.target === cancelModal) {
                    closeCancelModal();
                    resetCancelState();
                }
            });
        }

        if (approveCloseButton && approveModal) {
            approveCloseButton.addEventListener('click', closeApproveModal);
        }

        if (approveModal) {
            approveModal.addEventListener('click', (event) => {
                if (event.target === approveModal) {
                    closeApproveModal();
                }
            });
        }

        const cancelForm = document.getElementById('saldo-cancel-form');
        if (cancelForm) {
            cancelForm.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!pendingCancelId) return;
                const motivo = cancelReasonInput ? cancelReasonInput.value.trim() : '';
                if (!motivo) {
                    showAlertSafe('error', 'Debes indicar el motivo de cancelación.');
                    return;
                }
                const pedidoId = pendingCancelId;
                closeCancelModal();
                resetCancelState();
                sendAction('cancelar', pedidoId, motivo);
            });
        }
    </script>
</body>

</html>
