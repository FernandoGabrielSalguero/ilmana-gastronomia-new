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
/*

// Mensaje si viene por expiración
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = "La sesión expiró por inactividad. Por favor, iniciá sesión nuevamente.";
}

*/
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-container h1 {
            text-align: center;
            color: #673ab7;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .form-group input:focus {
            border-color: #673ab7;
            outline: none;
        }

        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #673ab7;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .form-group button:hover {
            background-color: #5e35b1;
        }

        .error {
            color: red;
            margin-bottom: 10px;
            text-align: center;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #673ab7;
            user-select: none;
        }

        .toggle-password svg {
            display: block;
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        .toggle-password .icon-hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h1>Iniciar Sesión</h1>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" name="usuario" id="usuario" required>
            </div>
            <div class="form-group password-container">
                <label for="contrasena">Contraseña:</label>
                <input type="password" name="contrasena" id="contrasena" required>
                <span class="toggle-password" role="button" aria-label="Mostrar contrasena">
                    <svg class="icon-visible" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 5c-5 0-9.27 3.11-11 7 1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                    </svg>
                    <svg class="icon-hidden" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2.1 3.51 3.52 2.1 21.9 20.49l-1.41 1.41-3.25-3.25A11.1 11.1 0 0 1 12 19c-5 0-9.27-3.11-11-7 1.05-2.37 3.02-4.43 5.56-5.66L2.1 3.51zm7.07 7.07a3 3 0 0 0 4.25 4.25l-4.25-4.25zM12 9a3 3 0 0 1 3 3c0 .4-.08.78-.22 1.13l-3.91-3.91c.35-.14.73-.22 1.13-.22zm0-4c5 0 9.27 3.11 11 7a11.17 11.17 0 0 1-3.09 4.03l-2.16-2.16A5 5 0 0 0 12 7c-.88 0-1.72.2-2.47.56L7.8 5.83C9.1 5.3 10.52 5 12 5z" />
                    </svg>
                </span>
            </div>
            <div class="form-group">
                <button type="submit">INGRESAR</button>
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
                const iconVisible = togglePassword.querySelector('.icon-visible');
                const iconHidden = togglePassword.querySelector('.icon-hidden');

                if (iconVisible && iconHidden) {
                    iconVisible.style.display = isPassword ? 'none' : 'block';
                    iconHidden.style.display = isPassword ? 'block' : 'none';
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
