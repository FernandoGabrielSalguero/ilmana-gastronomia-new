<?php
require_once __DIR__ . '/../models/representante_dashboardModel.php';

$representanteId = $_SESSION['usuario_id'] ?? null;
$fechaEntrega = date('Y-m-d');

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
        if ($alumnoNombre !== '') {
            $idx = $cursoIndex[$cursoId];
            if (!in_array($alumnoNombre, $cursosTarjetas[$idx]['alumnos'], true)) {
                $cursosTarjetas[$idx]['alumnos'][] = $alumnoNombre;
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
