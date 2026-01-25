<?php
require_once __DIR__ . '/../models/representante_dashboardModel.php';

$representanteId = $_SESSION['usuario_id'] ?? null;
$fechaEntrega = $_GET['fecha_entrega'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEntrega)) {
    $fechaEntrega = date('Y-m-d');
}

$cursosTarjetas = [];
$resumenCursos = [];
$totalPedidosDia = 0;

if ($representanteId) {
    $model = new RepresentanteDashboardModel($pdo);
    $cursosAlumnos = $model->obtenerCursosConPedidos($representanteId, $fechaEntrega);
    $resumenCursosRaw = $model->obtenerResumenPedidosPorCurso($representanteId, $fechaEntrega);
    $totalPedidosDia = $model->obtenerTotalPedidosDia($representanteId, $fechaEntrega);

    $cursoIndex = [];
    foreach ($cursosAlumnos as $row) {
        $cursoId = $row['Curso_Id'] ?? 'sin_curso';
        $cursoNombre = trim((string) ($row['Curso_Nombre'] ?? ''));
        if ($cursoNombre === '') {
            $cursoNombre = 'Sin curso asignado';
        }

        if (!isset($cursoIndex[$cursoId])) {
            $cursoIndex[$cursoId] = count($cursosTarjetas);
            $cursosTarjetas[] = [
                'id' => $cursoId,
                'nombre' => $cursoNombre,
                'alumnos' => []
            ];
        }

        $alumnoNombre = trim((string) ($row['Alumno'] ?? ''));
        $estado = trim((string) ($row['Estado'] ?? ''));
        $cancelado = ($estado === 'Cancelado');
        $motivoCancelacion = trim((string) ($row['motivo_cancelacion'] ?? ''));
        if ($alumnoNombre !== '') {
            $idx = $cursoIndex[$cursoId];
            $claveAlumno = $alumnoNombre . '|' . ($cancelado ? '1' : '0') . '|' . $motivoCancelacion;
            $alumnoItem = [
                'nombre' => $alumnoNombre,
                'cancelado' => $cancelado,
                'motivo' => $motivoCancelacion
            ];
            if (!isset($cursosTarjetas[$idx]['alumnos_map'])) {
                $cursosTarjetas[$idx]['alumnos_map'] = [];
            }
            if (!isset($cursosTarjetas[$idx]['alumnos_map'][$claveAlumno])) {
                $cursosTarjetas[$idx]['alumnos_map'][$claveAlumno] = true;
                $cursosTarjetas[$idx]['alumnos'][] = $alumnoItem;
            }
        }
    }

    foreach ($resumenCursosRaw as $row) {
        $cursoNombre = trim((string) ($row['Curso_Nombre'] ?? ''));
        if ($cursoNombre === '') {
            $cursoNombre = 'Sin curso asignado';
        }
        $resumenCursos[] = [
            'nombre' => $cursoNombre,
            'total' => (int) ($row['Total'] ?? 0)
        ];
    }
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');

    ob_start();
    if (!empty($cursosTarjetas)) {
        foreach ($cursosTarjetas as $curso) {
            ?>
            <div class="card curso-card">
                <div class="curso-card-header">
                    <h4><?= htmlspecialchars($curso['nombre']) ?></h4>
                </div>
                <div class="curso-meta">
                    <span class="curso-icon">
                        <span class="material-icons">restaurant</span>
                    </span>
                    <span class="curso-count"><?= count($curso['alumnos']) ?> alumnos</span>
                </div>
                <?php if (!empty($curso['alumnos'])): ?>
                    <ul class="curso-alumnos">
                        <?php foreach ($curso['alumnos'] as $alumno): ?>
                            <li class="<?= !empty($alumno['cancelado']) ? 'is-cancelado' : '' ?>">
                                <?= htmlspecialchars($alumno['nombre']) ?>
                                <?php if (!empty($alumno['cancelado'])): ?>
                                    <span class="cancelacion-icon material-icons"
                                        title="<?= htmlspecialchars($alumno['motivo'] ?: 'Sin motivo') ?>">help_outline</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="curso-empty">Sin alumnos con pedidos.</div>
                <?php endif; ?>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="curso-empty">No hay cursos con pedidos para el dia.</div>
        <?php
    }
    $cursosGridHtml = ob_get_clean();

    echo json_encode([
        'totalPedidos' => $totalPedidosDia,
        'fechaTexto' => date('d/m/Y', strtotime($fechaEntrega)),
        'cursosGridHtml' => $cursosGridHtml
    ]);
    exit;
}
