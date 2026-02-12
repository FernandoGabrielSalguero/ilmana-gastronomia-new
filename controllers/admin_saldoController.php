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

require_once __DIR__ . '/../models/admin_saldoModel.php';
require_once __DIR__ . '/../mail/Mail.php';

$model = new AdminSaldoModel($pdo);
$errores = [];
$mensaje = null;

$colegioId = filter_input(INPUT_GET, 'colegio', FILTER_VALIDATE_INT) ?: null;
$cursoId = filter_input(INPUT_GET, 'curso', FILTER_VALIDATE_INT) ?: null;
$estado = $_GET['estado'] ?? '';
$fechaDesde = $_GET['fecha_desde'] ?? '';
$fechaHasta = $_GET['fecha_hasta'] ?? '';
$movFiltro = trim($_GET['mov_filtro'] ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
    $fechaDesde = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
    $fechaHasta = '';
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1');
$action = $_POST['action'] ?? $_GET['action'] ?? null;

$respondJson = function (array $payload) {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['aprobar', 'cancelar'], true)) {
    $pedidoId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $observaciones = trim($_POST['observaciones'] ?? '');
    $nuevoEstado = $action === 'aprobar' ? 'Aprobado' : 'Cancelado';

    if ($pedidoId <= 0) {
        $errores[] = 'Id invalido.';
    }

    if (empty($errores)) {
        $solicitud = $model->obtenerSolicitudSaldo($pedidoId);
        $resultado = $model->actualizarEstadoSaldo($pedidoId, $nuevoEstado, $observaciones);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
            $saldoFinal = $resultado['saldo_final'] ?? null;
            if ($solicitud) {
                $correoDestino = (string)($solicitud['UsuarioCorreo'] ?? '');
                $nombreDestino = (string)($solicitud['UsuarioNombre'] ?? '');
                $saldoActual = (float)($solicitud['UsuarioSaldo'] ?? 0);
                $saldoNuevo = $nuevoEstado === 'Aprobado' ? (float)($saldoFinal ?? $saldoActual) : null;
                $motivo = $nuevoEstado === 'Cancelado' ? $observaciones : '';

                if ($correoDestino !== '') {
                    $mailResult = \SVE\Mail\Maill::enviarGestionSaldo([
                        'usuario_id' => $solicitud['Usuario_Id'] ?? null,
                        'nombre' => $nombreDestino,
                        'correo' => $correoDestino,
                        'accion' => $nuevoEstado,
                        'saldo_actual' => $saldoActual,
                        'saldo_nuevo' => $saldoNuevo,
                        'motivo' => $motivo,
                        'meta' => [
                            'evento' => 'gestion_saldo',
                            'pedido_id' => $pedidoId,
                            'estado' => $nuevoEstado
                        ]
                    ]);
                    if (!$mailResult['ok']) {
                        $mailError = (string)($mailResult['error'] ?? '');
                        $detalle = $mailError !== '' ? ' Detalle: ' . $mailError : '';
                        $mensaje = trim($mensaje . ' (No se pudo enviar el correo de gestion de saldo.' . $detalle . ')');
                    }
                }
            }
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores,
            'saldo_final' => $saldoFinal ?? null
        ]);
    }
}

if ($action === 'list' && $isAjax) {
    $solicitudes = $model->obtenerSolicitudesSaldo($colegioId, $cursoId, $estado, $fechaDesde, $fechaHasta);
    $respondJson([
        'ok' => true,
        'items' => $solicitudes
    ]);
}

if ($action === 'movimientos' && $isAjax) {
    $movimientos = $model->obtenerMovimientosSaldo($movFiltro);
    $respondJson([
        'ok' => true,
        'items' => $movimientos
    ]);
}

$colegios = $model->obtenerColegios();
$cursos = $model->obtenerCursos($colegioId);
$solicitudes = $model->obtenerSolicitudesSaldo($colegioId, $cursoId, $estado, $fechaDesde, $fechaHasta);
