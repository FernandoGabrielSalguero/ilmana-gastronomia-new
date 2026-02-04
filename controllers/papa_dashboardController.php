<?php
require_once __DIR__ . '/../models/papa_dashboardModel.php';
$model = new PapaDashboardModel($pdo);

$usuarioId = $_SESSION['usuario_id'] ?? null;
$hijoSeleccionado = $_GET['hijo_id'] ?? null;
$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;

$esAjax = isset($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $esAjax && ($_POST['accion'] ?? '') === 'actualizar_cursos') {
    header('Content-Type: application/json');

    $cursos = $_POST['cursos'] ?? [];
    if (!is_array($cursos)) {
        $cursos = [];
    }

    $resultado = $model->actualizarCursosHijos($usuarioId, $cursos);

    if ($resultado['ok']) {
        registrarAuditoria($pdo, [
            'evento' => 'papa_actualizar_curso',
            'modulo' => 'papa',
            'entidad' => 'Hijos',
            'estado' => 'ok',
            'datos' => [
                'actualizados' => $resultado['actualizados'] ?? 0,
                'detalles' => $resultado['detalles'] ?? [],
            ],
        ]);
    }

    $hijosActualizados = $resultado['ok'] ? $model->obtenerHijosDetallePorUsuario($usuarioId) : [];

    echo json_encode([
        'ok' => (bool) ($resultado['ok'] ?? false),
        'error' => $resultado['error'] ?? '',
        'actualizados' => $resultado['actualizados'] ?? 0,
        'hijos' => $hijosActualizados
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $esAjax && ($_POST['accion'] ?? '') === 'cancelar_pedido') {
    header('Content-Type: application/json');

    $pedidoId = isset($_POST['pedido_id']) ? (int) $_POST['pedido_id'] : 0;
    $motivo = trim((string) ($_POST['motivo'] ?? ''));

    if ($pedidoId <= 0 || $motivo === '') {
        echo json_encode([
            'ok' => false,
            'error' => 'Debes indicar un motivo de cancelacion.'
        ]);
        exit;
    }

    $resultado = $model->cancelarPedidoComida($usuarioId, $pedidoId, $motivo);
    $saldoActual = $resultado['ok'] ? $model->obtenerSaldoUsuario($usuarioId) : null;
    if ($resultado['ok'] && $usuarioId) {
        $_SESSION['saldo'] = $saldoActual;
    }
    $saldoPendiente = $resultado['ok'] ? $model->obtenerSaldoPendiente($usuarioId) : null;
    if ($resultado['ok']) {
        registrarAuditoria($pdo, [
            'evento' => 'papa_cancelar_pedido',
            'modulo' => 'papa',
            'entidad' => 'Pedidos_Comida',
            'entidad_id' => $pedidoId,
            'estado' => 'ok',
            'datos' => [
                'motivo' => $motivo,
            ],
        ]);
    }
    echo json_encode([
        'ok' => $resultado['ok'],
        'error' => $resultado['error'] ?? '',
        'saldoActual' => $saldoActual,
        'saldoPendiente' => $saldoPendiente
    ]);
    exit;
}

$hijosDetalle = $model->obtenerHijosDetallePorUsuario($usuarioId);
$cursosDisponibles = $model->obtenerCursosDisponibles();
$pedidosSaldo = $model->obtenerPedidosSaldo($usuarioId, $desde, $hasta);
$pedidosComida = $model->obtenerPedidosComida($usuarioId, $hijoSeleccionado, $desde, $hasta);
$saldoPendiente = $model->obtenerSaldoPendiente($usuarioId);
$saldoActual = $model->obtenerSaldoUsuario($usuarioId);
if ($usuarioId) {
    $_SESSION['saldo'] = $saldoActual;
}

$cursosPorColegio = [];
foreach ($cursosDisponibles as $curso) {
    $colegioId = $curso['Colegio_Id'] !== null ? (int) $curso['Colegio_Id'] : 0;
    if (!isset($cursosPorColegio[$colegioId])) {
        $cursosPorColegio[$colegioId] = [];
    }
    $cursosPorColegio[$colegioId][] = $curso;
}

$mostrarModalCursos = false;
if ($usuarioId && !empty($hijosDetalle)) {
    $mostrarModalCursos = !$model->tieneActualizacionCursoAnual($usuarioId, (int) date('Y'));
}

// cargamos los datos dinamicamente con ajax
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');

    ob_start();
foreach ($pedidosComida as $pedido): ?>
    <tr>
        <td><?= $pedido['Id'] ?></td>
        <td>
            <?php if (!empty($pedido['Puede_cancelar'])): ?>
                <button class="btn btn-aceptar btn-small" type="button" data-cancelar-pedido data-pedido-id="<?= (int) $pedido['Id'] ?>">Cancelar</button>
            <?php else: ?>
                <button class="btn btn-small btn-disabled" type="button" disabled>Cancelar</button>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($pedido['Alumno']) ?></td>
        <td><?= htmlspecialchars($pedido['Menu']) ?></td>
        <td><?= $pedido['Fecha_entrega'] ?></td>
        <td>
            <span class="badge <?= $pedido['Estado'] === 'Procesando' ? 'success' : 'danger' ?>">
                <?= $pedido['Estado'] ?>
            </span>
        </td>
    </tr>
<?php endforeach;

    $tablaComida = ob_get_clean();

    ob_start();
    foreach ($pedidosSaldo as $saldo): ?>
        <tr>
            <td><?= $saldo['Id'] ?></td>
            <td>$<?= number_format($saldo['Saldo'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($saldo['Fecha_pedido'] ?? '') ?></td>
            <td>
                <span class="badge <?= $saldo['Estado'] === 'Aprobado' ? 'success' : ($saldo['Estado'] === 'Cancelado' ? 'danger' : 'warning') ?>">
                    <?= $saldo['Estado'] ?>
                </span>
            </td>
            <td><?= htmlspecialchars($saldo['Observaciones'] ?? '') ?></td>
            <td>
                <?php if (!empty($saldo['Comprobante'])): ?>
                    <?php
                    $comprobanteFile = basename((string) $saldo['Comprobante']);
                    $comprobanteUrl = '/uploads/comprobantes_inbox/' . rawurlencode($comprobanteFile);
                    ?>
                    <a href="<?= $comprobanteUrl ?>" target="_blank" title="Ver comprobante">
                        <span class="material-icons" style="font-size: 20px; color: #5b21b6;">visibility</span>
                    </a>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach;
    $tablaSaldo = ob_get_clean();

    echo json_encode([
        'comida' => $tablaComida ?: '<tr><td colspan="6">No hay pedidos de comida.</td></tr>',
        'saldo' => $tablaSaldo ?: '<tr><td colspan="6">No hay pedidos de saldo.</td></tr>',
        'saldoActual' => $saldoActual,
        'saldoPendiente' => $saldoPendiente
    ]);
    exit;
}

