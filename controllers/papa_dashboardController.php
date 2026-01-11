<?php
require_once __DIR__ . '/../models/papa_dashboardModel.php';
$model = new PapaDashboardModel($pdo);

$usuarioId = $_SESSION['usuario_id'] ?? null;
$hijoSeleccionado = $_GET['hijo_id'] ?? null;
$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;

$hijos = $model->obtenerHijosPorUsuario($usuarioId);
$pedidosSaldo = $model->obtenerPedidosSaldo($usuarioId, $desde, $hasta);
$pedidosComida = $model->obtenerPedidosComida($usuarioId, $hijoSeleccionado, $desde, $hasta);

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
            <td>
                <span class="badge <?= $saldo['Estado'] === 'Aprobado' ? 'success' : ($saldo['Estado'] === 'Cancelado' ? 'danger' : 'warning') ?>">
                    <?= $saldo['Estado'] ?>
                </span>
            </td>
            <td><?= $saldo['Fecha_pedido'] ?></td>
        </tr>
    <?php endforeach;
    $tablaSaldo = ob_get_clean();

    echo json_encode([
        'comida' => $tablaComida ?: '<tr><td colspan="4">No hay pedidos de comida.</td></tr>',
        'saldo' => $tablaSaldo ?: '<tr><td colspan="4">No hay pedidos de saldo.</td></tr>'
    ]);
    exit;
}

