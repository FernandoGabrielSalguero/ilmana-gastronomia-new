<?php

session_start();
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Metodo no permitido.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$evento = isset($payload['evento']) ? trim((string) $payload['evento']) : '';
if ($evento === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Evento requerido.']);
    exit;
}

$ok = registrarAuditoria($pdo, [
    'evento' => $evento,
    'modulo' => $payload['modulo'] ?? null,
    'entidad' => $payload['entidad'] ?? null,
    'entidad_id' => $payload['entidad_id'] ?? null,
    'estado' => $payload['estado'] ?? null,
    'codigo_http' => $payload['codigo_http'] ?? null,
    'datos' => $payload['datos'] ?? null,
]);

header('Content-Type: application/json');
echo json_encode(['ok' => (bool) $ok]);
