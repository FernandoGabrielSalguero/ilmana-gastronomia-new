<?php
// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controllers/papa_dashboardController.php';

// âš ï¸ ExpiraciÃ³n por inactividad (20 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: /index.php?expired=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time(); // Actualiza el tiempo de actividad



// ðŸ§¿ Control de sesiÃ³n activa
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['nombre'])) {
    header("Location: /index.php?expired=1");
    exit;
}

// ðŸ” ValidaciÃ³n estricta por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'papas') {
    die("ðŸš« Acceso restringido: esta secciÃ³n es solo para el rol 'papas'.");
}

// ðŸ“¦ AsignaciÃ³n de datos desde sesiÃ³n
$usuario_id = $_SESSION['usuario_id'];
$usuario = $_SESSION['usuario'] ?? 'Sin usuario';
$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
$correo = $_SESSION['correo'] ?? 'Sin correo';
$telefono = $_SESSION['telefono'] ?? 'Sin telÃ©fono';
$saldo = $_SESSION['saldo'] ?? '0.00';


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IlMana Gastronomia</title>

    <!-- Ãconos de Material Design -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

    <!-- Framework Success desde CDN -->
    <link rel="stylesheet" href="https://framework.impulsagroup.com/assets/css/framework.css">
    <script src="https://framework.impulsagroup.com/assets/javascript/framework.js" defer></script>

    <!-- Descarga de consolidado (no se usa directamente aquÃ­, pero se deja por consistencia) -->
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <!-- PDF: html2canvas + jsPDF (CDN gratuitos) -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Tablas con saltos de pÃ¡gina prolijos (autoTable) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

