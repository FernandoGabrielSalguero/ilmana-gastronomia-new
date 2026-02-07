<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    // Configurar duraciÃ³n de sesión en 20 minutos
    ini_set('session.gc_maxlifetime', 31536000); // 1 anio
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/AuthModel.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    $auth = new AuthModel($pdo);
    $user = $auth->login($usuario, $contrasena);

    if (is_array($user) && isset($user['error'])) {
        registrarAuditoria($pdo, [
            'evento' => 'login_error',
            'estado' => $user['error'],
            'datos' => [
                'usuario' => $usuario,
            ],
        ]);
        if ($user['error'] === 'inactive') {
            $error = 'No tenes permiso para acceder, contactate con el administrador';
        } else {
            $error = 'Usuario o contrasena incorrectos.';
        }
    } elseif ($user) {
        // Guardar solo los datos de la tabla Usuarios en sesión
        $_SESSION['usuario_id'] = $user['Id'];
        $_SESSION['usuario'] = $user['Usuario'];
        $_SESSION['nombre'] = $user['Nombre'];
        $_SESSION['correo'] = $user['Correo'];
        $_SESSION['telefono'] = $user['Telefono'];
        $_SESSION['rol'] = $user['Rol'];
        $_SESSION['estado'] = $user['Estado'];
        $_SESSION['saldo'] = $user['Saldo'] ?? 0.00;

        registrarAuditoria($pdo, [
            'evento' => 'login_ok',
            'estado' => 'ok',
            'usuario_id' => $user['Id'],
            'usuario_login' => $user['Usuario'],
            'rol' => $user['Rol'],
        ]);

        // RedirecciÃ³n por Rol
        switch ($user['Rol']) {
            case 'administrador':
                header('Location: /views/admin/admin_dashboard.php');
                break;
            case 'cocina':
                header('Location: /views/cocina/cocina_dashboard.php');
                break;
            case 'cuyo_placa':
                header('Location: /views/cuyo_placas/cuyo_placa_dashboard.php');
                break;
            case 'papas':
                header('Location: /views/papa/papa_dashboard.php');
                break;
            case 'representante':
                header('Location: /views/representante/representante_dashboard.php');
                break;
            default:
                die("Rol no reconocido: " . $user['Rol']);
        }
        exit;
    } else {
        registrarAuditoria($pdo, [
            'evento' => 'login_error',
            'estado' => 'invalid',
            'datos' => [
                'usuario' => $usuario,
            ],
        ]);
        $error = 'Usuario o contrasena incorrectos.';
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,500,0,0" />
    <style>
        body {
            font-family: 'Manrope', Arial, sans-serif;
            background-color: #f7f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #ffffff;
            padding: 28px;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            width: 100%;
            max-width: 520px;
        }

        .login-container h1 {
            text-align: left;
            color: #1f1a46;
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 700;
        }

        .login-subtitle {
            margin: 0 0 18px;
            color: #6b7280;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #3f3f46;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #c7c9d9;
            border-radius: 10px;
            font-size: 15px;
            color: #111827;
            background: #ffffff;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #7c83f2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(124, 131, 242, 0.18);
        }

        .form-group button {
            width: 100%;
            padding: 12px;
            background-color: #6f77f6;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 600;
            font-size: 15px;
        }

        .form-group button:hover {
            background-color: #5b64ee;
        }

        .error {
            color: #b91c1c;
            margin-bottom: 12px;
            text-align: left;
            background: #fee2e2;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #fecaca;
        }

        .password-container {
            position: relative;
        }

        .password-container input {
            padding-right: 46px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 70%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            cursor: pointer;
            color: #6f77f6;
            user-select: none;
            line-height: 0;
            font-size: 22px;
        }

        .field-required {
            color: #ef4444;
            margin-left: 3px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h1>Iniciar Sesión</h1>
        <p class="login-subtitle">Ingresá tu usuario y contraseña para continuar.</p>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="usuario">Usuario<span class="field-required">*</span></label>
                <input type="text" name="usuario" id="usuario" placeholder="Ingresá tu usuario" required>
            </div>
            <div class="form-group password-container">
                <label for="contrasena">Contraseña<span class="field-required">*</span></label>
                <input type="password" name="contrasena" id="contrasena" placeholder="Ingresá tu contraseña" required>
                <span class="toggle-password material-symbols-outlined" role="button" aria-label="Mostrar contrasena">visibility</span>
            </div>
            <div class="form-group">
                <button type="submit">Ingresar</button>
            </div>
        </form>
    </div>

    <script>
        // visualizador de Contraseña
        const togglePassword = document.querySelector('.toggle-password');
        const passwordField = document.getElementById('contrasena');

        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', () => {
                const isPassword = passwordField.getAttribute('type') === 'password';
                passwordField.setAttribute('type', isPassword ? 'text' : 'password');
                if (togglePassword.classList.contains('material-symbols-outlined')) {
                    togglePassword.textContent = isPassword ? 'visibility_off' : 'visibility';
                }

                togglePassword.setAttribute(
                    'aria-label',
                    isPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'
                );
            });
        }

        // imprirmir los datos de la sesion en la consola
        <?php if (!empty($_SESSION)): ?>
            const sessionData = <?= json_encode($_SESSION, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            console.log("Datos de sesión:", sessionData);
        <?php endif; ?>

        // visualizar los campos del formulario de ingreso por consola:
        document.querySelector('form').addEventListener('submit', e => {
            const u = document.getElementById('usuario').value;
            const c = document.getElementById('contrasena').value;
            console.log("Intento login con:", u, c);
        });
    </script>

    <!-- Spinner Global -->
    <script src="views/partials/spinner-global.js"></script>
</body>

</html>
