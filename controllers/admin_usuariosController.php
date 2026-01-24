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

$estados = ['activo', 'inactivo'];

$formData = [
    'nombre' => '',
    'usuario' => '',
    'contrasena' => '',
    'telefono' => '',
    'correo' => '',
    'pedidos_saldo' => '',
    'saldo' => '',
    'pedidos_comida' => '',
    'rol' => '',
    'hijos_texto' => '',
    'estado' => 'activo'
];

$hijosForm = [];

$colegios = $model->obtenerColegios();
$cursos = $model->obtenerCursos();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $pedidosSaldo = trim($_POST['pedidos_saldo'] ?? '');
    $saldoInput = trim($_POST['saldo'] ?? '');
    $pedidosComida = trim($_POST['pedidos_comida'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $hijosTexto = trim($_POST['hijos_texto'] ?? '');
    $estado = $_POST['estado'] ?? 'activo';

    $formData = [
        'nombre' => $nombre,
        'usuario' => $usuario,
        'contrasena' => '',
        'telefono' => $telefono,
        'correo' => $correo,
        'pedidos_saldo' => $pedidosSaldo,
        'saldo' => $saldoInput,
        'pedidos_comida' => $pedidosComida,
        'rol' => $rol,
        'hijos_texto' => $hijosTexto,
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
    if ($estado === '' || !in_array($estado, $estados, true)) {
        $errores[] = 'Selecciona un estado valido.';
    }
    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es valido.';
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

    for ($i = 0; $i < $max; $i++) {
        $nombreHijo = trim($hijosNombres[$i] ?? '');
        $prefHijo = trim($hijosPreferencias[$i] ?? '');
        $colegioRaw = $hijosColegios[$i] ?? '';
        $cursoRaw = $hijosCursos[$i] ?? '';

        $hasAny = $nombreHijo !== '' || $prefHijo !== '' || $colegioRaw !== '' || $cursoRaw !== '';
        if (!$hasAny) {
            continue;
        }

        $colegioId = $colegioRaw !== '' ? (int) $colegioRaw : null;
        $cursoId = $cursoRaw !== '' ? (int) $cursoRaw : null;

        $hijosForm[] = [
            'nombre' => $nombreHijo,
            'preferencias' => $prefHijo,
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

        $hijos[] = [
            'nombre' => $nombreHijo,
            'preferencias' => $prefHijo !== '' ? $prefHijo : null,
            'colegio_id' => $colegioId ?: null,
            'curso_id' => $cursoId ?: null
        ];
    }

    if (empty($errores)) {
        $data = [
            'nombre' => $nombre !== '' ? $nombre : null,
            'usuario' => $usuario !== '' ? $usuario : null,
            'contrasena' => password_hash($contrasena, PASSWORD_BCRYPT),
            'telefono' => $telefono !== '' ? $telefono : null,
            'correo' => $correo !== '' ? $correo : null,
            'pedidos_saldo' => $pedidosSaldo !== '' ? $pedidosSaldo : null,
            'saldo' => $saldo,
            'pedidos_comida' => $pedidosComida !== '' ? $pedidosComida : null,
            'rol' => $rol,
            'hijos' => $hijosTexto !== '' ? $hijosTexto : null,
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
                'pedidos_saldo' => '',
                'saldo' => '',
                'pedidos_comida' => '',
                'rol' => '',
                'hijos_texto' => '',
                'estado' => 'activo'
            ];
            $hijosForm = [];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }
}