</head>
<style>
    /* Contenedor de tabla con scroll vertical */
    .tabla-wrapper {
        max-height: 450px;
        overflow-y: auto;
    }

    /* Tabla con ajuste dinÃ¡mico de columnas */
    .tabla-wrapper table {
        border-collapse: collapse;
        width: 100%;
        table-layout: auto;
        /* âš ï¸ CLAVE: que se adapte al contenido */
    }

    /* Cabecera fija */
    .tabla-wrapper thead th {
        position: sticky;
        top: 0;
        background-color: #fff;
        z-index: 2;
    }

    /* Altura de filas */
    .tabla-wrapper tbody tr {
        height: 44px;
    }

    /* Estilos generales de celdas */
    .data-table th,
    .data-table td {
        padding: 6px 8px;
        vertical-align: middle;
        overflow: hidden;
    }

    /* Si querÃ©s permitir quiebre de lÃ­nea en algunas columnas */
    .breakable {
        white-space: normal !important;
        word-wrap: break-word;
    }

    /* âœ… Clase opcional para limitar ancho mÃ¡ximo (solo si se aplica explÃ­citamente) */
    .max-150 {
        max-width: 150px;
        white-space: normal;
        overflow-wrap: break-word;
        word-break: break-word;
    }

    .max-80 {
        width: 80px;
        /* âœ… Lo fuerza como sugerencia */
        max-width: 80px;
        white-space: normal;
        overflow-wrap: break-word;
        word-break: break-word;
    }

    /* ancho boton de comprobante */
    .max-40 {
        width: 40px;
        max-width: 40px;
        text-align: center;
    }

    .badge {
        display: inline-block;
        max-width: 100%;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
    }

    .modal-content {
        background: #fff;
        width: 100%;
        max-width: 980px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
</style>

<body>

    <!-- ðŸ”² CONTENEDOR PRINCIPAL -->
    <div class="layout">

        <!-- ðŸ§­ SIDEBAR -->
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
                    <li onclick="abrirModalSaldo()">
                        <span class="material-icons" style="color: #5b21b6;">attach_money</span><span class="link-text">Cargar Saldo</span>
                    </li>
                    <li onclick="location.href='admin_altaUsuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">restaurant</span><span class="link-text">Viandas</span>
                    </li>
                    <li onclick="location.href='admin_importarUsuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">calendar_month</span><span class="link-text">Calendario</span>
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

        <!-- ðŸ§± MAIN -->
        <div class="main">

            <!-- ðŸŸª NAVBAR -->
            <header class="navbar">
                <button class="btn-icon" onclick="toggleSidebar()">
                    <span class="material-icons">menu</span>
                </button>
                <div class="navbar-title">Inicio</div>
            </header>

            <!-- ðŸ“¦ CONTENIDO -->
            <section class="content">

                                <!-- Bienvenida -->
                <div class="card">
                    <h2>Hola <?= htmlspecialchars($nombre) ?></h2>
                    <p>En esta pagina, vas a poder visualizar el nombre de tus hijos junto con su informacion, los pedidos de saldo y de comida</p>
                </div>

                <?php
                $saldoValor = (float)str_replace(',', '.', preg_replace('/[^0-9,\.\-]/', '', (string)$saldo));
                $saldoColor = $saldoValor < 0 ? 'red' : 'green';
                ?>

                <!-- Tarjetas de hijos y saldo -->
                <div class="card-grid grid-3">
                    <div class="card" id="saldo-card">
                        <h3>Saldo</h3>
                        <p id="saldo-disponible" style="color: <?= $saldoColor ?>;">
                            <strong>Saldo disponible:</strong> $<?= number_format($saldoValor, 2, ',', '.') ?>
                        </p>
                        <p id="saldo-pendiente" style="color: #f59e0b; <?= $saldoPendiente > 0 ? '' : 'display: none;' ?>">
                            <strong>Saldo a confirmar:</strong> $<span id="saldo-pendiente-valor"><?= number_format($saldoPendiente, 2, ',', '.') ?></span>
                        </p>
                        <button class="btn btn-aceptar" type="button" onclick="abrirModalSaldo()">Cargar saldo</button>
                    </div>
                    <?php if (!empty($hijosDetalle)): ?>
                        <?php foreach ($hijosDetalle as $hijo): ?>
                            <?php
                            $preferencias = trim($hijo['Preferencia'] ?? '');
                            $colegio = trim($hijo['Colegio'] ?? '');
                            $curso = trim($hijo['Curso'] ?? '');
                            ?>
                            <div class="card">
                                <h3><?= htmlspecialchars($hijo['Nombre']) ?></h3>
                                <p><strong>Preferencias alimenticias:</strong> <?= $preferencias !== '' ? htmlspecialchars($preferencias) : 'Sin preferencias' ?></p>
                                <p><strong>Nombre del colegio:</strong> <?= $colegio !== '' ? htmlspecialchars($colegio) : 'Sin colegio' ?></p>
                                <p><strong>Curso:</strong> <?= $curso !== '' ? htmlspecialchars($curso) : 'Sin curso' ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card">
                            <p>No hay hijos asociados a este usuario.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tablas de resultados -->
                <div>
                    <!-- Pedidos de Comida -->
                    <div class="card tabla-card">
                        <h2>Pedidos de comida</h2>
                        <div class="tabla-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>AcciÃ³n</th>
                                        <th class="max-150">Alumno</th>
                                        <th class="max-150">MenÃº</th>
                                        <th>Fecha de entrega</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pedidosComida)): ?>
                                        <?php foreach ($pedidosComida as $pedido): ?>
                                            <tr>
                                                <td><?= $pedido['Id'] ?></td>
                                                <td><button class="btn btn-small">Cancelar</button></td>
                                                <td class="max-150 breakable"><?= htmlspecialchars($pedido['Alumno']) ?></td>
                                                <td class="max-150 breakable"><?= htmlspecialchars($pedido['Menu']) ?></td>
                                                <td><?= $pedido['Fecha_entrega'] ?></td>
                                                <td>
                                                    <span class="badge <?= $pedido['Estado'] === 'Procesando' ? 'success' : 'danger' ?>">
                                                        <?= $pedido['Estado'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">No hay pedidos de comida.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pedidos de Saldo -->
                    <div class="card tabla-card" style="margin-top: 16px;">
                        <h2>Pedidos de saldo</h2>
                        <div class="tabla-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th class="max-80">Saldo</th>
                                        <th class="max-80">Estado</th>
                                        <th>Comprobante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pedidosSaldo)): ?>
                                        <?php foreach ($pedidosSaldo as $saldo): ?>
                                            <tr>
                                                <td class="max-80"><?= $saldo['Id'] ?></td>
                                                <td>$<?= number_format($saldo['Saldo'], 2, ',', '.') ?></td>
                                                <td class="max-80">
                                                    <span class="badge <?= $saldo['Estado'] === 'Aprobado' ? 'success' : ($saldo['Estado'] === 'Cancelado' ? 'danger' : 'warning') ?>">
                                                        <?= $saldo['Estado'] ?>
                                                    </span>
                                                </td>
                                                <td class="max-40">
                                                    <?php if (!empty($saldo['Comprobante'])): ?>
                                                        <?php
                                                        $comprobanteFile = basename((string) $saldo['Comprobante']);
                                                        $comprobanteUrl = '/uploads/comprobantes_inbox/' . rawurlencode($comprobanteFile);
                                                        ?>
                                                        <a href="<?= $comprobanteUrl ?>"
                                                            target="_blank"
                                                            title="Ver comprobante"
                                                            style="display: inline-block; padding: 0; margin: 0; text-decoration: none;">
                                                            <span class="material-icons" style="font-size: 20px; color: #5b21b6;">visibility</span>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">â€”</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4">No hay pedidos de saldo.</td>
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

    <!-- Modal cargar saldo -->
    <div class="modal-overlay" id="saldo-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cargar saldo</h3>
                <button class="btn btn-small btn-cancelar" type="button" onclick="cerrarModalSaldo()">Cerrar</button>
            </div>
            <div class="modal-body" id="saldo-modal-body">
                <p>Cargando...</p>
            </div>
        </div>
    </div>

    <!-- Spinner Global -->
    <script src="../partials/spinner-global.js"></script>

    <script>
        function abrirModalSaldo() {
            const modal = document.getElementById('saldo-modal');
            modal.style.display = 'flex';
            cargarModalSaldo();
        }

        function cerrarModalSaldo() {
            const modal = document.getElementById('saldo-modal');
            modal.style.display = 'none';
        }

        function formatearMonto(valor) {
            const numero = Number(valor) || 0;
            return new Intl.NumberFormat('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(numero);
        }

        function actualizarSaldoPendiente(valor) {
            const pendiente = document.getElementById('saldo-pendiente');
            const valorSpan = document.getElementById('saldo-pendiente-valor');
            if (!pendiente || !valorSpan) return;
            if (valor > 0) {
                pendiente.style.display = 'block';
                valorSpan.textContent = formatearMonto(valor);
            } else {
                pendiente.style.display = 'none';
                valorSpan.textContent = '0,00';
            }
        }

        function showAlertSafe(type, message, options = {}) {
            if (typeof window.showAlert === 'function') {
                try {
                    if (window.showAlert.length <= 1) {
                        window.showAlert(Object.assign({ type, message }, options));
                    } else {
                        window.showAlert(type, message, options);
                    }
                    return;
                } catch (err) {
                    console.warn('showAlert failed, falling back to alert.', err);
                }
            }
            alert(message);
        }

        function renderMensajeSaldo(ok, mensaje, errores) {
            const contenedor = document.getElementById('saldo-mensajes');
            if (!contenedor) return;
            if (ok) {
                contenedor.innerHTML = `<div class="card" style="border-left: 4px solid #16a34a;"><p>${mensaje}</p></div>`;
                showAlertSafe('success', mensaje || 'Solicitud enviada correctamente.');
                return;
            }
            if (errores && errores.length) {
                const items = errores.map(error => `<li>${error}</li>`).join('');
                contenedor.innerHTML = `<div class="card" style="border-left: 4px solid #dc2626;"><p><strong>Hubo un problema:</strong></p><ul>${items}</ul></div>`;
                showAlertSafe('error', errores.join(' | '));
            }
        }

        function cargarModalSaldo() {
            const body = document.getElementById('saldo-modal-body');
            body.innerHTML = '<p>Cargando...</p>';

            fetch('papa_saldo_view.php?modal=1', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(async (res) => {
                    if (!res.ok) {
                        const body = await res.text();
                        console.error('Error cargando modal saldo:', {
                            status: res.status,
                            statusText: res.statusText,
                            body
                        });
                        showAlertSafe('error', 'No se pudo cargar el formulario.');
                        throw new Error('Error cargando modal saldo');
                    }
                    return res.text();
                })
                .then(html => {
                    body.innerHTML = html;
                    inicializarModalSaldo();
                })
                .catch(() => {
                    body.innerHTML = '<p>Error al cargar el formulario.</p>';
                });
        }

        function inicializarModalSaldo() {
            const form = document.getElementById('saldo-form');
            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const formData = new FormData(form);
                    formData.append('ajax', '1');
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                        .then(async (res) => {
                            if (!res.ok) {
                                const body = await res.text();
                                console.error('Error en solicitud de saldo:', {
                                    status: res.status,
                                    statusText: res.statusText,
                                    body
                                });
                                showAlertSafe('error', 'No se pudo enviar la solicitud.');
                                throw new Error('Error en solicitud de saldo');
                            }
                            return res.json();
                        })
                        .then(data => {
                            console.log('Respuesta solicitud saldo:', data);
                            renderMensajeSaldo(data.ok, data.mensaje, data.errores);
                            if (data.ok) {
                                actualizarSaldoPendiente(data.saldoPendiente);
                                form.reset();
                            } else if (data.errores && data.errores.length) {
                                showAlertSafe('warning', data.errores.join(' | '));
                            }
                        })
                        .catch((err) => {
                            console.error('Error de conexion en solicitud de saldo:', err);
                            renderMensajeSaldo(false, '', ['Error de conexion. Intenta nuevamente.']);
                            showAlertSafe('error', 'Error de conexion. Intenta nuevamente.');
                        });
                });
            }

            const copyBtn = document.querySelector('[data-copy-cbu]');
            if (copyBtn) {
                copyBtn.addEventListener('click', () => {
                    const cbu = document.getElementById('cbu')?.innerText || '';
                    if (!cbu) return;
                    navigator.clipboard.writeText(cbu).then(() => {
                        showAlertSafe('success', 'CBU copiado al portapapeles');
                    }).catch((err) => {
                        console.error('Error al copiar CBU:', err);
                        showAlertSafe('error', 'No se pudo copiar el CBU.');
                    });
                });
            }
        }

        document.getElementById('saldo-modal').addEventListener('click', (event) => {
            if (event.target.id === 'saldo-modal') {
                cerrarModalSaldo();
            }
        });

        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>
