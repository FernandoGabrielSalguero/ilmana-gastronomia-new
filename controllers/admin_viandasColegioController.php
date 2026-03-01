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

require_once __DIR__ . '/../models/admin_viandasColegioModel.php';

$model = new AdminViandasColegioModel($pdo);
$errores = [];
$mensaje = null;

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1');
$action = $_POST['action'] ?? $_GET['action'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$action) {
    $action = 'crear';
}

$respondJson = function (array $payload) {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
};

if ($action === 'list' && $isAjax) {
    $menuItems = $model->obtenerMenuActual();
    $respondJson([
        'ok' => true,
        'items' => $menuItems
    ]);
}

if ($action === 'list_descuentos' && $isAjax) {
    try {
        $descuentos = $model->obtenerDescuentos();
        $respondJson([
            'ok' => true,
            'items' => $descuentos
        ]);
    } catch (Exception $e) {
        $respondJson([
            'ok' => false,
            'mensaje' => 'No se pudo cargar descuentos. Verifica la tabla descuentos_colegios.'
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear_descuento') {
    $colegioIdInput = $_POST['colegio_id'] ?? '';
    $nivel = $_POST['nivel_educativo'] ?? '';
    $porcentajeInput = $_POST['porcentaje'] ?? '';
    $viandasPorDiaInput = $_POST['viandas_por_dia'] ?? '';
    $vigenciaDesde = $_POST['vigencia_desde'] ?? '';
    $vigenciaHasta = $_POST['vigencia_hasta'] ?? '';
    $diasObligatoriosInput = $_POST['dias_obligatorios'] ?? '';
    $terminos = trim($_POST['terminos'] ?? '');

    $nivelesValidos = ['Inicial', 'Primaria', 'Secundaria', 'Sin Curso Asignado'];
    if ($nivel === '' || !in_array($nivel, $nivelesValidos, true)) {
        $errores[] = 'Selecciona un nivel educativo valido.';
    }

    $colegioId = null;
    if ($colegioIdInput === '' || !is_numeric($colegioIdInput)) {
        $errores[] = 'Selecciona un colegio valido.';
    } else {
        $colegioId = (int)$colegioIdInput;
        if ($colegioId <= 0) {
            $errores[] = 'Selecciona un colegio valido.';
        }
    }

    $porcentaje = null;
    if ($porcentajeInput === '' || !is_numeric($porcentajeInput)) {
        $errores[] = 'El porcentaje debe ser numerico.';
    } else {
        $porcentaje = number_format((float)$porcentajeInput, 2, '.', '');
        if ($porcentaje < 0 || $porcentaje > 100) {
            $errores[] = 'El porcentaje debe estar entre 0 y 100.';
        }
    }

    $viandasPorDia = null;
    if ($viandasPorDiaInput === '' || !is_numeric($viandasPorDiaInput)) {
        $errores[] = 'La cantidad de viandas por dia es obligatoria.';
    } else {
        $viandasPorDia = (int)$viandasPorDiaInput;
        if ($viandasPorDia < 1 || $viandasPorDia > 3) {
            $errores[] = 'La cantidad de viandas por dia debe estar entre 1 y 3.';
        }
    }

    $fechaDesdeOk = DateTime::createFromFormat('Y-m-d', $vigenciaDesde) !== false;
    if ($vigenciaDesde === '' || !$fechaDesdeOk) {
        $errores[] = 'La vigencia desde es obligatoria.';
    }

    $fechaHastaOk = DateTime::createFromFormat('Y-m-d H:i:s', $vigenciaHasta) !== false;
    if ($vigenciaHasta === '' || !$fechaHastaOk) {
        $errores[] = 'La vigencia hasta debe tener fecha y hora.';
    }

    if ($fechaDesdeOk && $fechaHastaOk) {
        $fechaDesdeDt = new DateTime($vigenciaDesde);
        $fechaHastaDt = new DateTime($vigenciaHasta);
        if ($fechaHastaDt < $fechaDesdeDt) {
            $errores[] = 'La vigencia hasta no puede ser anterior a la vigencia desde.';
        }
    }

    $diasObligatorios = [];
    if ($diasObligatoriosInput !== '') {
        $diasParts = array_filter(array_map('trim', explode(',', $diasObligatoriosInput)));
        foreach ($diasParts as $fecha) {
            $fechaOk = DateTime::createFromFormat('Y-m-d', $fecha);
            if ($fechaOk === false) {
                $errores[] = 'Los dias obligatorios deben tener formato YYYY-MM-DD.';
                break;
            }
            $diasObligatorios[] = $fecha;
        }
    }

    if (empty($diasObligatorios)) {
        $errores[] = 'Selecciona al menos un dia obligatorio.';
    }

    if (empty($errores)) {
        $resultado = $model->crearDescuento([
            'colegio_id' => $colegioId,
            'nivel_educativo' => $nivel,
            'porcentaje' => $porcentaje,
            'viandas_por_dia' => $viandasPorDia,
            'vigencia_desde' => $vigenciaDesde,
            'vigencia_hasta' => $vigenciaHasta,
            'dias_obligatorios' => implode(',', $diasObligatorios),
            'terminos' => $terminos ?: null,
            'estado' => 'activo'
        ]);

        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'actualizar_descuento') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $colegioIdInput = $_POST['colegio_id'] ?? '';
    $nivel = $_POST['nivel_educativo'] ?? '';
    $porcentajeInput = $_POST['porcentaje'] ?? '';
    $viandasPorDiaInput = $_POST['viandas_por_dia'] ?? '';
    $vigenciaDesde = $_POST['vigencia_desde'] ?? '';
    $vigenciaHasta = $_POST['vigencia_hasta'] ?? '';
    $diasObligatoriosInput = $_POST['dias_obligatorios'] ?? '';
    $terminos = trim($_POST['terminos'] ?? '');

    if ($id <= 0) {
        $errores[] = 'Id invalido.';
    }

    $nivelesValidos = ['Inicial', 'Primaria', 'Secundaria', 'Sin Curso Asignado'];
    if ($nivel === '' || !in_array($nivel, $nivelesValidos, true)) {
        $errores[] = 'Selecciona un nivel educativo valido.';
    }

    $colegioId = null;
    if ($colegioIdInput === '' || !is_numeric($colegioIdInput)) {
        $errores[] = 'Selecciona un colegio valido.';
    } else {
        $colegioId = (int)$colegioIdInput;
        if ($colegioId <= 0) {
            $errores[] = 'Selecciona un colegio valido.';
        }
    }

    $porcentaje = null;
    if ($porcentajeInput === '' || !is_numeric($porcentajeInput)) {
        $errores[] = 'El porcentaje debe ser numerico.';
    } else {
        $porcentaje = number_format((float)$porcentajeInput, 2, '.', '');
        if ($porcentaje < 0 || $porcentaje > 100) {
            $errores[] = 'El porcentaje debe estar entre 0 y 100.';
        }
    }

    $viandasPorDia = null;
    if ($viandasPorDiaInput === '' || !is_numeric($viandasPorDiaInput)) {
        $errores[] = 'La cantidad de viandas por dia es obligatoria.';
    } else {
        $viandasPorDia = (int)$viandasPorDiaInput;
        if ($viandasPorDia < 1 || $viandasPorDia > 3) {
            $errores[] = 'La cantidad de viandas por dia debe estar entre 1 y 3.';
        }
    }

    $fechaDesdeOk = DateTime::createFromFormat('Y-m-d', $vigenciaDesde) !== false;
    if ($vigenciaDesde === '' || !$fechaDesdeOk) {
        $errores[] = 'La vigencia desde es obligatoria.';
    }

    $fechaHastaOk = DateTime::createFromFormat('Y-m-d H:i:s', $vigenciaHasta) !== false;
    if ($vigenciaHasta === '' || !$fechaHastaOk) {
        $errores[] = 'La vigencia hasta debe tener fecha y hora.';
    }

    if ($fechaDesdeOk && $fechaHastaOk) {
        $fechaDesdeDt = new DateTime($vigenciaDesde);
        $fechaHastaDt = new DateTime($vigenciaHasta);
        if ($fechaHastaDt < $fechaDesdeDt) {
            $errores[] = 'La vigencia hasta no puede ser anterior a la vigencia desde.';
        }
    }

    $diasObligatorios = [];
    if ($diasObligatoriosInput !== '') {
        $diasParts = array_filter(array_map('trim', explode(',', $diasObligatoriosInput)));
        foreach ($diasParts as $fecha) {
            $fechaOk = DateTime::createFromFormat('Y-m-d', $fecha);
            if ($fechaOk === false) {
                $errores[] = 'Los dias obligatorios deben tener formato YYYY-MM-DD.';
                break;
            }
            $diasObligatorios[] = $fecha;
        }
    }

    if (empty($diasObligatorios)) {
        $errores[] = 'Selecciona al menos un dia obligatorio.';
    }

    if (empty($errores)) {
        $resultado = $model->actualizarDescuento($id, [
            'colegio_id' => $colegioId,
            'nivel_educativo' => $nivel,
            'porcentaje' => $porcentaje,
            'viandas_por_dia' => $viandasPorDia,
            'vigencia_desde' => $vigenciaDesde,
            'vigencia_hasta' => $vigenciaHasta,
            'dias_obligatorios' => implode(',', $diasObligatorios),
            'terminos' => $terminos ?: null,
            'estado' => 'activo'
        ]);

        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'eliminar_descuento') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        $errores[] = 'Id invalido.';
    }

    if (empty($errores)) {
        $resultado = $model->eliminarDescuento($id);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');
    $fechaEntrega = $_POST['fecha_entrega'] ?? '';
    $fechaHoraCompra = $_POST['fecha_hora_compra'] ?? null;
    $fechaHoraCancelacion = $_POST['fecha_hora_cancelacion'] ?? null;
    $precioInput = $_POST['precio'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $niveles = $_POST['nivel_educativo'] ?? [];

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif ((function_exists('mb_strlen') ? mb_strlen($nombre) : strlen($nombre)) > 100) {
        $errores[] = 'El nombre no puede superar los 100 caracteres.';
    }

    if ($fechaEntrega === '') {
        $errores[] = 'La fecha de entrega es obligatoria.';
    }

    $estadosValidos = ['En venta', 'Sin stock'];
    if ($estado === '' || !in_array($estado, $estadosValidos, true)) {
        $errores[] = 'Selecciona un estado valido.';
    }

    $nivelesValidos = ['Inicial', 'Primaria', 'Secundaria', 'Sin Curso Asignado'];
    if (!is_array($niveles)) {
        $niveles = [$niveles];
    }
    $niveles = array_values(array_filter($niveles, function ($nivel) use ($nivelesValidos) {
        return in_array($nivel, $nivelesValidos, true);
    }));

    $precio = null;
    if ($precioInput !== '') {
        if (!is_numeric($precioInput)) {
            $errores[] = 'El precio debe ser numerico.';
        } else {
            $precio = number_format((float)$precioInput, 2, '.', '');
        }
    }

    $data = [
        'nombre' => $nombre,
        'fecha_entrega' => $fechaEntrega ?: null,
        'fecha_hora_compra' => $fechaHoraCompra ?: null,
        'fecha_hora_cancelacion' => $fechaHoraCancelacion ?: null,
        'precio' => $precio,
        'estado' => $estado
    ];

    if (empty($errores)) {
        $resultado = $model->crearMenu($data, $niveles);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'actualizar') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $fechaEntrega = $_POST['fecha_entrega'] ?? '';
    $fechaHoraCompra = $_POST['fecha_hora_compra'] ?? null;
    $fechaHoraCancelacion = $_POST['fecha_hora_cancelacion'] ?? null;
    $precioInput = $_POST['precio'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $nivel = $_POST['nivel_educativo'] ?? '';

    if ($id <= 0) {
        $errores[] = 'Id invalido.';
    }

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif ((function_exists('mb_strlen') ? mb_strlen($nombre) : strlen($nombre)) > 100) {
        $errores[] = 'El nombre no puede superar los 100 caracteres.';
    }

    if ($fechaEntrega === '') {
        $errores[] = 'La fecha de entrega es obligatoria.';
    }

    $estadosValidos = ['En venta', 'Sin stock'];
    if ($estado === '' || !in_array($estado, $estadosValidos, true)) {
        $errores[] = 'Selecciona un estado valido.';
    }

    $nivelesValidos = ['Inicial', 'Primaria', 'Secundaria', 'Sin Curso Asignado'];
    if (!in_array($nivel, $nivelesValidos, true)) {
        $errores[] = 'Selecciona un nivel educativo valido.';
    }

    $precio = null;
    if ($precioInput !== '') {
        if (!is_numeric($precioInput)) {
            $errores[] = 'El precio debe ser numerico.';
        } else {
            $precio = number_format((float)$precioInput, 2, '.', '');
        }
    }

    $data = [
        'nombre' => $nombre,
        'fecha_entrega' => $fechaEntrega ?: null,
        'fecha_hora_compra' => $fechaHoraCompra ?: null,
        'fecha_hora_cancelacion' => $fechaHoraCancelacion ?: null,
        'precio' => $precio,
        'estado' => $estado
    ];

    if (empty($errores)) {
        $resultado = $model->actualizarMenu($id, $data, $nivel);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'eliminar') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        $errores[] = 'Id invalido.';
    }

    if (empty($errores)) {
        $resultado = $model->eliminarMenu($id);
        if ($resultado['ok']) {
            $mensaje = $resultado['mensaje'];
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }

    if ($isAjax) {
        $respondJson([
            'ok' => empty($errores),
            'mensaje' => $mensaje,
            'errores' => $errores
        ]);
    }
}

$menuItems = $model->obtenerMenuActual();
$colegios = $model->obtenerColegios();
