<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar duración de sesión en 20 minutos
ini_set('session.gc_maxlifetime', 1200); // 20 minutos
session_set_cookie_params([
    'lifetime' => 1200,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/AuthModel.php';

$error = '';

// Mensaje si viene por expiración
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = "La sesión expiró por inactividad. Por favor, iniciá sesión nuevamente.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    $auth = new AuthModel($pdo);
    $user = $auth->login($usuario, $contrasena);

    if ($user) {
        // Guardar solo los datos de la tabla Usuarios en sesión
        $_SESSION['usuario_id'] = $user['Id'];
        $_SESSION['usuario_id'] = $user['Id'];
        $_SESSION['usuario'] = $user['Usuario'];
        $_SESSION['nombre'] = $user['Nombre'];
        $_SESSION['correo'] = $user['Correo'];
        $_SESSION['telefono'] = $user['Telefono'];
        $_SESSION['rol'] = $user['Rol'];
        $_SESSION['estado'] = $user['Estado'];
        $_SESSION['saldo'] = $user['Saldo'] ?? 0.00;
        $_SESSION['LAST_ACTIVITY'] = time();

        // Redirección por Rol
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
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
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
            </div>
            <div class="form-group">
                <button type="submit">INGRESAR</button>
            </div>
        </form>
    </div>

    <script>
        // visualizador de contraseña
        const togglePassword = document.querySelector('.toggle-password');
        const passwordField = document.getElementById('contrasena');

        togglePassword.addEventListener('click', () => {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
        });

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