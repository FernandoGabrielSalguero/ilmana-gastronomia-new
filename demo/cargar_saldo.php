<?php
session_start();
include '../includes/header_papas.php';
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'papas') {
    header("Location: ../index.php");
    exit();
}

$mostrarPopup = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_id = $_SESSION['usuario_id'];
    $monto = $_POST['monto'];
    $comprobante = $_FILES['comprobante'];

    // Validar monto
    $montos_validos = [3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 12000, 15000, 17000, 20000, 25000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000, 120000, 150000, 200000];
    if (!in_array($monto, $montos_validos)) {
        $error = "Monto no válido.";
    } else {
        // Validar y mover el archivo de comprobante
        $comprobante_nombre = $usuario_id . "_" . time() . "_" . basename($comprobante["name"]);
        $target_dir = "../uploads/comprobantes_inbox/";
        $target_file = $target_dir . $comprobante_nombre;
        if (move_uploaded_file($comprobante["tmp_name"], $target_file)) {
            // Insertar el pedido de saldo
            $stmt = $pdo->prepare("INSERT INTO Pedidos_Saldo (Usuario_Id, Saldo, Estado, Comprobante, Fecha_pedido) VALUES (?, ?, 'Pendiente de aprobaci�n', ?, NOW())");
            if ($stmt->execute([$usuario_id, $monto, $comprobante_nombre])) {
                $mostrarPopup = true;  // Esto indica que se debe mostrar el pop-up
            } else {
                $error = "Error al realizar el pedido de saldo.";
            }
        } else {
            $error = "Error al subir el comprobante.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar Saldo</title>
    <link rel="stylesheet" href="../css/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .bank-info {
            display: flex;
            flex-direction: column;
        }
        .bank-info-item {
            display: flex;
            align-items: center;
        }
        .bank-info-label {
            margin-right: 10px;
        }
        .copy-button {
            margin-left: 10px;
            cursor: pointer;
        }
        /* Estilos del pop-up */
        .popup {
            display: none; /* Por defecto está oculto */
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            border-radius: 10px;
            text-align: center;
        }
        .popup button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Cargar Saldo</h1>
    <?php
    if (isset($error)) {
        echo "<p class='error'>$error</p>";
    }
    ?>
    <form method="post" enctype="multipart/form-data" action="cargar_saldo.php">
        <label for="monto">Monto a recargar:</label>
        <select id="monto" name="monto" required>
            <option value="">Seleccione un monto</option>
            <?php foreach ([3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 12000, 15000, 17000, 20000, 25000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000, 120000, 150000, 200000] as $monto) : ?>
                <option value="<?php echo $monto; ?>"><?php echo number_format($monto, 2); ?> ARS</option>
            <?php endforeach; ?>
        </select>
        <br>
        <label for="comprobante">Comprobante:</label>
        <input type="file" id="comprobante" name="comprobante" accept=".jpg,.jpeg,.png,.pdf" required>
        <br>
        <button type="submit">Cargar Saldo</button>
    </form>
    <div class="bank-info">
        <div class="bank-info-item">
            <span class="bank-info-label">CUIT:</span>
            <span>20273627651</span>
        </div>
        <div class="bank-info-item">
            <span class="bank-info-label">CBU:</span>
            <span id="cbu">0340300408300313721004</span>
            <button class="copy-button" onclick="copiarCBU()">Copiar</button>
        </div>
        <div class="bank-info-item">
            <span class="bank-info-label">Banco:</span>
            <span>BANCO PATAGONIA</span>
        </div>
        <div class="bank-info-item">
            <span class="bank-info-label">Titular de la cuenta:</span>
            <span>Federico Figueroa</span>
        </div>
        <div class="bank-info-item">
            <span class="bank-info-label">Alias:</span>
            <span>ROJO.GENIO.CASINO</span>
        </div>
    </div>

    <!-- Pop-up -->
    <div id="popup" class="popup">
        <p>Pedido de saldo realizado con éxito. La acreditación puede demorar hasta 72hs</p>
        <button onclick="redirigirDashboard()">Aceptar</button>
    </div>

    <script>
        function copiarCBU() {
            const cbu = document.getElementById('cbu').innerText;
            navigator.clipboard.writeText(cbu).then(() => {
                alert("CBU copiado al portapapeles");
            });
        }

        function redirigirDashboard() {
            window.location.href = 'dashboard.php';
        }

        <?php if ($mostrarPopup) : ?>
        document.getElementById('popup').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>

