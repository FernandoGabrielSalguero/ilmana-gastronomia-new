<?php
require_once __DIR__ . '/../../controllers/admin_usuariosController.php';

$colegioOptionsHtml = '<option value="">Seleccionar</option>';
foreach ($colegios as $colegio) {
    $colegioOptionsHtml .= sprintf(
        '<option value="%s">%s</option>',
        htmlspecialchars((string) ($colegio['Id'] ?? '')),
        htmlspecialchars((string) ($colegio['Nombre'] ?? ''))
    );
}

$cursoOptionsHtml = '<option value="">Seleccionar</option>';
foreach ($cursos as $curso) {
    $cursoOptionsHtml .= sprintf(
        '<option value="%s" data-colegio="%s">%s</option>',
        htmlspecialchars((string) ($curso['Id'] ?? '')),
        htmlspecialchars((string) ($curso['Colegio_Id'] ?? '')),
        htmlspecialchars((string) ($curso['Nombre'] ?? ''))
    );
}

$preferenciaOptionsHtml = '<option value="">Seleccionar</option>';
foreach ($preferencias as $preferencia) {
    $preferenciaOptionsHtml .= sprintf(
        '<option value="%s">%s</option>',
        htmlspecialchars((string) ($preferencia['Id'] ?? '')),
        htmlspecialchars((string) ($preferencia['Nombre'] ?? ''))
    );
}

