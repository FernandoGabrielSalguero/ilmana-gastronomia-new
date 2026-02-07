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
$cursosDisponibles = [];
$alumnosCursos = [];
if ($representanteId) {
    $model = new RepresentanteDashboardModel($pdo);
    $cursosDisponibles = $model->obtenerCursosPorRepresentante($representanteId);
    $alumnosCursos = $model->obtenerAlumnosPorRepresentante($representanteId);
    $colegiosRepresentante = $model->obtenerColegiosPorRepresentante($representanteId);
    $regalosDia = $model->obtenerRegalosPorFechaYColegios($fechaEntrega, $colegiosRepresentante);
    $regalosIndex = [];
    foreach ($regalosDia as $regalo) {
        $alumnoNombre = strtolower(trim((string) ($regalo['Alumno_Nombre'] ?? '')));
        $colegioNombre = strtolower(trim((string) ($regalo['Colegio_Nombre'] ?? '')));
        if ($alumnoNombre !== '' && $colegioNombre !== '') {
            $regalosIndex[$alumnoNombre . '|' . $colegioNombre] = true;
        }
    }
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
        $colegioNombre = trim((string) ($row['Colegio_Nombre'] ?? ''));
        $regaloKey = strtolower($alumnoNombre) . '|' . strtolower($colegioNombre);
        $tieneRegalo = $alumnoNombre !== '' && $colegioNombre !== '' && !empty($regalosIndex[$regaloKey]);
        if ($alumnoNombre !== '') {
            $idx = $cursoIndex[$cursoId];
            $claveAlumno = $alumnoNombre . '|' . ($cancelado ? '1' : '0') . '|' . $motivoCancelacion;
            $alumnoItem = [
                'nombre' => $alumnoNombre,
                'cancelado' => $cancelado,
                'motivo' => $motivoCancelacion,
                'tiene_regalo' => $tieneRegalo
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

if (isset($_GET['ajax']) && $_GET['ajax'] === 'curso_detalle') {
    header('Content-Type: application/json');

    $cursoId = $_GET['curso_id'] ?? '';
    if ($cursoId === '') {
        echo json_encode(['ok' => false, 'error' => 'Curso invalido.']);
        exit;
    }

    if (!$representanteId) {
        echo json_encode(['ok' => false, 'error' => 'Sesion invalida.']);
        exit;
    }

    $colegiosRepresentante = $model->obtenerColegiosPorRepresentante($representanteId);
    $regalosDia = $model->obtenerRegalosPorFechaYColegios($fechaEntrega, $colegiosRepresentante);
    $regalosIndex = [];
    foreach ($regalosDia as $regalo) {
        $alumnoNombre = strtolower(trim((string) ($regalo['Alumno_Nombre'] ?? '')));
        $colegioNombre = strtolower(trim((string) ($regalo['Colegio_Nombre'] ?? '')));
        if ($alumnoNombre !== '' && $colegioNombre !== '') {
            $regalosIndex[$alumnoNombre . '|' . $colegioNombre] = true;
        }
    }

    $detalle = $model->obtenerDetalleCursoPedidos($representanteId, $cursoId, $fechaEntrega);
    $rows = $detalle['rows'] ?? [];
    $cursoNombre = 'Curso';
    $colegioNombre = 'Colegio';

    if (!empty($rows)) {
        $cursoNombre = trim((string) ($rows[0]['Curso'] ?? $cursoNombre));
        $colegioNombre = trim((string) ($rows[0]['Colegio'] ?? $colegioNombre));
    }

    $alumnos = [];
    foreach ($rows as $row) {
        $alumnoNombre = (string) ($row['Alumno'] ?? '');
        $colegioRow = (string) ($row['Colegio'] ?? $colegioNombre);
        $regaloKey = strtolower(trim($alumnoNombre)) . '|' . strtolower(trim($colegioRow));
        $alumnos[] = [
            'nombre' => $alumnoNombre,
            'estado' => $row['Estado'] ?? '',
            'menu' => $row['Menu'] ?? '',
            'preferencias' => $row['Preferencias'] ?? '',
            'motivo' => $row['motivo_cancelacion'] ?? '',
            'tiene_regalo' => !empty($regalosIndex[$regaloKey])
        ];
    }

    echo json_encode([
        'ok' => true,
        'colegio' => $colegioNombre,
        'curso' => $cursoNombre,
        'fecha' => $fechaEntrega,
        'viandas' => (int) ($detalle['viandas'] ?? 0),
        'alumnos' => $alumnos
    ]);
    exit;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === 'actualizar_curso') {
    header('Content-Type: application/json');

    if (!$representanteId) {
        echo json_encode(['ok' => false, 'error' => 'Sesion invalida.']);
        exit;
    }

    $hijoId = isset($_POST['hijo_id']) ? (int) $_POST['hijo_id'] : 0;
    $cursoId = $_POST['curso_id'] ?? '';
    if ($hijoId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Alumno invalido.']);
        exit;
    }

    $cursoIdFinal = null;
    $cursoNombre = 'Sin curso asignado';
    $cursoMap = [];
    foreach ($cursosDisponibles as $curso) {
        $cursoMap[(string) $curso['Id']] = $curso['Nombre'];
    }

    if ($cursoId !== '' && $cursoId !== 'sin_curso') {
        if (!isset($cursoMap[(string) $cursoId])) {
            echo json_encode(['ok' => false, 'error' => 'Curso invalido.']);
            exit;
        }
        $cursoIdFinal = (int) $cursoId;
        $cursoNombre = $cursoMap[(string) $cursoId];
    }

    $ok = $model->actualizarCursoHijo($representanteId, $hijoId, $cursoIdFinal);
    echo json_encode([
        'ok' => $ok,
        'cursoNombre' => $cursoNombre
    ]);
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'tabla_alumnos') {
    header('Content-Type: application/json');

    if (!$representanteId) {
        echo json_encode(['ok' => false, 'error' => 'Sesion invalida.']);
        exit;
    }

    $nombre = trim((string) ($_GET['nombre'] ?? ''));
    if (strlen($nombre) < 3) {
        $nombre = null;
    }

    $fechaFiltro = isset($_GET['fecha_entrega']) ? $fechaEntrega : null;
    $alumnosCursos = $model->obtenerAlumnosPorRepresentante($representanteId, $fechaFiltro, $nombre);

    ob_start();
    if (!empty($alumnosCursos)) {
        foreach ($alumnosCursos as $alumno) {
            $cursoActualNombre = trim((string) ($alumno['Curso'] ?? ''));
            if ($cursoActualNombre === '') {
                $cursoActualNombre = 'Sin curso asignado';
            }
            $cursoActualIdRaw = $alumno['Curso_Id'] ?? null;
            $cursoActualId = $cursoActualIdRaw ? (string) $cursoActualIdRaw : 'sin_curso';
            ?>
            <tr data-hijo-id="<?= (int) ($alumno['Id'] ?? 0) ?>">
                <td><?= (int) ($alumno['Id'] ?? 0) ?></td>
                <td><?= htmlspecialchars($alumno['Nombre'] ?? '') ?></td>
                <td class="curso-actual"><?= htmlspecialchars($cursoActualNombre) ?></td>
                <td>
                    <div class="input-icon input-icon-globe">
                        <select class="alumnos-select" data-curso-select
                            data-hijo-id="<?= (int) ($alumno['Id'] ?? 0) ?>"
                            data-prev="<?= htmlspecialchars((string) $cursoActualId) ?>">
                            <option value="sin_curso" <?= $cursoActualId === 'sin_curso' ? 'selected' : '' ?>>
                                Sin curso asignado
                            </option>
                            <?php foreach ($cursosDisponibles as $curso): ?>
                                <option value="<?= (int) $curso['Id'] ?>"
                                    <?= (string) $curso['Id'] === (string) $cursoActualId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($curso['Nombre'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </td>
            </tr>
            <?php
        }
    } else {
        ?>
        <tr>
            <td colspan="4" class="alumnos-empty">No hay alumnos disponibles.</td>
        </tr>
        <?php
    }
    $alumnosTablaHtml = ob_get_clean();

    echo json_encode([
        'ok' => true,
        'alumnosTablaHtml' => $alumnosTablaHtml
    ]);
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');

    $alumnosCursos = $model->obtenerAlumnosPorRepresentante($representanteId, $fechaEntrega);

    ob_start();
    if (!empty($cursosTarjetas)) {
        foreach ($cursosTarjetas as $curso) {
            ?>
            <div class="card curso-card">
                <div class="curso-card-header">
                    <h4><?= htmlspecialchars($curso['nombre']) ?></h4>
                    <button class="btn-icon curso-download" type="button"
                        data-descargar-curso
                        data-curso="<?= htmlspecialchars($curso['nombre']) ?>"
                        data-curso-id="<?= htmlspecialchars((string) $curso['id']) ?>"
                        data-fecha="<?= htmlspecialchars($fechaEntrega) ?>"
                        data-tooltip="Descargar imagen">
                        <span class="material-icons">download</span>
                    </button>
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
                                <?php if (!empty($alumno['tiene_regalo'])): ?>
                                    <span class="material-icons regalo-icon" title="Recibe regalo">card_giftcard</span>
                                <?php endif; ?>
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

    ob_start();
    if (!empty($alumnosCursos)) {
        foreach ($alumnosCursos as $alumno) {
            $cursoActualNombre = trim((string) ($alumno['Curso'] ?? ''));
            if ($cursoActualNombre === '') {
                $cursoActualNombre = 'Sin curso asignado';
            }
            $cursoActualIdRaw = $alumno['Curso_Id'] ?? null;
            $cursoActualId = $cursoActualIdRaw ? (string) $cursoActualIdRaw : 'sin_curso';
            ?>
            <tr data-hijo-id="<?= (int) ($alumno['Id'] ?? 0) ?>">
                <td><?= (int) ($alumno['Id'] ?? 0) ?></td>
                <td><?= htmlspecialchars($alumno['Nombre'] ?? '') ?></td>
                <td class="curso-actual"><?= htmlspecialchars($cursoActualNombre) ?></td>
                <td>
                    <div class="input-icon input-icon-globe">
                        <select class="alumnos-select" data-curso-select
                            data-hijo-id="<?= (int) ($alumno['Id'] ?? 0) ?>"
                            data-prev="<?= htmlspecialchars((string) $cursoActualId) ?>">
                            <option value="sin_curso" <?= $cursoActualId === 'sin_curso' ? 'selected' : '' ?>>
                                Sin curso asignado
                            </option>
                            <?php foreach ($cursosDisponibles as $curso): ?>
                                <option value="<?= (int) $curso['Id'] ?>"
                                    <?= (string) $curso['Id'] === (string) $cursoActualId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($curso['Nombre'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </td>
            </tr>
            <?php
        }
    } else {
        ?>
        <tr>
            <td colspan="4" class="alumnos-empty">No hay alumnos disponibles.</td>
        </tr>
        <?php
    }
    $alumnosTablaHtml = ob_get_clean();

    echo json_encode([
        'totalPedidos' => $totalPedidosDia,
        'fechaTexto' => date('d/m/Y', strtotime($fechaEntrega)),
        'cursosGridHtml' => $cursosGridHtml,
        'alumnosTablaHtml' => $alumnosTablaHtml
    ]);
    exit;
}
