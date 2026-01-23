<?php
require_once __DIR__ . '/../models/papa_dashboardModel.php';
$model = new PapaDashboardModel($pdo);

$usuarioId = $_SESSION['usuario_id'] ?? null;
$hijoSeleccionado = $_GET['hijo_id'] ?? null;
$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;

$hijosDetalle = $model->obtenerHijosDetallePorUsuario($usuarioId);
$pedidosSaldo = $model->obtenerPedidosSaldo($usuarioId, $desde, $hasta);
$pedidosComida = $model->obtenerPedidosComida($usuarioId, $hijoSeleccionado, $desde, $hasta);
$saldoPendiente = $model->obtenerSaldoPendiente($usuarioId);

// cargamos los datos dinamicamente con ajax
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');

    ob_start();
foreach ($pedidosComida as $pedido): ?>
    <tr>
        <td><?= $pedido['Id'] ?></td>
        <td>
            <button class="btn btn-editar btn-xs">🔍</button>
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
        'comida' => $tablaComida ?: '<tr><td colspan="4">No hay pedidos de comida.</td></tr>',
        'saldo' => $tablaSaldo ?: '<tr><td colspan="6">No hay pedidos de saldo.</td></tr>'
    ]);
    exit;
}

