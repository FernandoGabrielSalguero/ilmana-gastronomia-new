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
                    <li onclick="location.href='admin_usuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">people</span><span class="link-text">Usuarios</span>
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
                                    'preferencias' => '',
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
            </section>
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
            if (preferenciaSelect && preferenciaSelect.dataset.selected) {
                preferenciaSelect.value = preferenciaSelect.dataset.selected;
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
    </script>
</body>

</html>
