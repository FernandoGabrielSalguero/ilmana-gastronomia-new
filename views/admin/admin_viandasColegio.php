<?php
require_once __DIR__ . '/../../controllers/admin_viandasColegioController.php';
$selectedNiveles = $_POST['nivel_educativo'] ?? [];
if (!is_array($selectedNiveles)) {
    $selectedNiveles = [$selectedNiveles];
}
$estadoSeleccionado = $_POST['estado'] ?? 'En venta';
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

                    <form class="form-modern" method="post">
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
                                <div class="input-icon input-icon-date">
                                    <input type="text" id="fecha_hora_compra" name="fecha_hora_compra"
                                        value="<?= htmlspecialchars($_POST['fecha_hora_compra'] ?? '') ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="fecha_hora_cancelacion">Fecha y hora cancelacion</label>
                                <div class="input-icon input-icon-date">
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

                            <div class="input-group" style="grid-column: span 3;">
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
                                    <label class="chip-option">
                                        <input type="checkbox" name="nivel_educativo[]" value="Sin Curso Asignado"
                                            <?= in_array('Sin Curso Asignado', $selectedNiveles, true) ? 'checked' : '' ?> />
                                        <span>Sin Curso Asignado</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Guardar</button>
                            <button class="btn btn-cancelar" type="reset">Cancelar</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Listado</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($menuItems)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha entrega</th>
                                        <th>Compra</th>
                                        <th>Cancelacion</th>
                                        <th>Nombre</th>
                                        <th>Precio</th>
                                        <th>Estado</th>
                                        <th>Nivel educativo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($menuItems as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['Fecha_entrega'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($item['Fecha_hora_compra'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($item['Fecha_hora_cancelacion'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($item['Nombre'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($item['Precio'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($item['Estado'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($item['Nivel_Educativo'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>Sin datos cargados todavia.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fechaEntrega = document.getElementById('fecha_entrega');
            const fechaCompra = document.getElementById('fecha_hora_compra');
            const fechaCancelacion = document.getElementById('fecha_hora_cancelacion');

            const pickerConfig = {
                enableTime: true,
                enableSeconds: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i:S',
                locale: 'es'
            };

            const compraPicker = fechaCompra ? flatpickr(fechaCompra, pickerConfig) : null;
            const cancelacionPicker = fechaCancelacion ? flatpickr(fechaCancelacion, pickerConfig) : null;

            const getTimePart = (value) => {
                if (!value) {
                    return '00:00:00';
                }
                const partes = value.trim().split(' ');
                return partes[1] || '00:00:00';
            };

            const syncFecha = (picker, input) => {
                if (!fechaEntrega || !fechaEntrega.value || !input) {
                    return;
                }
                const timePart = getTimePart(input.value);
                const nextValue = `${fechaEntrega.value} ${timePart}`;
                if (picker) {
                    picker.setDate(nextValue, true);
                } else {
                    input.value = nextValue;
                }
            };

            if (fechaEntrega) {
                fechaEntrega.addEventListener('change', () => {
                    syncFecha(compraPicker, fechaCompra);
                    syncFecha(cancelacionPicker, fechaCancelacion);
                });
            }

            if (fechaEntrega && fechaEntrega.value) {
                syncFecha(compraPicker, fechaCompra);
                syncFecha(cancelacionPicker, fechaCancelacion);
            }
        });
    </script>
</body>

</html>
