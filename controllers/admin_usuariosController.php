<?php
// Mostrar errores en pantalla (util en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesion y proteger acceso
session_start();

// Expiracion por inactividad (20 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: /index.php?expired=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time(); // Actualiza el tiempo de actividad

// Proteccion de acceso general
if (!isset($_SESSION['usuario'])) {
    die("Acceso denegado. No has iniciado sesion.");
}

// Proteccion por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    die("Acceso restringido: esta pagina es solo para usuarios Administrador.");
}

require_once __DIR__ . '/../models/admin_usuariosModel.php';

$model = new AdminUsuariosModel($pdo);
$errores = [];
$mensaje = null;

$roles = [
    'papas',
    'hyt_agencia',
    'hyt_admin',
    'cocina',
    'representante',
    'administrador',
    'cuyo_placa',
    'transporte_ld'
];

$formData = [
    'nombre' => '',
    'usuario' => '',
    'contrasena' => '',
    'telefono' => '',
    'correo' => '',
    'saldo' => '',
    'rol' => '',
    'estado' => 'activo'
];

$hijosForm = [];

$colegios = $model->obtenerColegios();
$cursos = $model->obtenerCursos();
$preferencias = $model->obtenerPreferencias();
$preferenciasLookup = [];
foreach ($preferencias as $preferencia) {
    $preferenciasLookup[(string) ($preferencia['Id'] ?? '')] = true;
}
$preferenciaDefaultId = '6';
if (!isset($preferenciasLookup[$preferenciaDefaultId])) {
    $preferenciaDefaultId = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $saldoInput = trim($_POST['saldo'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $estado = 'activo';

    $formData = [
        'nombre' => $nombre,
        'usuario' => $usuario,
        'contrasena' => '',
        'telefono' => $telefono,
        'correo' => $correo,
        'saldo' => $saldoInput,
        'rol' => $rol,
        'estado' => $estado
    ];

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if ($usuario === '') {
        $errores[] = 'El usuario es obligatorio.';
    }
    if ($contrasena === '') {
        $errores[] = 'La contrasena es obligatoria.';
    }
    if ($rol === '' || !in_array($rol, $roles, true)) {
        $errores[] = 'Selecciona un rol valido.';
    }
    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es valido.';
    }

    $telefonoNormalizado = preg_replace('/\D+/', '', $telefono);
    if ($telefonoNormalizado !== '' && (strlen($telefonoNormalizado) < 8 || strlen($telefonoNormalizado) > 15)) {
        $errores[] = 'El telefono debe tener entre 8 y 15 digitos.';
    }

    $saldo = null;
    if ($saldoInput !== '') {
        if (!is_numeric($saldoInput)) {
            $errores[] = 'El saldo debe ser numerico.';
        } else {
            $saldo = number_format((float) $saldoInput, 2, '.', '');
        }
    }

    $hijos = [];
    $hijosNombres = $_POST['hijos_nombre'] ?? [];
    $hijosPreferencias = $_POST['hijos_preferencias'] ?? [];
    $hijosColegios = $_POST['hijos_colegio'] ?? [];
    $hijosCursos = $_POST['hijos_curso'] ?? [];

    $max = max(
        count($hijosNombres),
        count($hijosPreferencias),
        count($hijosColegios),
        count($hijosCursos)
    );

    if ($rol === 'papas' && $max > 20) {
        $errores[] = 'Se permiten hasta 20 hijos por usuario.';
    }

    for ($i = 0; $i < $max; $i++) {
        $nombreHijo = trim($hijosNombres[$i] ?? '');
        $prefIdRaw = trim($hijosPreferencias[$i] ?? '');
        $prefId = $prefIdRaw;
        if ($prefId === '' && $preferenciaDefaultId !== '') {
            $prefId = $preferenciaDefaultId;
        }
        $colegioRaw = $hijosColegios[$i] ?? '';
        $cursoRaw = $hijosCursos[$i] ?? '';

        $hasAny = $nombreHijo !== '' || $prefIdRaw !== '' || $colegioRaw !== '' || $cursoRaw !== '';
        if (!$hasAny) {
            continue;
        }

        $colegioId = $colegioRaw !== '' ? (int) $colegioRaw : null;
        $cursoId = $cursoRaw !== '' ? (int) $cursoRaw : null;

        $hijosForm[] = [
            'nombre' => $nombreHijo,
            'preferencias' => $prefId,
            'colegio_id' => $colegioId,
            'curso_id' => $cursoId
        ];

        if ($nombreHijo === '') {
            $errores[] = 'Cada hijo debe tener un nombre.';
            continue;
        }

        if ($colegioRaw !== '' && $colegioId <= 0) {
            $errores[] = 'Selecciona un colegio valido para los hijos.';
            continue;
        }

        if ($cursoRaw !== '' && $cursoId <= 0) {
            $errores[] = 'Selecciona un curso valido para los hijos.';
            continue;
        }

        if ($prefId !== '' && !isset($preferenciasLookup[$prefId])) {
            $errores[] = 'Selecciona una preferencia alimenticia valida.';
            continue;
        }

        $hijos[] = [
            'nombre' => $nombreHijo,
            'preferencias_id' => $prefId !== '' ? (int) $prefId : null,
            'colegio_id' => $colegioId ?: null,
            'curso_id' => $cursoId ?: null
        ];
    }

    if (empty($errores)) {
        $data = [
            'nombre' => $nombre !== '' ? $nombre : null,
            'usuario' => $usuario !== '' ? $usuario : null,
            'contrasena' => password_hash($contrasena, PASSWORD_BCRYPT),
            'telefono' => $telefonoNormalizado !== '' ? $telefonoNormalizado : null,
            'correo' => $correo !== '' ? $correo : null,
            'pedidos_saldo' => null,
            'saldo' => $saldo !== null ? $saldo : 0,
            'pedidos_comida' => null,
            'rol' => $rol,
            'hijos' => null,
            'estado' => $estado
        ];

        $resultado = $model->crearUsuarioConHijos($data, $rol === 'papas' ? $hijos : []);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
            $formData = [
                'nombre' => '',
                'usuario' => '',
                'contrasena' => '',
                'telefono' => '',
                'correo' => '',
                'saldo' => '',
                'rol' => '',
                'estado' => 'activo'
            ];
            $hijosForm = [];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }
}
