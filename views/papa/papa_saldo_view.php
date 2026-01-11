<?php
// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controllers/papa_saldo_controller.php';

// Expiracion por inactividad (20 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: /index.php?expired=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Control de sesion activa
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['nombre'])) {
    header("Location: /index.php?expired=1");
    exit;
}

// Validacion estricta por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'papas') {
    die("Acceso restringido: esta seccion es solo para el rol 'papas'.");
}

$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
$esModal = isset($_GET['modal']) && $_GET['modal'] == '1';

ob_start();
?>

<div id="saldo-mensajes"></div>

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

<?php if ($exito): ?>
    <div class="card" style="border-left: 4px solid #16a34a;">
        <p>Pedido de saldo realizado con exito. La acreditacion puede demorar hasta 72hs.</p>
        <a class="btn" href="papa_dashboard.php">Volver al dashboard</a>
    </div>
<?php endif; ?>

<div class="card-grid grid-2">
    <div class="card">
        <h3>Solicitud de saldo</h3>
        <form method="post" action="papa_saldo_view.php" enctype="multipart/form-data" class="form-modern" id="saldo-form">
            <div class="input-group">
                <label for="monto">Monto a recargar</label>
                <select id="monto" name="monto" required>
                    <option value="">Selecciona un monto</option>
                    <?php foreach ($montosValidos as $monto): ?>
                        <option value="<?= $monto ?>">$<?= number_format($monto, 2, ',', '.') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <label for="comprobante">Comprobante</label>
                <input type="file" id="comprobante" name="comprobante" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>

            <button class="btn" type="submit">Cargar saldo</button>
        </form>
    </div>

    <div class="card">
        <h3>Datos bancarios</h3>
        <p><strong>CUIT:</strong> 20273627651</p>
        <p><strong>CBU:</strong> <span id="cbu">0340300408300313721004</span>
            <button class="btn btn-small" type="button" data-copy-cbu>Copiar</button>
        </p>
        <p><strong>Banco:</strong> BANCO PATAGONIA</p>
        <p><strong>Titular de la cuenta:</strong> Federico Figueroa</p>
        <p><strong>Alias:</strong> ROJO.GENIO.CASINO</p>
    </div>
</div>
<?php
$saldoContenido = ob_get_clean();

if ($esModal) {
    echo $saldoContenido;
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cargar saldo</title>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

    <link rel="stylesheet" href="https://framework.impulsagroup.com/assets/css/framework.css">
    <script src="https://framework.impulsagroup.com/assets/javascript/framework.js" defer></script>
</head>

<body>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <span class="material-icons logo-icon">dashboard</span>
                <span class="logo-text">AMPD</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li onclick="location.href='papa_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
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

        <div class="main">
            <header class="navbar">
                <button class="btn-icon" onclick="toggleSidebar()">
                    <span class="material-icons">menu</span>
                </button>
                <div class="navbar-title">Cargar saldo</div>
            </header>

            <section class="content">
                <div class="card">
                    <h2>Hola <?= htmlspecialchars($nombre) ?></h2>
                    <p>Completa la solicitud de saldo y adjunta el comprobante de transferencia.</p>
                </div>

                <?= $saldoContenido ?>
            </section>
        </div>
    </div>

    <script>
        function renderMensajeSaldo(ok, mensaje, errores) {
            const contenedor = document.getElementById('saldo-mensajes');
            if (!contenedor) return;
            if (ok) {
                contenedor.innerHTML = `<div class="card" style="border-left: 4px solid #16a34a;"><p>${mensaje}</p></div>`;
                return;
            }
            if (errores && errores.length) {
                const items = errores.map(error => `<li>${error}</li>`).join('');
                contenedor.innerHTML = `<div class="card" style="border-left: 4px solid #dc2626;"><p><strong>Hubo un problema:</strong></p><ul>${items}</ul></div>`;
            }
        }

        function handleSaldoSubmit(form) {
            const formData = new FormData(form);
            formData.append('ajax', '1');
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    renderMensajeSaldo(data.ok, data.mensaje, data.errores);
                    if (data.ok) {
                        form.reset();
                    }
                })
                .catch(() => {
                    renderMensajeSaldo(false, '', ['Error de conexion. Intenta nuevamente.']);
                });
        }

        document.addEventListener('click', (event) => {
            if (event.target && event.target.matches('[data-copy-cbu]')) {
                const cbu = document.getElementById('cbu').innerText;
                navigator.clipboard.writeText(cbu).then(() => {
                    alert('CBU copiado al portapapeles');
                });
            }
        });

        const saldoForm = document.getElementById('saldo-form');
        if (saldoForm) {
            saldoForm.addEventListener('submit', function (event) {
                event.preventDefault();
                handleSaldoSubmit(saldoForm);
            });
        }
    </script>
</body>

</html>
