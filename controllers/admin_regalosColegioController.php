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

require_once __DIR__ . '/../models/admin_regalosColegioModel.php';

$model = new AdminRegalosColegioModel($pdo);
$errores = [];
$mensaje = null;
$mensajeExito = null;

$normalizarFecha = function ($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if (!$dt) {
        return null;
    }
    $errors = DateTime::getLastErrors();
    if (!empty($errors['warning_count']) || !empty($errors['error_count'])) {
        return null;
    }
    return $dt->format('Y-m-d');
};

$hoy = new DateTime('now');
$inicioSemana = (clone $hoy)->modify('monday this week');
$finSemana = (clone $inicioSemana)->modify('thursday this week');

$fechaDesdeInput = $_GET['fecha_desde'] ?? '';
$fechaHastaInput = $_GET['fecha_hasta'] ?? '';

$fechaDesde = $normalizarFecha($fechaDesdeInput);
if ($fechaDesdeInput !== '' && !$fechaDesde) {
    $errores[] = 'La fecha desde no es valida.';
}
if (!$fechaDesde) {
    $fechaDesde = $inicioSemana->format('Y-m-d');
}

$fechaHasta = $normalizarFecha($fechaHastaInput);
if ($fechaHastaInput !== '' && !$fechaHasta) {
    $errores[] = 'La fecha hasta no es valida.';
}
if (!$fechaHasta) {
    $fechaHasta = $finSemana->format('Y-m-d');
}

if ($fechaHasta < $fechaDesde) {
    $errores[] = 'La fecha hasta era menor que la fecha desde. Se invirtio el rango.';
    $tmp = $fechaDesde;
    $fechaDesde = $fechaHasta;
    $fechaHasta = $tmp;
}

$contarDiasHabiles = function ($desde, $hasta) {
    $inicio = new DateTime($desde);
    $fin = new DateTime($hasta);
    $fin->setTime(0, 0, 0);
    $dias = 0;
    for ($d = clone $inicio; $d <= $fin; $d->modify('+1 day')) {
        $numero = (int) $d->format('N');
        if ($numero <= 4) {
            $dias++;
        }
    }
    return $dias;
};

$diasHabiles = $contarDiasHabiles($fechaDesde, $fechaHasta);
$juevesSemana = (new DateTime($fechaDesde))->modify('thursday this week')->format('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_regalo') {
    $alumno = trim((string) ($_POST['alumno_nombre'] ?? ''));
    $colegio = trim((string) ($_POST['colegio_nombre'] ?? ''));
    $curso = trim((string) ($_POST['curso_nombre'] ?? ''));
    $nivel = trim((string) ($_POST['nivel_educativo'] ?? ''));
    $fechaJueves = $normalizarFecha($_POST['fecha_entrega_jueves'] ?? '');
    $menusSemana = trim((string) ($_POST['menus_semana'] ?? ''));

    if ($alumno === '') {
        $errores[] = 'El nombre del alumno es obligatorio.';
    }
    if ($colegio === '') {
        $errores[] = 'El colegio es obligatorio.';
    }
    if ($curso === '') {
        $errores[] = 'El curso es obligatorio.';
    }
    if ($nivel === '') {
        $errores[] = 'El nivel educativo es obligatorio.';
    }
    if (!$fechaJueves) {
        $errores[] = 'La fecha de entrega (jueves) no es valida.';
    }
    $menusDecoded = json_decode($menusSemana, true);
    if (!is_array($menusDecoded)) {
        $errores[] = 'El detalle de menus no tiene un formato valido.';
    }

    if (empty($errores)) {
        $ok = $model->insertarRegalo([
            'alumno' => $alumno,
            'colegio' => $colegio,
            'curso' => $curso,
            'nivel' => $nivel,
            'fecha_jueves' => $fechaJueves,
            'menus' => $menusSemana
        ]);

        if ($ok) {
            $mensajeExito = 'Regalo registrado correctamente.';
        } else {
            $errores[] = 'No se pudo registrar el regalo. Intente nuevamente.';
        }
    }
}

$registros = $model->obtenerResumenSemanal($fechaDesde, $fechaHasta);
$resumenEntregas = $model->obtenerResumenPorEntrega($fechaDesde, $fechaHasta);

$totalNinos = count($registros);
$totalViandas = 0;
$totalCompletos = 0;

foreach ($registros as $row) {
    $totalViandas += (int) ($row['Total_Pedidos'] ?? 0);
    $diasConEntrega = (int) ($row['Dias_Con_Entrega'] ?? 0);
    if ($diasHabiles > 0 && $diasConEntrega >= $diasHabiles) {
        $totalCompletos++;
    }
}