$saldoValue = $formData['saldo'] !== '' ? $formData['saldo'] : '0';
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
        .hijos-wrapper {
            display: grid;
            gap: 16px;
        }

        .hijo-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 16px;
            background: #ffffff;
        }

        .hijo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .hijo-title {
            font-weight: 600;
            margin: 0;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        #hijos-section {
            margin-top: 16px;
        }

        #hijos-section h3 {
            margin: 0 0 12px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .action-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }

        .data-table td.wrap-text,
        .data-table th.wrap-text {
            white-space: normal;
            word-break: break-word;
        }

        #modal-editar .modal-content {
            max-width: 1100px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
        }

        #edit-hijos-section {
            margin-top: 16px;
        }

        .tabla-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filtro-usuarios {
            min-width: 220px;
            max-width: 320px;
        }

        .filtro-usuarios input {
            width: 100%;
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
                <div class="navbar-title">Usuarios</div>
            </header>

            <!-- CONTENIDO -->
            <section class="content">
                <div class="card">
                    <h2>Alta de usuarios</h2>
                    <p>Completa todos los campos de la tabla Usuarios y, si corresponde, agrega los hijos.</p>
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

                <div id="usuarios-feedback"></div>

                <div class="card">
                    <h3>Nuevo usuario</h3>
                    <form class="form-modern" method="post" id="usuarioForm" autocomplete="off">
                        <input type="hidden" name="action" value="crear" />

                        <div class="form-grid grid-4">
                            <div class="input-group">
                                <label for="nombre">Nombre</label>
                                <div class="input-icon input-icon-name">
                                    <input type="text" id="nombre" name="nombre" required autocomplete="off"
                                        value="<?= htmlspecialchars($formData['nombre']) ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="usuario">Usuario</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="text" id="usuario" name="usuario" required autocomplete="off"
                                        value="<?= htmlspecialchars($formData['usuario']) ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="contrasena">Contrasena</label>
                                <div class="input-icon">
                                    <span class="material-icons">lock</span>
                                    <input type="password" id="contrasena" name="contrasena" required autocomplete="new-password" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="telefono_display">Telefono</label>
                                <div class="input-icon input-icon-phone">
                                    <input type="tel" id="telefono_display" name="telefono_display" inputmode="numeric" autocomplete="off"
                                        value="<?= htmlspecialchars($formData['telefono']) ?>" />
                                    <input type="hidden" id="telefono" name="telefono"
                                        value="<?= htmlspecialchars($formData['telefono']) ?>" />
                                </div>
                                <small class="gform-helper">Ingresá tu número sin 0 y sin 15 (ej: 2611234567)</small>
                            </div>

                            <div class="input-group">
                                <label for="correo">Correo</label>
                                <div class="input-icon input-icon-email">
                                    <input type="email" id="correo" name="correo" autocomplete="off"
                                        value="<?= htmlspecialchars($formData['correo']) ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="saldo">Saldo</label>
                                <div class="input-icon">
                                    <span class="material-icons">attach_money</span>
                                    <input type="number" id="saldo" name="saldo" step="0.01" min="0"
                                        value="<?= htmlspecialchars($saldoValue) ?>" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="rol">Rol</label>
                                <div class="input-icon input-icon-globe">
                                    <select id="rol" name="rol" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?= htmlspecialchars($rol) ?>" <?= $formData['rol'] === $rol ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($rol) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div id="hijos-section">
                        <div class="hijos-wrapper" id="hijos-container">
                            <?php
                            $mostrarHijos = $formData['rol'] === 'papas';
                            if ($mostrarHijos && empty($hijosForm)) {
                                $hijosForm[] = [
                                    'nombre' => '',
                                    'preferencias' => $preferenciaDefaultId ?? '',
                                    'colegio_id' => '',
                                    'curso_id' => ''
                                ];
                            }
                            ?>
                            <?php foreach ($hijosForm as $index => $hijo): ?>
                                <div class="hijo-card" data-index="<?= (int) $index ?>">
                                    <div class="hijo-header">
                                        <p class="hijo-title">Hijo <?= (int) ($index + 1) ?></p>
                                        <button type="button" class="btn btn-cancelar btn-small btn-remove-hijo">Quitar</button>
                                    </div>
                                    <div class="form-grid grid-4">
                                        <div class="input-group">
                                            <label>Nombre</label>
                                            <div class="input-icon input-icon-name">
                                                <input type="text" name="hijos_nombre[]"
                                                    value="<?= htmlspecialchars($hijo['nombre'] ?? '') ?>" />
                                            </div>
                                        </div>

                                        <div class="input-group">
                                            <label>Preferencias</label>
                                            <div class="input-icon input-icon-comment">
                                                <select name="hijos_preferencias[]" class="hijo-preferencia" data-selected="<?= htmlspecialchars((string) ($hijo['preferencias'] ?? '')) ?>">
                                                    <?= $preferenciaOptionsHtml ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="input-group">
                                            <label>Colegio</label>
                                            <div class="input-icon input-icon-globe">
                                                <select name="hijos_colegio[]" class="hijo-colegio" data-selected="<?= htmlspecialchars((string) ($hijo['colegio_id'] ?? '')) ?>">
                                                    <?= $colegioOptionsHtml ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="input-group">
                                            <label>Curso</label>
                                            <div class="input-icon input-icon-globe">
                                                <select name="hijos_curso[]" class="hijo-curso" data-selected="<?= htmlspecialchars((string) ($hijo['curso_id'] ?? '')) ?>">
                                                    <?= $cursoOptionsHtml ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <br>
                        <div class="form-buttons">
                            <button type="button" class="btn btn-info" id="add-hijo">Agregar hijo</button>
                        </div>
                    </div>

                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Guardar</button>
                        </div>
                    </form>
                </div>

                <div class="card tabla-card">
                    <div class="tabla-header">
                        <h3>Usuarios registrados</h3>
                        <div class="input-group filtro-usuarios">
                            <label for="usuarios-search">Buscar por nombre</label>
                            <div class="input-icon input-icon-name">
                                <input type="text" id="usuarios-search" name="usuarios-search" placeholder="Escribe un nombre" autocomplete="off" />
                            </div>
                        </div>
                    </div>
                    <div class="tabla-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Telefono</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Saldo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="usuarios-table-body">
                                <?php if (!empty($usuarios)): ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <?php
                                        $saldoTabla = isset($usuario['Saldo']) ? number_format((float) $usuario['Saldo'], 2, '.', '') : '0.00';
                                        $nombreWords = preg_split('/\s+/', trim((string) ($usuario['Nombre'] ?? '')));
                                        $usuarioWords = preg_split('/\s+/', trim((string) ($usuario['Usuario'] ?? '')));
                                        $nombreWrapped = implode('<br>', array_map('htmlspecialchars', array_filter($nombreWords, 'strlen')));
                                        $usuarioWrapped = implode('<br>', array_map('htmlspecialchars', array_filter($usuarioWords, 'strlen')));
                                        $estadoRaw = strtolower(trim((string) ($usuario['Estado'] ?? '')));
                                        $estadoLabel = $estadoRaw !== '' ? $estadoRaw : 'activo';
                                        $estadoClass = $estadoLabel === 'inactivo' ? 'danger' : 'success';
                                        $usuarioPayload = [
                                            'id' => $usuario['Id'] ?? '',
                                            'nombre' => $usuario['Nombre'] ?? '',
                                            'usuario' => $usuario['Usuario'] ?? '',
                                            'telefono' => $usuario['Telefono'] ?? '',
                                            'correo' => $usuario['Correo'] ?? '',
                                            'rol' => $usuario['Rol'] ?? '',
                                            'saldo' => $saldoTabla,
                                            'estado' => $estadoLabel
                                        ];
                                        $usuarioJson = htmlspecialchars(json_encode($usuarioPayload), ENT_QUOTES, 'UTF-8');
                                        $hijosJson = htmlspecialchars(json_encode($usuario['hijos'] ?? []), ENT_QUOTES, 'UTF-8');
                                        ?>
                                    <tr data-usuario-id="<?= htmlspecialchars((string) ($usuario['Id'] ?? '')) ?>">
                                        <td><?= htmlspecialchars((string) ($usuario['Id'] ?? '')) ?></td>
                                        <td class="wrap-text"><?= $nombreWrapped !== '' ? $nombreWrapped : htmlspecialchars((string) ($usuario['Nombre'] ?? '')) ?></td>
                                        <td class="wrap-text"><?= $usuarioWrapped !== '' ? $usuarioWrapped : htmlspecialchars((string) ($usuario['Usuario'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($usuario['Telefono'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($usuario['Correo'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($usuario['Rol'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars($saldoTabla) ?></td>
                                        <td><span class="badge <?= htmlspecialchars($estadoClass) ?>"><?= htmlspecialchars($estadoLabel) ?></span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="action-btn action-toggle" data-usuario="<?= $usuarioJson ?>" aria-label="Cambiar estado">
                                                    <span class="material-icons" style="color: <?= $estadoLabel === 'inactivo' ? '#dc2626' : '#16a34a'; ?>;">
                                                        <?= $estadoLabel === 'inactivo' ? 'toggle_off' : 'toggle_on'; ?>
                                                    </span>
                                                </button>
                                                <button type="button" class="action-btn action-edit" data-usuario="<?= $usuarioJson ?>" data-hijos="<?= $hijosJson ?>">
                                                    <span class="material-icons" style="color: #2563eb;">edit</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9">No hay usuarios cargados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="modal hidden" id="modal-editar">
        <div class="modal-content">
            <h3>Editar usuario</h3>
            <form class="form-modern" id="editUsuarioForm" autocomplete="off" onsubmit="return false;">
                <input type="hidden" id="edit_id" name="edit_id" />
                <div class="form-grid grid-4">
                    <div class="input-group">
                        <label for="edit_nombre">Nombre</label>
                        <div class="input-icon input-icon-name">
                            <input type="text" id="edit_nombre" name="edit_nombre" />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_usuario">Usuario</label>
                        <div class="input-icon">
                            <span class="material-icons">person</span>
                            <input type="text" id="edit_usuario" name="edit_usuario" />
                        </div>
                    </div>

                    <div class="input-group" style="display: none;">
                        <label for="edit_contrasena">Contrasena</label>
                        <div class="input-icon">
                            <span class="material-icons">lock</span>
                            <input type="password" id="edit_contrasena" name="edit_contrasena" autocomplete="new-password" />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_telefono">Telefono</label>
                        <div class="input-icon input-icon-phone">
                            <input type="tel" id="edit_telefono" name="edit_telefono" inputmode="numeric" />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_correo">Correo</label>
                        <div class="input-icon input-icon-email">
                            <input type="email" id="edit_correo" name="edit_correo" />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_saldo">Saldo</label>
                        <div class="input-icon">
                            <span class="material-icons">attach_money</span>
                            <input type="number" id="edit_saldo" name="edit_saldo" step="0.01" min="0" />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_rol">Rol</label>
                        <div class="input-icon input-icon-globe">
                            <select id="edit_rol" name="edit_rol">
                                <option value="">Seleccionar</option>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= htmlspecialchars($rol) ?>"><?= htmlspecialchars($rol) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="edit-hijos-section">
                    <div class="hijos-wrapper" id="edit-hijos-container"></div>
                    <br>
                    <div class="form-buttons">
                        <button type="button" class="btn btn-info" id="edit-add-hijo">Agregar hijo</button>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn btn-cancelar" data-close-modal>Cancelar</button>
                    <button type="submit" class="btn btn-aceptar">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const rolSelect = document.getElementById('rol');
        const nombreInput = document.getElementById('nombre');
        const usuarioInput = document.getElementById('usuario');
        const telefonoInput = document.getElementById('telefono');
        const telefonoDisplayInput = document.getElementById('telefono_display');
        const hijosSection = document.getElementById('hijos-section');
        const hijosContainer = document.getElementById('hijos-container');
        const addHijoButton = document.getElementById('add-hijo');

        const colegioOptionsHtml = <?= json_encode($colegioOptionsHtml) ?>;
        const cursoOptionsHtml = <?= json_encode($cursoOptionsHtml) ?>;
        const preferenciaOptionsHtml = <?= json_encode($preferenciaOptionsHtml) ?>;
        const defaultPreferenciaId = <?= json_encode($preferenciaDefaultId ?? '') ?>;

        let autoUsuario = true;
        let lastAutoUsuario = '';

        const toggleHijosSection = () => {
            if (!hijosSection) return;
            hijosSection.style.display = rolSelect && rolSelect.value === 'papas' ? 'block' : 'none';
        };

        const syncCursoOptions = (select, colegioId) => {
            if (!select) return;
            const selectedValue = select.value;
            Array.from(select.options).forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }
                const optionColegio = option.dataset.colegio || '';
                if (!colegioId || optionColegio === colegioId) {
                    option.hidden = false;
                } else {
                    option.hidden = true;
                }
            });

            if (selectedValue) {
                const selectedOption = Array.from(select.options).find((option) => option.value === selectedValue);
                if (selectedOption && selectedOption.hidden) {
                    select.value = '';
                }
            }
        };

        const bindRow = (row) => {
            if (!row) return;
            const colegioSelect = row.querySelector('.hijo-colegio');
            const cursoSelect = row.querySelector('.hijo-curso');
            const removeButton = row.querySelector('.btn-remove-hijo');

            if (colegioSelect && cursoSelect) {
                if (colegioSelect.dataset.selected) {
                    colegioSelect.value = colegioSelect.dataset.selected;
                }
                if (cursoSelect.dataset.selected) {
                    cursoSelect.value = cursoSelect.dataset.selected;
                }
                syncCursoOptions(cursoSelect, colegioSelect.value);
                colegioSelect.addEventListener('change', () => {
                    syncCursoOptions(cursoSelect, colegioSelect.value);
                });
            }

            const preferenciaSelect = row.querySelector('.hijo-preferencia');
            if (preferenciaSelect) {
                if (preferenciaSelect.dataset.selected) {
                    preferenciaSelect.value = preferenciaSelect.dataset.selected;
                } else if (defaultPreferenciaId) {
                    preferenciaSelect.value = defaultPreferenciaId;
                }
            }

            if (removeButton) {
                removeButton.addEventListener('click', () => {
                    row.remove();
                    updateHijoTitles();
                });
            }
        };

        const createHijoRow = () => {
            const row = document.createElement('div');
            row.className = 'hijo-card';
            row.innerHTML = `
                <div class="hijo-header">
                    <p class="hijo-title">Hijo</p>
                    <button type="button" class="btn btn-cancelar btn-small btn-remove-hijo">Quitar</button>
                </div>
                <div class="form-grid grid-4">
                    <div class="input-group">
                        <label>Nombre</label>
                        <div class="input-icon input-icon-name">
                            <input type="text" name="hijos_nombre[]" />
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Preferencias</label>
                        <div class="input-icon input-icon-comment">
                            <select name="hijos_preferencias[]" class="hijo-preferencia">${preferenciaOptionsHtml}</select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Colegio</label>
                        <div class="input-icon input-icon-globe">
                            <select name="hijos_colegio[]" class="hijo-colegio">${colegioOptionsHtml}</select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Curso</label>
                        <div class="input-icon input-icon-globe">
                            <select name="hijos_curso[]" class="hijo-curso">${cursoOptionsHtml}</select>
                        </div>
                    </div>
                </div>
            `;
            return row;
        };

        const updateHijoTitles = () => {
            if (!hijosContainer) return;
            const rows = Array.from(hijosContainer.querySelectorAll('.hijo-card'));
            rows.forEach((row, index) => {
                const title = row.querySelector('.hijo-title');
                if (title) {
                    title.textContent = `Hijo ${index + 1}`;
                }
            });
            if (addHijoButton) {
                addHijoButton.disabled = rows.length >= 20;
            }
        };

        const formatTelefono = (digits) => {
            if (!digits) return '';
            const clean = digits.replace(/\D+/g, '');
            const parts = [];
            parts.push(clean.slice(0, 3));
            if (clean.length > 3) parts.push(clean.slice(3, 6));
            if (clean.length > 6) parts.push(clean.slice(6, 10));
            if (clean.length > 10) parts.push(clean.slice(10));
            return parts.filter(Boolean).join('-');
        };

        const syncTelefono = () => {
            if (!telefonoInput || !telefonoDisplayInput) return;
            const digits = telefonoDisplayInput.value.replace(/\D+/g, '');
            telefonoInput.value = digits;
            telefonoDisplayInput.value = formatTelefono(digits);
        };

        if (telefonoDisplayInput) {
            telefonoDisplayInput.addEventListener('input', syncTelefono);
        }

        const usuarioForm = document.getElementById('usuarioForm');
        if (usuarioForm) {
            usuarioForm.addEventListener('submit', () => {
                syncTelefono();
            });
        }

        if (nombreInput && usuarioInput) {
            nombreInput.addEventListener('input', () => {
                if (autoUsuario) {
                    usuarioInput.value = nombreInput.value;
                    lastAutoUsuario = nombreInput.value;
                }
            });
            usuarioInput.addEventListener('input', () => {
                if (usuarioInput.value.trim() === '' || usuarioInput.value === nombreInput.value || usuarioInput.value === lastAutoUsuario) {
                    autoUsuario = true;
                } else {
                    autoUsuario = false;
                }
            });
        }

        if (rolSelect) {
            rolSelect.addEventListener('change', toggleHijosSection);
        }

        if (addHijoButton && hijosContainer) {
            addHijoButton.addEventListener('click', () => {
                const row = createHijoRow();
                hijosContainer.appendChild(row);
                bindRow(row);
                updateHijoTitles();
            });
        }

        if (hijosContainer) {
            Array.from(hijosContainer.children).forEach((row) => {
                bindRow(row);
            });
        }

        if (telefonoDisplayInput) {
            syncTelefono();
        }

        toggleHijosSection();
        updateHijoTitles();

        const editModal = document.getElementById('modal-editar');
        const editForm = document.getElementById('editUsuarioForm');
        const editIdInput = document.getElementById('edit_id');
        const editNombreInput = document.getElementById('edit_nombre');
        const editUsuarioInput = document.getElementById('edit_usuario');
        const editContrasenaInput = document.getElementById('edit_contrasena');
        const editTelefonoInput = document.getElementById('edit_telefono');
        const editCorreoInput = document.getElementById('edit_correo');
        const editSaldoInput = document.getElementById('edit_saldo');
        const editRolSelect = document.getElementById('edit_rol');
        const editHijosSection = document.getElementById('edit-hijos-section');
        const editHijosContainer = document.getElementById('edit-hijos-container');
        const editAddHijoButton = document.getElementById('edit-add-hijo');
        const usuariosTableBody = document.getElementById('usuarios-table-body');
        const feedbackContainer = document.getElementById('usuarios-feedback');
        const usuariosSearchInput = document.getElementById('usuarios-search');

        const toggleEditHijosSection = () => {
            if (!editHijosSection) return;
            editHijosSection.style.display = editRolSelect && editRolSelect.value === 'papas' ? 'block' : 'none';
        };

        const openAdminModal = (modal) => {
            if (!modal) return;
            modal.classList.remove('hidden');
        };

        const closeAdminModal = (modal) => {
            if (!modal) return;
            modal.classList.add('hidden');
        };

        document.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                closeAdminModal(editModal);
            });
        });

        const parseJson = (raw) => {
            if (!raw) return null;
            try {
                return JSON.parse(raw);
            } catch (error) {
                return null;
            }
        };

        const escapeHtml = (value) => {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        const renderFeedback = (type, messages) => {
            const messageArray = Array.isArray(messages) ? messages : [messages];
            const messageText = messageArray.filter(Boolean).join(' ');

            if (typeof window.showAlert === 'function' && messageText) {
                window.showAlert(type, messageText);
                return;
            }

            if (!feedbackContainer) return;
            if (!messageText) {
                feedbackContainer.innerHTML = '';
                return;
            }
            const borderColor = type === 'success' ? '#16a34a' : '#dc2626';
            const title = type === 'success' ? 'Listo:' : 'Hubo un problema:';
            const listHtml = messageArray.length > 1
                ? `<ul>${messageArray.map((msg) => `<li>${escapeHtml(msg)}</li>`).join('')}</ul>`
                : `<p>${escapeHtml(messageArray[0])}</p>`;
            feedbackContainer.innerHTML = `
                <div class="card" style="border-left: 4px solid ${borderColor};">
                    <p><strong>${title}</strong></p>
                    ${listHtml}
                </div>
            `;
        };

        const appendWrappedWords = (cell, text) => {
            const words = String(text || '').trim().split(/\s+/).filter(Boolean);
            if (words.length === 0) {
                cell.textContent = '';
                return;
            }
            words.forEach((word, index) => {
                if (index > 0) {
                    cell.appendChild(document.createElement('br'));
                }
                cell.appendChild(document.createTextNode(word));
            });
        };

        const updateEstadoUI = (row, usuarioData) => {
            if (!row || !usuarioData) return;
            const estadoLabel = usuarioData.estado === 'inactivo' ? 'inactivo' : 'activo';
            const estadoClass = estadoLabel === 'inactivo' ? 'danger' : 'success';
            const badge = row.querySelector('.badge');
            if (badge) {
                badge.classList.remove('success', 'danger');
                badge.classList.add(estadoClass);
                badge.textContent = estadoLabel;
            }

            const toggleButton = row.querySelector('.action-toggle');
            if (toggleButton) {
                const icon = toggleButton.querySelector('.material-icons');
                if (icon) {
                    icon.textContent = estadoLabel === 'inactivo' ? 'toggle_off' : 'toggle_on';
                    icon.style.color = estadoLabel === 'inactivo' ? '#dc2626' : '#16a34a';
                }
                toggleButton.dataset.usuario = JSON.stringify(usuarioData);
            }

            const editButton = row.querySelector('.action-edit');
            if (editButton) {
                editButton.dataset.usuario = JSON.stringify(usuarioData);
            }
        };

        const bindToggleButton = (button, row) => {
            if (!button) return;
            button.addEventListener('click', () => {
                const usuario = parseJson(button.dataset.usuario);
                if (!usuario || !usuario.id) return;

                const estadoActual = usuario.estado === 'inactivo' ? 'inactivo' : 'activo';
                const nuevoEstado = estadoActual === 'inactivo' ? 'activo' : 'inactivo';

                const formData = new FormData();
                formData.append('action', 'toggle_estado');
                formData.append('usuario_id', String(usuario.id));
                formData.append('estado', nuevoEstado);
                formData.append('ajax', '1');

                button.disabled = true;
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
                            usuario.estado = data.estado || nuevoEstado;
                            updateEstadoUI(row, usuario);
                            renderFeedback('success', data.mensaje || 'Usuario actualizado correctamente.');
                        } else {
                            renderFeedback('error', data.errores || 'No se pudo actualizar el usuario.');
                        }
                    })
                    .catch(() => {
                        renderFeedback('error', 'No se pudo actualizar el usuario.');
                    })
                    .finally(() => {
                        button.disabled = false;
                    });
            });
        };

        const bindEditButton = (button) => {
            if (!button) return;
            button.addEventListener('click', () => {
                const usuario = parseJson(button.dataset.usuario) || {};
                const hijos = parseJson(button.dataset.hijos) || [];

                if (editIdInput) editIdInput.value = usuario.id || '';
                if (editNombreInput) editNombreInput.value = usuario.nombre || '';
                if (editUsuarioInput) editUsuarioInput.value = usuario.usuario || '';
                if (editContrasenaInput) editContrasenaInput.value = '';
                if (editTelefonoInput) editTelefonoInput.value = usuario.telefono || '';
                if (editCorreoInput) editCorreoInput.value = usuario.correo || '';
                if (editSaldoInput) editSaldoInput.value = usuario.saldo || '0.00';
                if (editRolSelect) editRolSelect.value = usuario.rol || '';

                toggleEditHijosSection();
                if (editRolSelect && editRolSelect.value === 'papas') {
                    renderEditHijos(hijos);
                } else if (editHijosContainer) {
                    editHijosContainer.innerHTML = '';
                }

                openAdminModal(editModal);
            });
        };

        const buildUsuarioRow = (usuarioData, hijosData) => {
            if (!usuariosTableBody || !usuarioData) return null;
            const row = document.createElement('tr');
            row.dataset.usuarioId = usuarioData.id || '';
            const estadoLabel = usuarioData.estado === 'inactivo' ? 'inactivo' : 'activo';
            const estadoClass = estadoLabel === 'inactivo' ? 'danger' : 'success';
            row.innerHTML = `
                <td></td>
                <td class="wrap-text"></td>
                <td class="wrap-text"></td>
                <td>${escapeHtml(usuarioData.telefono || '')}</td>
                <td>${escapeHtml(usuarioData.correo || '')}</td>
                <td>${escapeHtml(usuarioData.rol || '')}</td>
                <td>${escapeHtml(usuarioData.saldo || '0.00')}</td>
                <td><span class="badge ${estadoClass}">${escapeHtml(estadoLabel)}</span></td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="action-btn action-toggle" aria-label="Cambiar estado">
                            <span class="material-icons" style="color: ${estadoLabel === 'inactivo' ? '#dc2626' : '#16a34a'};">
                                ${estadoLabel === 'inactivo' ? 'toggle_off' : 'toggle_on'}
                            </span>
                        </button>
                        <button type="button" class="action-btn action-edit">
                            <span class="material-icons" style="color: #2563eb;">edit</span>
                        </button>
                    </div>
                </td>
            `;

            const cells = row.querySelectorAll('td');
            if (cells[0]) cells[0].textContent = usuarioData.id || '';
            if (cells[1]) appendWrappedWords(cells[1], usuarioData.nombre || '');
            if (cells[2]) appendWrappedWords(cells[2], usuarioData.usuario || '');

            const toggleButton = row.querySelector('.action-toggle');
            const editButton = row.querySelector('.action-edit');
            if (toggleButton) {
                toggleButton.dataset.usuario = JSON.stringify(usuarioData);
                bindToggleButton(toggleButton, row);
            }
            if (editButton) {
                editButton.dataset.usuario = JSON.stringify(usuarioData);
                editButton.dataset.hijos = JSON.stringify(hijosData || []);
                bindEditButton(editButton);
            }
            return row;
        };

        const renderUsuariosTable = (usuarios) => {
            if (!usuariosTableBody) return;
            usuariosTableBody.innerHTML = '';
            if (!Array.isArray(usuarios) || usuarios.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="9">No hay usuarios cargados.</td>';
                usuariosTableBody.appendChild(emptyRow);
                return;
            }
            usuarios.forEach((item) => {
                const row = buildUsuarioRow(item.usuario, item.hijos);
                if (row) {
                    usuariosTableBody.appendChild(row);
                }
            });
        };

        const replaceUsuarioRow = (usuarioData, hijosData) => {
            if (!usuariosTableBody || !usuarioData) return;
            const newRow = buildUsuarioRow(usuarioData, hijosData);
            if (!newRow) return;
            const existingRow = usuariosTableBody.querySelector(`tr[data-usuario-id="${usuarioData.id}"]`);
            if (existingRow) {
                existingRow.replaceWith(newRow);
                return;
            }
            const emptyRow = usuariosTableBody.querySelector('tr td[colspan]');
            if (emptyRow) {
                emptyRow.closest('tr').remove();
            }
            usuariosTableBody.prepend(newRow);
        };

        const updateEditHijoTitles = () => {
            if (!editHijosContainer) return;
            const rows = Array.from(editHijosContainer.querySelectorAll('.hijo-card'));
            rows.forEach((row, index) => {
                const title = row.querySelector('.hijo-title');
                if (title) {
                    title.textContent = `Hijo ${index + 1}`;
                }
            });
            if (editAddHijoButton) {
                editAddHijoButton.disabled = rows.length >= 20;
            }
        };

        const createEditHijoRow = (hijo = {}) => {
            const row = document.createElement('div');
            row.className = 'hijo-card';
            row.innerHTML = `
                <div class="hijo-header">
                    <p class="hijo-title">Hijo</p>
                    <button type="button" class="btn btn-cancelar btn-small btn-remove-hijo">Quitar</button>
                </div>
                <div class="form-grid grid-4">
                    <div class="input-group">
                        <label>Nombre</label>
                        <div class="input-icon input-icon-name">
                            <input type="text" name="edit_hijos_nombre[]" />
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Preferencias</label>
                        <div class="input-icon input-icon-comment">
                            <select name="edit_hijos_preferencias[]" class="edit-hijo-preferencia">${preferenciaOptionsHtml}</select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Colegio</label>
                        <div class="input-icon input-icon-globe">
                            <select name="edit_hijos_colegio[]" class="edit-hijo-colegio">${colegioOptionsHtml}</select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Curso</label>
                        <div class="input-icon input-icon-globe">
                            <select name="edit_hijos_curso[]" class="edit-hijo-curso">${cursoOptionsHtml}</select>
                        </div>
                    </div>
                </div>
            `;

            const nombreInput = row.querySelector('input[name="edit_hijos_nombre[]"]');
            if (nombreInput) {
                nombreInput.value = hijo.nombre || '';
            }

            const preferenciaSelect = row.querySelector('.edit-hijo-preferencia');
            if (preferenciaSelect) {
                if (hijo.preferencias) {
                    preferenciaSelect.value = String(hijo.preferencias);
                } else if (defaultPreferenciaId) {
                    preferenciaSelect.value = defaultPreferenciaId;
                }
            }

            const colegioSelect = row.querySelector('.edit-hijo-colegio');
            const cursoSelect = row.querySelector('.edit-hijo-curso');
            if (colegioSelect) {
                colegioSelect.value = hijo.colegio_id ? String(hijo.colegio_id) : '';
            }
            if (cursoSelect) {
                cursoSelect.value = hijo.curso_id ? String(hijo.curso_id) : '';
            }
            if (colegioSelect && cursoSelect) {
                syncCursoOptions(cursoSelect, colegioSelect.value);
                colegioSelect.addEventListener('change', () => {
                    syncCursoOptions(cursoSelect, colegioSelect.value);
                });
            }

            const removeButton = row.querySelector('.btn-remove-hijo');
            if (removeButton) {
                removeButton.addEventListener('click', () => {
                    row.remove();
                    updateEditHijoTitles();
                });
            }

            return row;
        };

        const renderEditHijos = (hijos = []) => {
            if (!editHijosContainer) return;
            editHijosContainer.innerHTML = '';
            if (!Array.isArray(hijos) || hijos.length === 0) {
                editHijosContainer.appendChild(createEditHijoRow({}));
                updateEditHijoTitles();
                return;
            }
            hijos.forEach((hijo) => {
                editHijosContainer.appendChild(createEditHijoRow(hijo));
            });
            updateEditHijoTitles();
        };

        if (editAddHijoButton && editHijosContainer) {
            editAddHijoButton.addEventListener('click', () => {
                editHijosContainer.appendChild(createEditHijoRow({}));
                updateEditHijoTitles();
            });
        }

        if (editRolSelect) {
            editRolSelect.addEventListener('change', () => {
                toggleEditHijosSection();
            });
        }

        document.querySelectorAll('.action-toggle').forEach((button) => {
            bindToggleButton(button, button.closest('tr'));
        });

        document.querySelectorAll('.action-edit').forEach((button) => {
            bindEditButton(button);
        });

        if (editForm) {
            editForm.addEventListener('reset', () => {
                if (editHijosContainer) {
                    editHijosContainer.innerHTML = '';
                }
                toggleEditHijosSection();
            });
        }

        if (editForm) {
            editForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const formData = new FormData(editForm);
                formData.append('action', 'editar');
                formData.append('ajax', '1');
                if (editIdInput && !formData.get('edit_id')) {
                    formData.append('edit_id', editIdInput.value);
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
                        if (data.ok && data.usuario) {
                            replaceUsuarioRow(data.usuario, data.hijos || []);
                            closeAdminModal(editModal);
                            renderFeedback('success', data.mensaje || 'Usuario actualizado correctamente.');
                        } else {
                            renderFeedback('error', data.errores || 'No se pudo actualizar el usuario.');
                        }
                    })
                    .catch(() => {
                        renderFeedback('error', 'No se pudo actualizar el usuario.');
                    });
            });
        }

        if (usuarioForm && usuariosTableBody) {
            usuarioForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const formData = new FormData(usuarioForm);
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
                        if (data.ok && data.usuario) {
                            const row = buildUsuarioRow(data.usuario, data.hijos);
                            if (row) {
                                const emptyRow = usuariosTableBody.querySelector('tr td[colspan]');
                                if (emptyRow) {
                                    emptyRow.closest('tr').remove();
                                }
                                usuariosTableBody.prepend(row);
                            }
                            usuarioForm.reset();
                            if (hijosContainer) {
                                hijosContainer.innerHTML = '';
                            }
                            toggleHijosSection();
                            updateHijoTitles();
                            renderFeedback('success', data.mensaje || 'Usuario creado correctamente.');
                            if (data.mail_error) {
                                console.error('Error envio correo bienvenida:', data.mail_error);
                            }
                            if (data.mensaje) {
                                console.error('Mensaje servidor (alta usuario):', data.mensaje);
                            }
                        } else {
                            renderFeedback('error', data.errores || 'No se pudo crear el usuario.');
                        }
                    })
                    .catch(() => {
                        renderFeedback('error', 'No se pudo crear el usuario.');
                    });
            });
        }

        if (usuariosSearchInput) {
            let searchTimeout = null;
            let lastSearchValue = '';

            const fetchUsuarios = (termino) => {
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
                            renderUsuariosTable(data.usuarios || []);
                        } else {
                            renderFeedback('error', data.errores || 'No se pudo filtrar los usuarios.');
                        }
                    })
                    .catch(() => {
                        renderFeedback('error', 'No se pudo filtrar los usuarios.');
                    });
            };

            usuariosSearchInput.addEventListener('input', () => {
                const termino = usuariosSearchInput.value.trim();
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                if (termino.length >= 3 || (termino.length === 0 && lastSearchValue.length >= 3)) {
                    searchTimeout = setTimeout(() => {
                        fetchUsuarios(termino);
                        lastSearchValue = termino;
                    }, 300);
                    return;
                }
                if (termino.length === 0) {
                    lastSearchValue = '';
                }
            });
        }
    </script>
</body>

</html>
