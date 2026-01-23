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

require_once __DIR__ . '/../../config.php';

// Datos del usuario en sesion
$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
$correo = $_SESSION['correo'] ?? 'Sin correo';
$usuario = $_SESSION['usuario'] ?? 'Sin usuario';
$telefono = $_SESSION['telefono'] ?? 'Sin telefono';

$colegioId = filter_input(INPUT_GET, 'colegio', FILTER_VALIDATE_INT) ?: null;
$cursoId = filter_input(INPUT_GET, 'curso', FILTER_VALIDATE_INT) ?: null;
$fechaDesde = $_GET['fecha_desde'] ?? '';
$fechaHasta = $_GET['fecha_hasta'] ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
    $fechaDesde = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
    $fechaHasta = '';
}

$colegiosStmt = $pdo->query("SELECT Id, Nombre FROM Colegios ORDER BY Nombre");
$colegios = $colegiosStmt ? $colegiosStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$cursosSql = "SELECT Id, Nombre FROM Cursos";
$cursosParams = [];
if ($colegioId) {
    $cursosSql .= " WHERE Colegio_Id = :colegioId";
    $cursosParams['colegioId'] = $colegioId;
}
$cursosSql .= " ORDER BY Nombre";
$cursosStmt = $pdo->prepare($cursosSql);
$cursosStmt->execute($cursosParams);
$cursos = $cursosStmt->fetchAll(PDO::FETCH_ASSOC);

$buildUsuarioFilter = function ($usuarioField, $colegioId, $cursoId, &$params, $prefix) {
    $cond = [];
    if ($colegioId) {
        $cond[] = "h.Colegio_Id = :{$prefix}colegio";
        $params["{$prefix}colegio"] = $colegioId;
    }
    if ($cursoId) {
        $cond[] = "h.Curso_Id = :{$prefix}curso";
        $params["{$prefix}curso"] = $cursoId;
    }
    if (!$cond) {
        return '';
    }
    return "EXISTS (SELECT 1 FROM Usuarios_Hijos uh JOIN Hijos h ON h.Id = uh.Hijo_Id WHERE uh.Usuario_Id = {$usuarioField} AND " . implode(' AND ', $cond) . ")";
};

// KPIs: pedidos de comida
$pedidosParams = [];
$pedidosWhere = [];
if ($colegioId) {
    $pedidosWhere[] = "h.Colegio_Id = :colegioId";
    $pedidosParams['colegioId'] = $colegioId;
}
if ($cursoId) {
    $pedidosWhere[] = "h.Curso_Id = :cursoId";
    $pedidosParams['cursoId'] = $cursoId;
}
if ($fechaDesde) {
    $pedidosWhere[] = "pc.Fecha_pedido >= :fechaDesde";
    $pedidosParams['fechaDesde'] = $fechaDesde . ' 00:00:00';
}
if ($fechaHasta) {
    $pedidosWhere[] = "pc.Fecha_pedido <= :fechaHasta";
    $pedidosParams['fechaHasta'] = $fechaHasta . ' 23:59:59';
}

$pedidosSql = "SELECT COUNT(*) FROM Pedidos_Comida pc JOIN Hijos h ON h.Id = pc.Hijo_Id";
if ($pedidosWhere) {
    $pedidosSql .= " WHERE " . implode(' AND ', $pedidosWhere);
}
$pedidosStmt = $pdo->prepare($pedidosSql);
$pedidosStmt->execute($pedidosParams);
$totalPedidos = (int) $pedidosStmt->fetchColumn();

// KPI: usuarios registrados
$usuariosParams = [];
$usuariosWhere = [];
$usuariosFilter = $buildUsuarioFilter('u.Id', $colegioId, $cursoId, $usuariosParams, 'usr_');
if ($usuariosFilter) {
    $usuariosWhere[] = $usuariosFilter;
}
$usuariosSql = "SELECT COUNT(DISTINCT u.Id) FROM Usuarios u";
if ($usuariosWhere) {
    $usuariosSql .= " WHERE " . implode(' AND ', $usuariosWhere);
}
$usuariosStmt = $pdo->prepare($usuariosSql);
$usuariosStmt->execute($usuariosParams);
$totalUsuarios = (int) $usuariosStmt->fetchColumn();

