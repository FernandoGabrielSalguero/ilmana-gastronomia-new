<?php
require_once __DIR__ . '/../models/papa_saldo_model.php';

$model = new PapaSaldoModel($pdo);

$usuarioId = $_SESSION['usuario_id'] ?? null;
$errores = [];
$exito = false;

$montosValidos = [3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 12000, 15000, 17000, 20000, 25000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000, 120000, 150000, 200000];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto = isset($_POST['monto']) ? (int)$_POST['monto'] : 0;

    if (!in_array($monto, $montosValidos, true)) {
        $errores[] = 'Monto no valido.';
    }

    $comprobante = $_FILES['comprobante'] ?? null;
    $comprobanteNombre = null;

    if (!$comprobante || $comprobante['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'Error al subir el comprobante.';
    } else {
        $ext = strtolower(pathinfo($comprobante['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($ext, $permitidos, true)) {
            $errores[] = 'Formato de comprobante no valido. Usa JPG, PNG o PDF.';
        }

        $targetDir = __DIR__ . '/../uploads/tax_invoices/';
        if (!is_dir($targetDir)) {
            $errores[] = 'No existe la carpeta de comprobantes.';
        }

        if (empty($errores)) {
            $baseName = pathinfo($comprobante['name'], PATHINFO_FILENAME);
            $baseName = preg_replace('/[^A-Za-z0-9._-]/', '_', $baseName);
            $comprobanteNombre = $usuarioId . '_' . time() . '_' . $baseName . '.' . $ext;
            $targetFile = $targetDir . $comprobanteNombre;

            if (!move_uploaded_file($comprobante['tmp_name'], $targetFile)) {
                $errores[] = 'No se pudo guardar el comprobante.';
            }
        }
    }

    if (empty($errores)) {
        if ($model->crearSolicitudSaldo($usuarioId, $monto, $comprobanteNombre)) {
            $exito = true;
        } else {
            $errores[] = 'Error al realizar el pedido de saldo.';
        }
    }
}
