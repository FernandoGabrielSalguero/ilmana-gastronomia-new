<?php
// Mostrar errores en pantalla (util en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesion y proteger acceso
session_start();

// Proteccion de acceso general
if (!isset($_SESSION['usuario'])) {
    die("Acceso denegado. No has iniciado sesion.");
}

// Proteccion por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    die("Acceso restringido: esta pagina es solo para usuarios Administrador.");
}

require_once __DIR__ . '/../models/admin_usuariosModel.php';
require_once __DIR__ . '/../mail/Mail.php';

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

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || ($_POST['ajax'] ?? '') === '1';

$formatUsuarioPayload = static function ($usuario) {
    $saldoTabla = isset($usuario['Saldo']) ? number_format((float) $usuario['Saldo'], 2, '.', '') : '0.00';
    $estadoRaw = strtolower(trim((string) ($usuario['Estado'] ?? '')));
    $estadoLabel = $estadoRaw !== '' ? $estadoRaw : 'activo';

    return [
        'id' => $usuario['Id'] ?? '',
        'nombre' => $usuario['Nombre'] ?? '',
        'usuario' => $usuario['Usuario'] ?? '',
        'telefono' => $usuario['Telefono'] ?? '',
        'correo' => $usuario['Correo'] ?? '',
        'rol' => $usuario['Rol'] ?? '',
        'saldo' => $saldoTabla,
        'estado' => $estadoLabel
    ];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['desactivar', 'toggle_estado'], true)) {
    $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
    $estado = 'inactivo';
    if (($_POST['action'] ?? '') === 'toggle_estado') {
        $estado = strtolower(trim((string) ($_POST['estado'] ?? '')));
    }

    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        $errores[] = 'Estado invalido.';
    } elseif ($usuarioId <= 0) {
        $errores[] = 'Usuario invalido.';
    } else {
        $usuarioAnterior = $model->obtenerUsuarioConHijos($usuarioId);
        $ok = $model->actualizarEstadoUsuario($usuarioId, $estado);
        if ($ok) {
            $mensaje = 'Usuario actualizado correctamente.';
            $correoDestino = '';
            $nombreDestino = '';
            if ($usuarioAnterior) {
                $correoDestino = (string)($usuarioAnterior['Correo'] ?? '');
                $nombreDestino = (string)($usuarioAnterior['Nombre'] ?? '');
            }
            if ($correoDestino !== '' && $usuarioAnterior) {
                $estadoAntes = (string)($usuarioAnterior['Estado'] ?? '');
                $cambios = [];
                if ($estadoAntes !== $estado) {
                    $cambios[] = [
                        'campo' => 'Estado',
                        'antes' => $estadoAntes,
                        'despues' => $estado
                    ];
                }
                if (!empty($cambios)) {
                    $mailResult = \SVE\Mail\Maill::enviarActualizacionUsuario([
                        'usuario_id' => $usuarioId,
                        'nombre' => $nombreDestino,
                        'correo' => $correoDestino,
                        'cambios' => $cambios,
                        'estado_antes' => $estadoAntes,
                        'estado_despues' => $estado,
                        'meta' => [
                            'evento' => 'cambio_estado_usuario',
                            'usuario_id' => $usuarioId
                        ]
                    ]);
                    if (!$mailResult['ok']) {
                        $mailError = (string)($mailResult['error'] ?? '');
                        $detalle = $mailError !== '' ? ' Detalle: ' . $mailError : '';
                        $mensaje = trim($mensaje . ' (No se pudo enviar el correo de actualizacion.' . $detalle . ')');
                    }
                }
            }
        } else {
            $errores[] = 'No se pudo actualizar el usuario.';
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores,
            'estado' => empty($errores) ? $estado : null
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'buscar') {
    $termino = trim($_POST['termino'] ?? '');
    $usuarios = $model->buscarUsuariosConHijos($termino);

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        $respuestaUsuarios = [];
        foreach ($usuarios as $usuario) {
            $respuestaUsuarios[] = [
                'usuario' => $formatUsuarioPayload($usuario),
                'hijos' => $usuario['hijos'] ?? []
            ];
        }
        echo json_encode([
            'ok' => true,
            'usuarios' => $respuestaUsuarios
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {
    $usuarioId = (int) ($_POST['edit_id'] ?? 0);
    $nombre = trim($_POST['edit_nombre'] ?? '');
    $usuario = trim($_POST['edit_usuario'] ?? '');
    $contrasena = $_POST['edit_contrasena'] ?? '';
    $telefono = trim($_POST['edit_telefono'] ?? '');
    $correo = trim($_POST['edit_correo'] ?? '');
    $saldoInput = trim($_POST['edit_saldo'] ?? '');
    $rol = $_POST['edit_rol'] ?? '';

    if ($usuarioId <= 0) {
        $errores[] = 'Usuario invalido.';
    }
    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if ($usuario === '') {
        $errores[] = 'El usuario es obligatorio.';
    }
    if ($rol === '' || !in_array($rol, $roles, true)) {
        $errores[] = 'Selecciona un rol valido.';
    }
    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es valido.';
    }
    if ($usuario !== '' && $model->usuarioExiste($usuario, $usuarioId)) {
        $errores[] = 'El usuario ya existe.';
    }

    $telefonoNormalizado = preg_replace('/\D+/', '', $telefono);
    if ($telefonoNormalizado !== '' && (strlen($telefonoNormalizado) < 8 || strlen($telefonoNormalizado) > 15)) {
        $errores[] = 'El telefono debe tener entre 8 y 15 digitos.';
    }

    $saldo = 0;
    if ($saldoInput !== '') {
        if (!is_numeric($saldoInput)) {
            $errores[] = 'El saldo debe ser numerico.';
        } else {
            $saldo = number_format((float) $saldoInput, 2, '.', '');
        }
    }

    $hijos = [];
    if ($rol === 'papas') {
        $hijosNombres = $_POST['edit_hijos_nombre'] ?? [];
        $hijosPreferencias = $_POST['edit_hijos_preferencias'] ?? [];
        $hijosColegios = $_POST['edit_hijos_colegio'] ?? [];
        $hijosCursos = $_POST['edit_hijos_curso'] ?? [];

        $max = max(
            count($hijosNombres),
            count($hijosPreferencias),
            count($hijosColegios),
            count($hijosCursos)
        );

        if ($max > 20) {
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
    }

    $usuarioAnterior = null;
    if ($usuarioId > 0) {
        $usuarioAnterior = $model->obtenerUsuarioConHijos($usuarioId);
    }

    if (empty($errores)) {
        $data = [
            'nombre' => $nombre !== '' ? $nombre : null,
            'usuario' => $usuario !== '' ? $usuario : null,
            'telefono' => $telefonoNormalizado !== '' ? $telefonoNormalizado : null,
            'correo' => $correo !== '' ? $correo : null,
            'saldo' => $saldo,
            'rol' => $rol
        ];

        $hash = $contrasena !== '' ? password_hash($contrasena, PASSWORD_BCRYPT) : null;
        $resultado = $model->actualizarUsuarioConHijos($usuarioId, $data, $rol === 'papas' ? $hijos : [], $hash);

        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
            $correoDestino = $correo !== '' ? $correo : (string)($usuarioAnterior['Correo'] ?? '');
            $nombreDestino = $nombre !== '' ? $nombre : (string)($usuarioAnterior['Nombre'] ?? '');
            if ($correoDestino !== '' && $usuarioAnterior) {
                $cambios = [];
                $saldoAnterior = number_format((float)($usuarioAnterior['Saldo'] ?? 0), 2, '.', '');
                $saldoNuevo = number_format((float)($saldo ?? 0), 2, '.', '');
                $estadoAntes = (string)($usuarioAnterior['Estado'] ?? '');
                $estadoDespues = $estadoAntes;

                $comparaciones = [
                    'Nombre' => [(string)($usuarioAnterior['Nombre'] ?? ''), $nombre],
                    'Usuario' => [(string)($usuarioAnterior['Usuario'] ?? ''), $usuario],
                    'Correo' => [(string)($usuarioAnterior['Correo'] ?? ''), $correo],
                    'Telefono' => [preg_replace('/\D+/', '', (string)($usuarioAnterior['Telefono'] ?? '')), $telefonoNormalizado],
                    'Rol' => [(string)($usuarioAnterior['Rol'] ?? ''), $rol],
                    'Saldo' => [$saldoAnterior, $saldoNuevo]
                ];

                foreach ($comparaciones as $campo => [$antes, $despues]) {
                    $antesStr = (string)$antes;
                    $despuesStr = (string)$despues;
                    if ($campo === 'Correo') {
                        $antesStr = strtolower(trim($antesStr));
                        $despuesStr = strtolower(trim($despuesStr));
                    }
                    if ($antesStr !== $despuesStr) {
                        $cambios[] = [
                            'campo' => $campo,
                            'antes' => $antesStr === '' ? '-' : $antesStr,
                            'despues' => $despuesStr === '' ? '-' : $despuesStr
                        ];
                    }
                }

                if ($contrasena !== '') {
                    $cambios[] = [
                        'campo' => 'Contrasena',
                        'antes' => 'Actual',
                        'despues' => 'Actualizada'
                    ];
                }

                if (!empty($cambios)) {
                    $mailResult = \SVE\Mail\Maill::enviarActualizacionUsuario([
                        'usuario_id' => $usuarioId,
                        'nombre' => $nombreDestino,
                        'correo' => $correoDestino,
                        'cambios' => $cambios,
                        'estado_antes' => $estadoAntes,
                        'estado_despues' => $estadoDespues,
                        'meta' => [
                            'evento' => 'edicion_usuario',
                            'usuario_id' => $usuarioId
                        ]
                    ]);
                    if (!$mailResult['ok']) {
                        $mailError = (string)($mailResult['error'] ?? '');
                        $detalle = $mailError !== '' ? ' Detalle: ' . $mailError : '';
                        $mensaje = trim($mensaje . ' (No se pudo enviar el correo de actualizacion.' . $detalle . ')');
                    }
                }
            }
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        $usuarioActualizado = empty($errores) ? $model->obtenerUsuarioConHijos($usuarioId) : null;
        $usuarioPayload = $usuarioActualizado ? $formatUsuarioPayload($usuarioActualizado) : null;
        $hijosPayload = $usuarioActualizado['hijos'] ?? [];

        echo json_encode([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores,
            'usuario' => $usuarioPayload,
            'hijos' => $hijosPayload
        ]);
        exit;
    }
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
            $mailError = '';
            if ($correo !== '') {
                $mailResult = \SVE\Mail\Maill::enviarCorreoBienvenida([
                    'usuario_id' => $resultado['usuario_id'] ?? null,
                    'nombre' => $nombre,
                    'correo' => $correo,
                    'usuario' => $usuario,
                    'contrasena' => $contrasena,
                    'link' => 'https://viandas.ilmanagastronomia.com/',
                    'telefono' => '+54 9 2613 40-6173',
                    'meta' => [
                        'evento' => 'alta_usuario'
                    ]
                ]);
                if (!$mailResult['ok']) {
                    $mailError = (string)($mailResult['error'] ?? '');
                    $detalle = $mailError !== '' ? ' Detalle: ' . $mailError : '';
                    $mensaje = trim($mensaje . ' (No se pudo enviar el correo de bienvenida.' . $detalle . ')');
                }
            }
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

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        $saldoRespuesta = $saldo !== null ? $saldo : 0;
        $hijosRespuesta = [];
        if (empty($errores) && $rol === 'papas') {
            foreach ($hijos as $hijo) {
                $hijosRespuesta[] = [
                    'nombre' => $hijo['nombre'],
                    'preferencias' => $hijo['preferencias_id'] ?? null,
                    'colegio_id' => $hijo['colegio_id'] ?? null,
                    'curso_id' => $hijo['curso_id'] ?? null
                ];
            }
        }
        echo json_encode([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores,
            'mail_error' => $mailError ?? '',
            'usuario' => empty($errores) ? [
                'id' => $resultado['usuario_id'] ?? null,
                'nombre' => $nombre,
                'usuario' => $usuario,
                'telefono' => $telefonoNormalizado,
                'correo' => $correo,
                'rol' => $rol,
                'saldo' => number_format((float) $saldoRespuesta, 2, '.', ''),
                'estado' => $estado
            ] : null,
            'hijos' => $hijosRespuesta
        ]);
        exit;
    }
}

$usuarios = $model->obtenerUsuariosConHijos();