// KPI: pedidos de saldo pendientes
$saldoPendParams = [];
$saldoPendWhere = ["ps.Estado = 'Pendiente de aprobacion'"];
$saldoPendFilter = $buildUsuarioFilter('ps.Usuario_Id', $colegioId, $cursoId, $saldoPendParams, 'sp_');
if ($saldoPendFilter) {
    $saldoPendWhere[] = $saldoPendFilter;
}
if ($fechaDesde) {
    $saldoPendWhere[] = "ps.Fecha_pedido >= :sp_fechaDesde";
    $saldoPendParams['sp_fechaDesde'] = $fechaDesde . ' 00:00:00';
}
if ($fechaHasta) {
    $saldoPendWhere[] = "ps.Fecha_pedido <= :sp_fechaHasta";
    $saldoPendParams['sp_fechaHasta'] = $fechaHasta . ' 23:59:59';
}
$saldoPendSql = "SELECT COUNT(*) FROM Pedidos_Saldo ps WHERE " . implode(' AND ', $saldoPendWhere);
$saldoPendStmt = $pdo->prepare($saldoPendSql);
$saldoPendStmt->execute($saldoPendParams);
$totalSaldoPendiente = (int) $saldoPendStmt->fetchColumn();

// KPI: saldo aprobado total
$saldoAprobParams = [];
$saldoAprobWhere = ["ps.Estado = 'Aprobado'"];
$saldoAprobFilter = $buildUsuarioFilter('ps.Usuario_Id', $colegioId, $cursoId, $saldoAprobParams, 'sa_');
if ($saldoAprobFilter) {
    $saldoAprobWhere[] = $saldoAprobFilter;
}
if ($fechaDesde) {
    $saldoAprobWhere[] = "ps.Fecha_pedido >= :sa_fechaDesde";
    $saldoAprobParams['sa_fechaDesde'] = $fechaDesde . ' 00:00:00';
}
if ($fechaHasta) {
    $saldoAprobWhere[] = "ps.Fecha_pedido <= :sa_fechaHasta";
    $saldoAprobParams['sa_fechaHasta'] = $fechaHasta . ' 23:59:59';
}
$saldoAprobSql = "SELECT COALESCE(SUM(ps.Saldo), 0) FROM Pedidos_Saldo ps WHERE " . implode(' AND ', $saldoAprobWhere);
$saldoAprobStmt = $pdo->prepare($saldoAprobSql);
$saldoAprobStmt->execute($saldoAprobParams);
$totalSaldoAprobado = (float) $saldoAprobStmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IlMana Gastronomia</title>
    <!-- Iconos de Material Design -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

    <!-- Framework Success desde CDN -->
    <link rel="stylesheet" href="https://framework.impulsagroup.com/assets/css/framework.css">
    <script src="https://framework.impulsagroup.com/assets/javascript/framework.js" defer></script>

    <!-- Descarga de consolidado (no se usa directamente aqui, pero se deja por consistencia) -->
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <!-- PDF: html2canvas + jsPDF (CDN gratuitos) -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Tablas con saltos de pagina prolijos (autoTable) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <!-- Graficos (Chart.js) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <span class="material-icons logo-icon">dashboard</span>
                <span class="logo-text">AMPD</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li onclick="location.href='admin_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='admin_altaUsuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">person</span><span class="link-text">Alta usuarios</span>
                    </li>
                    <li onclick="location.href='admin_importarUsuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">upload_file</span><span class="link-text">Carga Masiva</span>
                    </li>
                    <li onclick="location.href='admin_pagoFacturas.php'">
                        <span class="material-icons" style="color: #5b21b6;">attach_money</span><span class="link-text">Pago Facturas</span>
                    </li>
                    <li onclick="location.href='../../../logout.php'">
                        <span class="material-icons" style="color: red;">logout</span><span class="link-text">Salir</span>
                    </li>
                </ul>
            </nav>


            <div class="sidebar-footer">
                <button class="btn-icon" onclick="toggleSidebar()">
                    <span class="material-icons" id="collapseIcon">chevron_left</span>
                </button>
            </div>
        </aside>

        <!-- MAIN -->
        <div class="main">

            <!-- NAVBAR -->
            <header class="navbar">
                <button class="btn-icon" onclick="toggleSidebar()">
                    <span class="material-icons">menu</span>
                </button>
                <div class="navbar-title">Inicio</div>
            </header>

            <!-- CONTENIDO -->
            <section class="content">

                <!-- Bienvenida -->
                <div class="card">
                    <h2>Hola</h2>
                    <p>En esta pagina, vamos a tener KPI.</p>
                </div>

                <div class="card">
                    <form class="form-modern" method="get">
                        <div class="input-group">
                            <label>Colegio</label>
                            <div class="input-icon">
                                <span class="material-icons">school</span>
                                <select name="colegio">
                                    <option value="">Todos</option>
                                    <?php foreach ($colegios as $colegio): ?>
                                        <option value="<?= (int) $colegio['Id'] ?>" <?= $colegioId === (int) $colegio['Id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($colegio['Nombre'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Curso</label>
                            <div class="input-icon">
                                <span class="material-icons">class</span>
                                <select name="curso">
                                    <option value="">Todos</option>
                                    <?php foreach ($cursos as $curso): ?>
                                        <option value="<?= (int) $curso['Id'] ?>" <?= $cursoId === (int) $curso['Id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($curso['Nombre'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Fecha desde</label>
                            <div class="input-icon">
                                <span class="material-icons">event</span>
                                <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde) ?>">
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Fecha hasta</label>
                            <div class="input-icon">
                                <span class="material-icons">event</span>
                                <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta) ?>">
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Filtrar</button>
                            <a class="btn btn-cancelar" href="admin_dashboard.php">Limpiar</a>
                        </div>
                    </form>
                </div>

                <div class="card-grid grid-4">
                    <div class="card">
                        <h3>Pedidos realizados</h3>
                        <p><?= number_format($totalPedidos, 0, ',', '.') ?></p>
                    </div>
                    <div class="card">
                        <h3>Usuarios registrados</h3>
                        <p><?= number_format($totalUsuarios, 0, ',', '.') ?></p>
                    </div>
                    <div class="card">
                        <h3>Pedidos de saldo por aprobar</h3>
                        <p><?= number_format($totalSaldoPendiente, 0, ',', '.') ?></p>
                    </div>
                    <div class="card">
                        <h3>Saldo aprobado</h3>
                        <p>$<?= number_format($totalSaldoAprobado, 2, ',', '.') ?></p>
                    </div>
                </div>

                <div class="card">
                    <h3>Resumen de KPI</h3>
                    <canvas id="kpi-chart" height="110"></canvas>
                </div>

            </section>

        </div>
    </div>
    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
    <script>
        const kpiChartEl = document.getElementById("kpi-chart");
        if (kpiChartEl) {
            const kpiData = {
                labels: [
                    "Pedidos realizados",
                    "Usuarios registrados",
                    "Saldo por aprobar",
                    "Saldo aprobado"
                ],
                datasets: [{
                    label: "Totales",
                    data: [
                        <?= (int) $totalPedidos ?>,
                        <?= (int) $totalUsuarios ?>,
                        <?= (int) $totalSaldoPendiente ?>,
                        <?= json_encode(round($totalSaldoAprobado, 2)) ?>
                    ],
                    backgroundColor: ["#5b21b6", "#0f766e", "#f59e0b", "#16a34a"],
                    borderRadius: 6
                }]
            };

            new Chart(kpiChartEl, {
                type: "bar",
                data: kpiData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>
