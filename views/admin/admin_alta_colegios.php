<?php
require_once __DIR__ . '/../../controllers/admin_alta_colegiosController.php';
?>

<style>
    #modal-alta-colegios .modal-content {
        width: 80%;
        max-height: 80vh;
        overflow-y: auto;
        max-width: 1000px;
    }
</style>

<div id="modal-alta-colegios" class="modal hidden" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <h3>Dar de alta colegios</h3>
                <p style="margin: 4px 0 0; color: #6b7280;">Gestiona colegios, cursos y representantes.</p>
            </div>
            <button class="btn-icon" type="button" data-colegios-modal="close" aria-label="Cerrar">
                <span class="material-icons">close</span>
            </button>
        </div>

        <div style="margin-top: 16px; display: grid; gap: 16px;">
            <div class="card" style="padding: 16px;">
                <h4 style="margin-top: 0;">Crear colegio</h4>
                <form class="form-modern" id="colegioForm">
                    <div class="form-grid grid-3">
                        <div class="input-group">
                            <label>Nombre</label>
                            <div class="input-icon">
                                <span class="material-icons">school</span>
                                <input type="text" name="nombre" maxlength="100" required />
                            </div>
                        </div>
                        <div class="input-group" style="grid-column: span 2;">
                            <label>Direccion</label>
                            <div class="input-icon">
                                <span class="material-icons">location_on</span>
                                <input type="text" name="direccion" maxlength="255" />
                            </div>
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn btn-aceptar" type="submit">Guardar colegio</button>
                    </div>
                </form>
            </div>

            <div class="card" style="padding: 16px;">
                <h4 style="margin-top: 0;">Crear curso</h4>
                <form class="form-modern" id="cursoForm">
                    <div class="form-grid grid-3">
                        <div class="input-group">
                            <label>Nombre</label>
                            <div class="input-icon">
                                <span class="material-icons">menu_book</span>
                                <input type="text" name="nombre" maxlength="100" required />
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Colegio</label>
                            <div class="input-icon input-icon-globe">
                                <select name="colegio_id" id="cursoColegioSelect" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($colegiosSelect ?? [] as $colegio): ?>
                                        <option value="<?= (int) ($colegio['Id'] ?? 0) ?>">
                                            <?= htmlspecialchars((string) ($colegio['Nombre'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Nivel educativo</label>
                            <div class="input-icon input-icon-globe">
                                <select name="nivel_educativo" required>
                                    <option value="">Seleccionar</option>
                                    <option value="Inicial">Inicial</option>
                                    <option value="Primaria">Primaria</option>
                                    <option value="Secundaria">Secundaria</option>
                                    <option value="Sin Curso Asignado">Sin Curso Asignado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn btn-aceptar" type="submit">Guardar curso</button>
                    </div>
                </form>
            </div>

            <div class="card" style="padding: 16px;">
                <h4 style="margin-top: 0;">Asignar representante</h4>
                <form class="form-modern" id="representanteForm">
                    <div class="form-grid grid-3">
                        <div class="input-group">
                            <label>Colegio</label>
                            <div class="input-icon input-icon-globe">
                                <select name="colegio_id" id="representanteColegioSelect" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($colegiosSelect ?? [] as $colegio): ?>
                                        <option value="<?= (int) ($colegio['Id'] ?? 0) ?>">
                                            <?= htmlspecialchars((string) ($colegio['Nombre'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="input-group" style="grid-column: span 2;">
                            <label>Representante</label>
                            <div class="input-icon input-icon-globe">
                                <select name="representante_id" id="representanteSelect" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($representantes ?? [] as $rep): ?>
                                        <option value="<?= (int) ($rep['Id'] ?? 0) ?>">
                                            <?= htmlspecialchars((string) ($rep['Nombre'] ?? '')) ?>
                                            (<?= htmlspecialchars((string) ($rep['Usuario'] ?? '')) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn btn-aceptar" type="submit">Asignar representante</button>
                    </div>
                </form>
            </div>

            <div class="card" style="padding: 16px;">
                <div class="card-header">
                    <h4 class="card-title">Colegios</h4>
                </div>
                <div class="tabla-wrapper">
                    <table class="data-table" id="colegiosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Colegio</th>
                                <th>Direccion</th>
                                <th>Representante</th>
                            </tr>
                        </thead>
                        <tbody id="colegiosTableBody">
                            <?php if (!empty($colegios)): ?>
                                <?php foreach ($colegios as $colegio): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($colegio['Id'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($colegio['Nombre'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($colegio['Direccion'] ?? '')) ?></td>
                                        <td>
                                            <?php if (!empty($colegio['Representantes_Nombres'])): ?>
                                                <?php
                                                $nombres = (string) $colegio['Representantes_Nombres'];
                                                $tooltip = trim((string) ($colegio['Representantes_Detalle'] ?? ''));
                                                ?>
                                                <span title="<?= htmlspecialchars($tooltip) ?>">
                                                    <?= nl2br(htmlspecialchars($nombres)) ?>
                                                </span>
                                            <?php else: ?>
                                                Sin asignar
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">Sin colegios cargados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="padding: 16px;">
                <div class="card-header">
                    <h4 class="card-title">Cursos</h4>
                </div>
                <div class="tabla-wrapper">
                    <table class="data-table" id="cursosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Curso</th>
                                <th>Nivel</th>
                                <th>Colegio</th>
                            </tr>
                        </thead>
                        <tbody id="cursosTableBody">
                            <?php if (!empty($cursos)): ?>
                                <?php foreach ($cursos as $curso): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($curso['Id'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($curso['Nombre'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($curso['Nivel_Educativo'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($curso['Colegio_Nombre'] ?? 'Sin colegio')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">Sin cursos cargados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="form-buttons">
            <button class="btn btn-cancelar" type="button" data-colegios-modal="close">Cerrar</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('modal-alta-colegios');
        if (!modal) return;

        const endpoint = '../../controllers/admin_alta_colegiosController.php';
        const colegioForm = document.getElementById('colegioForm');
        const cursoForm = document.getElementById('cursoForm');
        const representanteForm = document.getElementById('representanteForm');
        const colegiosTableBody = document.getElementById('colegiosTableBody');
        const cursosTableBody = document.getElementById('cursosTableBody');
        const cursoColegioSelect = document.getElementById('cursoColegioSelect');
        const representanteColegioSelect = document.getElementById('representanteColegioSelect');
        const representanteSelect = document.getElementById('representanteSelect');

        const notify = (type, message) => {
            if (typeof window.showAlert === 'function') {
                window.showAlert(type, message);
                return;
            }
            alert(message);
        };

        const openModal = () => {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        };

        const escapeHtml = (value) => {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        const renderColegios = (items) => {
            if (!colegiosTableBody) return;
            if (!items || items.length === 0) {
                colegiosTableBody.innerHTML = '<tr><td colspan="4">Sin colegios cargados.</td></tr>';
                return;
            }
            colegiosTableBody.innerHTML = items.map((item) => {
                const representante = item.Representantes_Nombres ? escapeHtml(item.Representantes_Nombres) : 'Sin asignar';
                const tooltip = item.Representantes_Detalle ? escapeHtml(item.Representantes_Detalle) : '';
                return `
                    <tr>
                        <td>${escapeHtml(item.Id)}</td>
                        <td>${escapeHtml(item.Nombre)}</td>
                        <td>${escapeHtml(item.Direccion || '')}</td>
                        <td><span title="${tooltip}">${representante.replace(/\n/g, '<br>')}</span></td>
                    </tr>
                `;
            }).join('');
        };

        const renderCursos = (items) => {
            if (!cursosTableBody) return;
            if (!items || items.length === 0) {
                cursosTableBody.innerHTML = '<tr><td colspan="4">Sin cursos cargados.</td></tr>';
                return;
            }
            cursosTableBody.innerHTML = items.map((item) => {
                return `
                    <tr>
                        <td>${escapeHtml(item.Id)}</td>
                        <td>${escapeHtml(item.Nombre)}</td>
                        <td>${escapeHtml(item.Nivel_Educativo || '')}</td>
                        <td>${escapeHtml(item.Colegio_Nombre || 'Sin colegio')}</td>
                    </tr>
                `;
            }).join('');
        };

        const syncColegiosSelects = (items) => {
            const selects = [cursoColegioSelect, representanteColegioSelect].filter(Boolean);
            if (selects.length === 0) return;
            selects.forEach((select) => {
                const currentValue = select.value;
                select.innerHTML = '<option value="">Seleccionar</option>' + (items || []).map((item) => {
                    return `<option value="${escapeHtml(item.Id)}">${escapeHtml(item.Nombre)}</option>`;
                }).join('');
                if (currentValue) {
                    select.value = currentValue;
                }
            });
        };

        const syncRepresentantesSelect = (items) => {
            if (!representanteSelect) return;
            const currentValue = representanteSelect.value;
            representanteSelect.innerHTML = '<option value="">Seleccionar</option>' + (items || []).map((item) => {
                const label = `${item.Nombre || ''}${item.Usuario ? ' (' + item.Usuario + ')' : ''}`;
                return `<option value="${escapeHtml(item.Id)}">${escapeHtml(label)}</option>`;
            }).join('');
            if (currentValue) {
                representanteSelect.value = currentValue;
            }
        };

        const fetchColegios = async () => {
            const response = await fetch(`${endpoint}?action=list_colegios&ajax=1`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.ok) {
                renderColegios(data.items);
                syncColegiosSelects(data.items);
            }
        };

        const fetchCursos = async () => {
            const response = await fetch(`${endpoint}?action=list_cursos&ajax=1`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.ok) {
                renderCursos(data.items);
            }
        };

        const fetchRepresentantes = async () => {
            const response = await fetch(`${endpoint}?action=list_representantes&ajax=1`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.ok) {
                syncRepresentantesSelect(data.items);
            }
        };

        if (colegioForm) {
            colegioForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(colegioForm);
                formData.set('action', 'crear_colegio');
                formData.set('ajax', '1');

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.ok) {
                        notify('success', data.mensaje || 'Colegio creado correctamente.');
                        colegioForm.reset();
                        await fetchColegios();
                    } else {
                        notify('error', (data.errores || []).join(' ') || data.mensaje || 'No se pudo crear el colegio.');
                    }
                } catch (error) {
                    notify('error', 'No se pudo crear el colegio.');
                }
            });
        }

        if (cursoForm) {
            cursoForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(cursoForm);
                formData.set('action', 'crear_curso');
                formData.set('ajax', '1');

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.ok) {
                        notify('success', data.mensaje || 'Curso creado correctamente.');
                        cursoForm.reset();
                        await fetchCursos();
                    } else {
                        notify('error', (data.errores || []).join(' ') || data.mensaje || 'No se pudo crear el curso.');
                    }
                } catch (error) {
                    notify('error', 'No se pudo crear el curso.');
                }
            });
        }

        if (representanteForm) {
            representanteForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(representanteForm);
                formData.set('action', 'asignar_representante');
                formData.set('ajax', '1');

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.ok) {
                        notify('success', data.mensaje || 'Representante asignado correctamente.');
                        await fetchColegios();
                    } else {
                        notify('error', (data.errores || []).join(' ') || data.mensaje || 'No se pudo asignar el representante.');
                    }
                } catch (error) {
                    notify('error', 'No se pudo asignar el representante.');
                }
            });
        }

        document.addEventListener('click', (event) => {
            const openTrigger = event.target.closest('[data-colegios-modal="open"]');
            if (openTrigger) {
                event.preventDefault();
                openModal();
                return;
            }

            const closeTrigger = event.target.closest('[data-colegios-modal="close"]');
            if (closeTrigger) {
                event.preventDefault();
                closeModal();
                return;
            }

            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        fetchRepresentantes();
    })();
</script>
