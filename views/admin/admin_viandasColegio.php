<?php
require_once __DIR__ . '/../../controllers/admin_viandasColegioController.php';
$selectedNiveles = $_POST['nivel_educativo'] ?? [];
if (!is_array($selectedNiveles)) {
    $selectedNiveles = [$selectedNiveles];
}
$estadoSeleccionado = $_POST['estado'] ?? 'En venta';
$formatDateTime = function ($value) {
    if (!$value) {
        return '';
    }
    $parts = explode(' ', trim($value), 2);
    $date = $parts[0] ?? '';
    $time = $parts[1] ?? '';
    $formattedDate = $date;
    if ($date) {
        $dateParts = explode('-', $date);
        if (count($dateParts) === 3) {
            $formattedDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        }
    }
    return '<span class="cell-date"><span class="cell-date-date">' . htmlspecialchars($formattedDate) .
        '</span><span class="cell-date-time">' . htmlspecialchars($time) . '</span></span>';
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <style>
        .chip-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        .chip-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .chip-option span {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            font-size: 0.92rem;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .chip-option input:checked+span {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

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

        .tabla-wrapper tbody tr {
            height: 44px;
        }

        .data-table th,
        .data-table td {
            padding: 8px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .col-nombre {
            max-width: 160px;
            white-space: normal !important;
            word-break: break-word;
            line-height: 1.25;
        }

        .col-fecha {
            max-width: 140px;
            white-space: normal;
        }

        .cell-date {
            display: inline-flex;
            flex-direction: column;
            line-height: 1.25;
        }

        .cell-date-date {
            font-weight: 600;
        }

        .cell-date-time {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .table-actions {
            text-align: center;
        }

        .icon-action {
            border: none;
            background: transparent;
            cursor: pointer;
            color: var(--icon-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
        }

        #modal-editar-menu .modal-content {
            width: 80%;
            max-width: 1200px;
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
                <div class="navbar-title">Menu</div>
            </header>

            <!-- CONTENIDO -->
            <section class="content">
                <div class="card">
                    <h2>Menu de comidas</h2>
                    <p>Base lista para empezar a cargar y editar las viandas del colegio.</p>
                </div>

                <div class="card">
                    <h3>Nuevo menu</h3>

                    <?php if (!empty($mensaje)): ?>
                        <p><?= htmlspecialchars($mensaje) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($errores)): ?>
                        <div>
                            <?php foreach ($errores as $error): ?>
                                <p><?= htmlspecialchars($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form class="form-modern" method="post" id="menuForm">
                        <input type="hidden" name="action" value="crear" />
                        <div class="form-grid grid-4">
                            <div class="input-group">
                                <label for="nombre">Nombre</label>
                                <div class="input-icon">
                                    <span class="material-icons">restaurant_menu</span>
                                    <input type="text" id="nombre" name="nombre" maxlength="100" required
                                        value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="fecha_entrega">Fecha entrega</label>
                                <div class="input-icon input-icon-date">
                                    <input type="date" id="fecha_entrega" name="fecha_entrega" required
                                        value="<?= htmlspecialchars($_POST['fecha_entrega'] ?? '') ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="fecha_hora_compra">Fecha y hora compra</label>
                                <div class="input-icon">
                                    <input type="text" id="fecha_hora_compra" name="fecha_hora_compra"
                                        value="<?= htmlspecialchars($_POST['fecha_hora_compra'] ?? '') ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="fecha_hora_cancelacion">Fecha y hora cancelacion</label>
                                <div class="input-icon">
                                    <input type="text" id="fecha_hora_cancelacion"
                                        name="fecha_hora_cancelacion"
                                        value="<?= htmlspecialchars($_POST['fecha_hora_cancelacion'] ?? '') ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="precio">Precio</label>
                                <div class="input-icon">
                                    <span class="material-icons">attach_money</span>
                                    <input type="number" id="precio" name="precio" step="0.01" min="0"
                                        value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="estado">Estado</label>
                                <div class="input-icon input-icon-globe">
                                    <select id="estado" name="estado" required>
                                        <option value="">Seleccionar</option>
                                        <option value="En venta" <?= ($estadoSeleccionado === 'En venta') ? 'selected' : '' ?>>En venta</option>
                                        <option value="Sin stock" <?= ($estadoSeleccionado === 'Sin stock') ? 'selected' : '' ?>>Sin stock</option>
                                    </select>
                                </div>
                            </div>

                            <div class="input-group" style="grid-column: span 2;">
                                <label>Nivel educativo</label>
                                <div class="chip-options">
                                    <label class="chip-option">
                                        <input type="checkbox" name="nivel_educativo[]" value="Inicial"
                                            <?= in_array('Inicial', $selectedNiveles, true) ? 'checked' : '' ?> />
                                        <span>Inicial</span>
                                    </label>
                                    <label class="chip-option">
                                        <input type="checkbox" name="nivel_educativo[]" value="Primaria"
                                            <?= in_array('Primaria', $selectedNiveles, true) ? 'checked' : '' ?> />
                                        <span>Primaria</span>
                                    </label>
                                    <label class="chip-option">
                                        <input type="checkbox" name="nivel_educativo[]" value="Secundaria"
                                            <?= in_array('Secundaria', $selectedNiveles, true) ? 'checked' : '' ?> />
                                        <span>Secundaria</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Guardar</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Listado</h3>
                    </div>
                    <div class="card-body">
                        <div class="tabla-wrapper">
                            <table class="data-table" id="menuTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th class="col-nombre">Nombre</th>
                                        <th class="col-fecha">Fecha entrega</th>
                                        <th class="col-fecha">Fecha limite de compra</th>
                                        <th class="col-fecha">Fecha limite de venta</th>
                                        <th>Precio</th>
                                        <th>Estado</th>
                                        <th>Nivel educativo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="menuTableBody">
                                    <?php if (!empty($menuItems)): ?>
                                        <?php foreach ($menuItems as $item): ?>
                                            <?php
                                            $estado = $item['Estado'] ?? '';
                                            $badgeClass = $estado === 'En venta' ? 'success' : ($estado === 'Sin stock' ? 'warning' : '');
                                            ?>
                                            <tr data-id="<?= htmlspecialchars($item['Id'] ?? '') ?>"
                                                data-nombre="<?= htmlspecialchars($item['Nombre'] ?? '') ?>"
                                                data-fecha-entrega="<?= htmlspecialchars($item['Fecha_entrega'] ?? '') ?>"
                                                data-fecha-compra="<?= htmlspecialchars($item['Fecha_hora_compra'] ?? '') ?>"
                                                data-fecha-cancelacion="<?= htmlspecialchars($item['Fecha_hora_cancelacion'] ?? '') ?>"
                                                data-precio="<?= htmlspecialchars($item['Precio'] ?? '') ?>"
                                                data-estado="<?= htmlspecialchars($item['Estado'] ?? '') ?>"
                                                data-nivel="<?= htmlspecialchars($item['Nivel_Educativo'] ?? '') ?>">
                                                <td><?= htmlspecialchars($item['Id'] ?? '') ?></td>
                                                <td class="col-nombre"><?= htmlspecialchars($item['Nombre'] ?? '') ?></td>
                                                <td class="col-fecha"><?= $formatDateTime($item['Fecha_entrega'] ?? '') ?></td>
                                                <td class="col-fecha"><?= $formatDateTime($item['Fecha_hora_compra'] ?? '') ?></td>
                                                <td class="col-fecha"><?= $formatDateTime($item['Fecha_hora_cancelacion'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($item['Precio'] ?? '') ?></td>
                                                <td>
                                                    <?php if ($estado !== ''): ?>
                                                        <span class="badge <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($estado) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($item['Nivel_Educativo'] ?? '') ?></td>
                                                <td class="table-actions">
                                                    <button type="button" class="icon-action" data-action="editar" aria-label="Editar menu">
                                                        <span class="material-icons">edit</span>
                                                    </button>
                                                    <button type="button" class="icon-action" data-action="eliminar" aria-label="Cambiar estado">
                                                        <span class="material-icons">power_settings_new</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9">Sin datos cargados todavia.</td>
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

    <div id="modal-editar-menu" class="modal hidden">
        <div class="modal-content">
            <h3>Editar menu</h3>
            <form class="form-modern" id="editMenuForm">
                <input type="hidden" name="action" value="actualizar" />
                <input type="hidden" name="id" id="edit_id" />
                <div class="form-grid grid-4">
                    <div class="input-group">
                        <label for="edit_nombre">Nombre</label>
                        <div class="input-icon">
                            <span class="material-icons">restaurant_menu</span>
                            <input type="text" id="edit_nombre" name="nombre" maxlength="100" required />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_fecha_entrega">Fecha entrega</label>
                        <div class="input-icon input-icon-date">
                            <input type="date" id="edit_fecha_entrega" name="fecha_entrega" required />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_fecha_hora_compra">Fecha y hora compra</label>
                        <div class="input-icon">
                            <input type="text" id="edit_fecha_hora_compra" name="fecha_hora_compra" />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_fecha_hora_cancelacion">Fecha y hora cancelacion</label>
                        <div class="input-icon">
                            <input type="text" id="edit_fecha_hora_cancelacion" name="fecha_hora_cancelacion" />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_precio">Precio</label>
                        <div class="input-icon">
                            <span class="material-icons">attach_money</span>
                            <input type="number" id="edit_precio" name="precio" step="0.01" min="0" />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_estado">Estado</label>
                        <div class="input-icon input-icon-globe">
                            <select id="edit_estado" name="estado" required>
                                <option value="">Seleccionar</option>
                                <option value="En venta">En venta</option>
                                <option value="Sin stock">Sin stock</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_nivel">Nivel educativo</label>
                        <div class="input-icon input-icon-globe">
                            <select id="edit_nivel" name="nivel_educativo" required>
                                <option value="">Seleccionar</option>
                                <option value="Inicial">Inicial</option>
                                <option value="Primaria">Primaria</option>
                                <option value="Secundaria">Secundaria</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-buttons">
                    <button class="btn btn-aceptar" type="submit">Guardar cambios</button>
                    <button class="btn btn-cancelar" type="button" onclick="closeMenuModal()">Cerrar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuEndpoint = '../../controllers/admin_viandasColegioController.php';
            const menuForm = document.getElementById('menuForm');
            const editForm = document.getElementById('editMenuForm');
            const tableBody = document.getElementById('menuTableBody');

            const fechaEntrega = document.getElementById('fecha_entrega');
            const fechaCompra = document.getElementById('fecha_hora_compra');
            const fechaCancelacion = document.getElementById('fecha_hora_cancelacion');

            const editFechaEntrega = document.getElementById('edit_fecha_entrega');
            const editFechaCompra = document.getElementById('edit_fecha_hora_compra');
            const editFechaCancelacion = document.getElementById('edit_fecha_hora_cancelacion');

            const pickerConfig = {
                enableTime: true,
                enableSeconds: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i:S',
                locale: 'es'
            };

            const compraPicker = fechaCompra ? flatpickr(fechaCompra, pickerConfig) : null;
            const cancelacionPicker = fechaCancelacion ? flatpickr(fechaCancelacion, pickerConfig) : null;
            const editCompraPicker = editFechaCompra ? flatpickr(editFechaCompra, pickerConfig) : null;
            const editCancelacionPicker = editFechaCancelacion ? flatpickr(editFechaCancelacion, pickerConfig) : null;

            const getTimePart = (value) => {
                if (!value) {
                    return '00:00:00';
                }
                const partes = value.trim().split(' ');
                return partes[1] || '00:00:00';
            };

            const syncFecha = (picker, input, baseInput) => {
                if (!baseInput || !baseInput.value || !input) {
                    return;
                }
                const timePart = getTimePart(input.value);
                const nextValue = `${baseInput.value} ${timePart}`;
                if (picker) {
                    picker.setDate(nextValue, true);
                } else {
                    input.value = nextValue;
                }
            };

            if (fechaEntrega) {
                fechaEntrega.addEventListener('change', () => {
                    syncFecha(compraPicker, fechaCompra, fechaEntrega);
                    syncFecha(cancelacionPicker, fechaCancelacion, fechaEntrega);
                });
            }

            if (editFechaEntrega) {
                editFechaEntrega.addEventListener('change', () => {
                    syncFecha(editCompraPicker, editFechaCompra, editFechaEntrega);
                    syncFecha(editCancelacionPicker, editFechaCancelacion, editFechaEntrega);
                });
            }

            if (fechaEntrega && fechaEntrega.value) {
                syncFecha(compraPicker, fechaCompra, fechaEntrega);
                syncFecha(cancelacionPicker, fechaCancelacion, fechaEntrega);
            }

            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };

            const formatDateTimeCell = (value) => {
                if (!value) {
                    return '';
                }
                const parts = String(value).trim().split(' ');
                const datePart = parts[0] || '';
                const timePart = parts[1] || '';
                let formattedDate = datePart;
                if (datePart) {
                    const datePieces = datePart.split('-');
                    if (datePieces.length === 3) {
                        formattedDate = `${datePieces[2]}-${datePieces[1]}-${datePieces[0]}`;
                    }
                }
                return `<span class="cell-date"><span class="cell-date-date">${escapeHtml(formattedDate)}</span><span class="cell-date-time">${escapeHtml(timePart)}</span></span>`;
            };

            const renderRows = (items) => {
                if (!tableBody) {
                    return;
                }
                if (!items || items.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="9">Sin datos cargados todavia.</td></tr>';
                    return;
                }
                tableBody.innerHTML = items.map((item) => {
                    const estado = item.Estado || '';
                    const badgeClass = estado === 'En venta' ? 'success' : (estado === 'Sin stock' ? 'warning' : '');
                    const badgeHtml = estado ? `<span class="badge ${badgeClass}">${escapeHtml(estado)}</span>` : '';
                    return `
                        <tr data-id="${escapeHtml(item.Id)}"
                            data-nombre="${escapeHtml(item.Nombre)}"
                            data-fecha-entrega="${escapeHtml(item.Fecha_entrega)}"
                            data-fecha-compra="${escapeHtml(item.Fecha_hora_compra)}"
                            data-fecha-cancelacion="${escapeHtml(item.Fecha_hora_cancelacion)}"
                            data-precio="${escapeHtml(item.Precio)}"
                            data-estado="${escapeHtml(item.Estado)}"
                            data-nivel="${escapeHtml(item.Nivel_Educativo)}">
                            <td>${escapeHtml(item.Id)}</td>
                            <td class="col-nombre">${escapeHtml(item.Nombre)}</td>
                            <td class="col-fecha">${formatDateTimeCell(item.Fecha_entrega)}</td>
                            <td class="col-fecha">${formatDateTimeCell(item.Fecha_hora_compra)}</td>
                            <td class="col-fecha">${formatDateTimeCell(item.Fecha_hora_cancelacion)}</td>
                            <td>${escapeHtml(item.Precio)}</td>
                            <td>${badgeHtml}</td>
                            <td>${escapeHtml(item.Nivel_Educativo)}</td>
                            <td class="table-actions">
                                <button type="button" class="icon-action" data-action="editar" aria-label="Editar menu">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button type="button" class="icon-action" data-action="eliminar" aria-label="Cambiar estado">
                                    <span class="material-icons">power_settings_new</span>
                                </button>
                            </td>
                        </tr>`;
                }).join('');
            };

            const fetchMenuItems = async () => {
                try {
                    const response = await fetch(`${menuEndpoint}?action=list&ajax=1`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();
                    if (data.ok) {
                        renderRows(data.items);
                    }
                } catch (error) {
                    console.error('Error al cargar el listado:', error);
                }
            };

            const showErrorAlert = (payload, fallback) => {
                const errors = payload?.errores || [];
                const message = errors.length ? errors.join(' ') : (payload?.mensaje || fallback);
                showAlert('error', message);
            };

            if (menuForm) {
                menuForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const formData = new FormData(menuForm);
                    formData.set('action', 'crear');
                    formData.set('ajax', '1');

                    try {
                        const response = await fetch(menuEndpoint, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });
                        const data = await response.json();
                        if (data.ok) {
                            showAlert('success', data.mensaje || 'Menu guardado correctamente.');
                            menuForm.reset();
                        } else {
                            showErrorAlert(data, 'No se pudo guardar el menu.');
                        }
                        await fetchMenuItems();
                    } catch (error) {
                        showAlert('error', 'No se pudo guardar el menu.');
                    }
                });
            }

            if (editForm) {
                editForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const formData = new FormData(editForm);
                    formData.set('action', 'actualizar');
                    formData.set('ajax', '1');

                    try {
                        const response = await fetch(menuEndpoint, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });
                        const data = await response.json();
                        if (data.ok) {
                            showAlert('success', data.mensaje || 'Menu actualizado correctamente.');
                            closeMenuModal();
                        } else {
                            showErrorAlert(data, 'No se pudo actualizar el menu.');
                        }
                        await fetchMenuItems();
                    } catch (error) {
                        showAlert('error', 'No se pudo actualizar el menu.');
                    }
                });
            }

            if (tableBody) {
                tableBody.addEventListener('click', async (event) => {
                    const button = event.target.closest('button[data-action]');
                    if (!button) {
                        return;
                    }
                    const row = button.closest('tr');
                    if (!row) {
                        return;
                    }
                    const action = button.dataset.action;

                    if (action === 'editar') {
                        openMenuModal(row);
                    }

                    if (action === 'eliminar') {
                        const id = row.dataset.id;
                        if (!id) {
                            return;
                        }
                        const currentEstado = row.dataset.estado || '';
                        const nextEstado = currentEstado === 'En venta' ? 'Sin stock' : 'En venta';
                        const formData = new FormData();
                        formData.set('action', 'actualizar');
                        formData.set('ajax', '1');
                        formData.set('id', id);
                        formData.set('nombre', row.dataset.nombre || '');
                        formData.set('fecha_entrega', row.dataset.fechaEntrega || '');
                        formData.set('fecha_hora_compra', row.dataset.fechaCompra || '');
                        formData.set('fecha_hora_cancelacion', row.dataset.fechaCancelacion || '');
                        formData.set('precio', row.dataset.precio || '');
                        formData.set('estado', nextEstado);
                        formData.set('nivel_educativo', row.dataset.nivel || '');

                        try {
                            const response = await fetch(menuEndpoint, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            });
                            const data = await response.json();
                            if (data.ok) {
                                showAlert('success', data.mensaje || 'Estado actualizado correctamente.');
                            } else {
                                showErrorAlert(data, 'No se pudo actualizar el estado.');
                            }
                            await fetchMenuItems();
                        } catch (error) {
                            showAlert('error', 'No se pudo actualizar el estado.');
                        }
                    }
                });
            }

            fetchMenuItems();
            setInterval(fetchMenuItems, 10000);
        });

        const openMenuModal = (row) => {
            const modal = document.getElementById('modal-editar-menu');
            if (!modal || !row) {
                return;
            }
            const idInput = document.getElementById('edit_id');
            const nombreInput = document.getElementById('edit_nombre');
            const fechaEntregaInput = document.getElementById('edit_fecha_entrega');
            const fechaCompraInput = document.getElementById('edit_fecha_hora_compra');
            const fechaCancelacionInput = document.getElementById('edit_fecha_hora_cancelacion');
            const precioInput = document.getElementById('edit_precio');
            const estadoInput = document.getElementById('edit_estado');
            const nivelInput = document.getElementById('edit_nivel');

            const fechaEntrega = row.dataset.fechaEntrega || '';
            const fechaEntregaDate = fechaEntrega ? fechaEntrega.split(' ')[0] : '';

            if (idInput) idInput.value = row.dataset.id || '';
            if (nombreInput) nombreInput.value = row.dataset.nombre || '';
            if (fechaEntregaInput) fechaEntregaInput.value = fechaEntregaDate;
            if (fechaCompraInput) fechaCompraInput.value = row.dataset.fechaCompra || '';
            if (fechaCancelacionInput) fechaCancelacionInput.value = row.dataset.fechaCancelacion || '';
            if (precioInput) precioInput.value = row.dataset.precio || '';
            if (estadoInput) estadoInput.value = row.dataset.estado || '';
            if (nivelInput) nivelInput.value = row.dataset.nivel || '';

            modal.classList.remove('hidden');
        };

        const closeMenuModal = () => {
            const modal = document.getElementById('modal-editar-menu');
            if (modal) {
                modal.classList.add('hidden');
            }
        };
    </script>
</body>

</html>






