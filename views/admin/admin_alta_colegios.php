<?php
require_once __DIR__ . '/../../controllers/admin_alta_colegiosController.php';
?>

<div id="modal-alta-colegios" class="modal hidden" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <h3>Dar de alta colegios</h3>
                <p style="margin: 4px 0 0; color: #6b7280;">Modulo en construccion.</p>
            </div>
            <button class="btn-icon" type="button" data-colegios-modal="close" aria-label="Cerrar">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div style="margin-top: 12px;">
            <p>Pronto vas a poder cargar colegios desde este panel.</p>
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

        const openModal = () => {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        };

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
    })();
</script>
