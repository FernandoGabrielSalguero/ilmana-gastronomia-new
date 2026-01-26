<?php

declare(strict_types=1);

namespace SVE\Mail;

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/lib/PHPMailer.php';
require_once __DIR__ . '/lib/SMTP.php';
require_once __DIR__ . '/lib/Exception.php';

final class Maill
{
    private static function formatMoney($value): string
    {
        return number_format((float)$value, 2, ',', '.');
    }

    private static function logEmail(array $data): void
    {
        try {
            global $pdo;
            if (!($pdo instanceof \PDO)) {
                return;
            }

            $meta = null;
            if (isset($data['meta']) && $data['meta'] !== null) {
                $meta = json_encode($data['meta']);
            }

            $stmt = $pdo->prepare(
                "INSERT INTO Correos_Log
                    (Usuario_Id, Correo, Nombre, Asunto, Template, Mensaje_HTML, Mensaje_Text, Estado, Error, Meta)
                 VALUES
                    (:usuario_id, :correo, :nombre, :asunto, :template, :mensaje_html, :mensaje_text, :estado, :error, :meta)"
            );

            $stmt->execute([
                'usuario_id' => $data['usuario_id'] ?? null,
                'correo' => $data['correo'] ?? '',
                'nombre' => $data['nombre'] ?? null,
                'asunto' => $data['asunto'] ?? '',
                'template' => $data['template'] ?? null,
                'mensaje_html' => $data['mensaje_html'] ?? null,
                'mensaje_text' => $data['mensaje_text'] ?? null,
                'estado' => $data['estado'] ?? 'fallido',
                'error' => $data['error'] ?? null,
                'meta' => $meta
            ]);
        } catch (\Throwable $e) {
            // No interrumpir el flujo principal si falla el log.
        }
    }

    private static function baseMailer(?array &$debugLog = null): PHPMailer
    {
        $m = new PHPMailer(true);
        $host = getenv('SMTP_HOST') ?: '';
        $user = getenv('SMTP_USERNAME') ?: '';
        $pass = getenv('SMTP_PASSWORD') ?: '';
        $port = (int)(getenv('SMTP_PORT') ?: 0);
        $secure = getenv('SMTP_SECURE') ?: '';

        if ($host === '' || $user === '' || $pass === '') {
            throw new \RuntimeException('Configuracion SMTP incompleta.');
        }

        if ($secure === '') {
            $secure = $port === 465 ? 'ssl' : 'tls';
        }
        if ($port <= 0) {
            $port = $secure === 'ssl' ? 465 : 587;
        }

        $from = getenv('MAIL_FROM') ?: $user;
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Il\'mana Gastronomia';

        $m->isSMTP();
        $m->Host       = $host;
        $m->SMTPAuth   = true;
        $m->Username   = $user;
        $m->Password   = $pass;
        if ($secure === 'ssl') {
            $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $m->SMTPAutoTLS = false;
        } elseif ($secure === 'tls') {
            $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $m->SMTPSecure = $secure;
        }
        $m->Port       = $port;
        $m->CharSet    = 'UTF-8';
        $m->setFrom($from, $fromName);
        $m->Sender = $from;
        $m->addReplyTo($from, $fromName);
        $m->isHTML(true);
        $m->Encoding   = 'base64';

        if ($debugLog !== null) {
            $m->SMTPDebug = 2;
            $m->Debugoutput = function ($str, $level) use (&$debugLog) {
                $line = trim((string)$str);
                if (stripos($line, 'CLIENT -> SERVER: AUTH') === 0) {
                    $debugLog[] = 'CLIENT -> SERVER: AUTH [redacted]';
                    return;
                }
                if (preg_match('/^CLIENT -> SERVER: [A-Za-z0-9+\\/=]+$/', $line) === 1) {
                    $debugLog[] = 'CLIENT -> SERVER: [redacted]';
                    return;
                }
                $debugLog[] = $line;
            };
        }

        return $m;
    }

    /**
     * Envia correo de bienvenida al crear un nuevo usuario.
     * $data = [
     *   'nombre' => string,
     *   'correo' => string,
     *   'usuario' => string,
     *   'contrasena' => string,
     *   'link' => string,
     *   'telefono' => string
     * ]
     * @return array{ok:bool, error?:string}
     */
    public static function enviarCorreoBienvenida(array $data): array
    {
        $debugLog = [];
        $mail = null;
        $html = '';
        try {
            $tplPath = __DIR__ . '/template/correo_bienvenida.html';
            $tpl = is_file($tplPath)
                ? file_get_contents($tplPath)
                : '<html><body style="font-family:Arial,sans-serif">{CONTENT}</body></html>';

            $nombre = (string)($data['nombre'] ?? '');
            $usuario = (string)($data['usuario'] ?? '');
            $contrasena = (string)($data['contrasena'] ?? '');
            $link = (string)($data['link'] ?? '');
            $telefono = (string)($data['telefono'] ?? '');

            $replacements = [
                '{{title}}' => 'Bienvenido a Il\'mana Gastronomia',
                '{{nombre}}' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
                '{{usuario}}' => htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'),
                '{{contrasena}}' => htmlspecialchars($contrasena, ENT_QUOTES, 'UTF-8'),
                '{{link}}' => htmlspecialchars($link, ENT_QUOTES, 'UTF-8'),
                '{{telefono}}' => htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8')
            ];

            if (strpos($tpl, '{CONTENT}') !== false) {
                $content = sprintf(
                    '<h1>Bienvenido a Il\'mana Gastronomia, %s</h1>
                    <p>Tu cuenta fue creada correctamente.</p>
                    <p><strong>Usuario:</strong> %s</p>
                    <p><strong>Contrasena:</strong> %s</p>
                    <p><strong>Link de acceso:</strong> %s</p>
                    <p><strong>Telefono de contacto:</strong> %s</p>',
                    $replacements['{{nombre}}'],
                    $replacements['{{usuario}}'],
                    $replacements['{{contrasena}}'],
                    $replacements['{{link}}'],
                    $replacements['{{telefono}}']
                );
                $html = str_replace('{CONTENT}', $content, $tpl);
            } else {
                $html = str_replace(array_keys($replacements), array_values($replacements), $tpl);
            }

            $mail = self::baseMailer($debugLog);
            $subject = 'Bienvenido a Il\'mana Gastronomia [' . $nombre . ']';
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = 'Bienvenido a Il\'mana Gastronomia [' . $nombre . '] - Usuario: ' . $usuario;

            $mail->addAddress((string)($data['correo'] ?? ''), $nombre);

            $mail->send();
            self::logEmail([
                'usuario_id' => $data['usuario_id'] ?? null,
                'correo' => $data['correo'] ?? '',
                'nombre' => $nombre,
                'asunto' => $subject,
                'template' => 'correo_bienvenida',
                'mensaje_html' => $html,
                'mensaje_text' => $mail->AltBody ?? null,
                'estado' => 'enviado',
                'meta' => $data['meta'] ?? null
            ]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            $mailError = $mail instanceof PHPMailer ? trim((string)$mail->ErrorInfo) : '';
            $debugText = '';
            if (!empty($debugLog)) {
                $tail = array_slice($debugLog, -10);
                $debugText = ' SMTP Log: ' . implode(' | ', $tail);
            }
            $errorBase = $e->getMessage();
            if ($mailError !== '' && stripos($errorBase, $mailError) === false) {
                $errorBase .= ' | ErrorInfo: ' . $mailError;
            }
            self::logEmail([
                'usuario_id' => $data['usuario_id'] ?? null,
                'correo' => $data['correo'] ?? '',
                'nombre' => $nombre ?? '',
                'asunto' => 'Bienvenido a Il\'mana Gastronomia',
                'template' => 'correo_bienvenida',
                'mensaje_html' => $html ?: null,
                'mensaje_text' => $mail instanceof PHPMailer ? ($mail->AltBody ?? null) : null,
                'estado' => 'fallido',
                'error' => $errorBase . $debugText,
                'meta' => $data['meta'] ?? null
            ]);
            return ['ok' => false, 'error' => $errorBase . $debugText];
        }
    }

    /**
     * Envia correo de actualizacion de perfil.
     * $data = [
     *   'nombre' => string,
     *   'correo' => string,
     *   'cambios' => array<int, array{campo:string, antes:string, despues:string}>,
     *   'estado_antes' => string,
     *   'estado_despues' => string
     * ]
     * @return array{ok:bool, error?:string}
     */
    public static function enviarActualizacionUsuario(array $data): array
    {
        $debugLog = [];
        $mail = null;
        $html = '';
        try {
            $tplPath = __DIR__ . '/template/actualizacion_usuarios.html';
            $tpl = is_file($tplPath)
                ? file_get_contents($tplPath)
                : '<html><body style="font-family:Arial,sans-serif">{CONTENT}</body></html>';

            $nombre = (string)($data['nombre'] ?? '');
            $cambios = (array)($data['cambios'] ?? []);

            $rows = '';
            foreach ($cambios as $cambio) {
                $rows .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                    htmlspecialchars((string)($cambio['campo'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($cambio['antes'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($cambio['despues'] ?? ''), ENT_QUOTES, 'UTF-8')
                );
            }

            $replacements = [
                '{{title}}' => 'Actualizacion de perfil',
                '{{nombre}}' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
                '{{cambios}}' => $rows,
                '{{estado_antes}}' => htmlspecialchars((string)($data['estado_antes'] ?? ''), ENT_QUOTES, 'UTF-8'),
                '{{estado_despues}}' => htmlspecialchars((string)($data['estado_despues'] ?? ''), ENT_QUOTES, 'UTF-8')
            ];

            if (strpos($tpl, '{CONTENT}') !== false) {
                $content = sprintf(
                    '<h1>%s, actualizamos tu perfil</h1>
                    <table><thead><tr><th>Campo</th><th>Antes</th><th>Despues</th></tr></thead><tbody>%s</tbody></table>
                    <p>Estado anterior: %s</p>
                    <p>Estado actual: %s</p>',
                    $replacements['{{nombre}}'],
                    $replacements['{{cambios}}'],
                    $replacements['{{estado_antes}}'],
                    $replacements['{{estado_despues}}']
                );
                $html = str_replace('{CONTENT}', $content, $tpl);
            } else {
                $html = str_replace(array_keys($replacements), array_values($replacements), $tpl);
            }

            $mail = self::baseMailer($debugLog);
            $subject = $nombre . ' actualizamos tu perfil';
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $nombre . ' actualizamos tu perfil';

            $mail->addAddress((string)($data['correo'] ?? ''), $nombre);

            $mail->send();
            self::logEmail([
                'usuario_id' => $data['usuario_id'] ?? null,
                'correo' => $data['correo'] ?? '',
                'nombre' => $nombre,
                'asunto' => $subject,
                'template' => 'actualizacion_usuarios',
                'mensaje_html' => $html,
                'mensaje_text' => $mail->AltBody ?? null,
                'estado' => 'enviado',
                'meta' => $data['meta'] ?? null
            ]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            $mailError = $mail instanceof PHPMailer ? trim((string)$mail->ErrorInfo) : '';
            $debugText = '';
            if (!empty($debugLog)) {
                $tail = array_slice($debugLog, -10);
                $debugText = ' SMTP Log: ' . implode(' | ', $tail);
            }
            $errorBase = $e->getMessage();
            if ($mailError !== '' && stripos($errorBase, $mailError) === false) {
                $errorBase .= ' | ErrorInfo: ' . $mailError;
            }
            self::logEmail([
                'usuario_id' => $data['usuario_id'] ?? null,
                'correo' => $data['correo'] ?? '',
                'nombre' => $nombre ?? '',
                'asunto' => $nombre ? ($nombre . ' actualizamos tu perfil') : 'Actualizamos tu perfil',
                'template' => 'actualizacion_usuarios',
                'mensaje_html' => $html ?: null,
                'mensaje_text' => $mail instanceof PHPMailer ? ($mail->AltBody ?? null) : null,
                'estado' => 'fallido',
                'error' => $errorBase . $debugText,
                'meta' => $data['meta'] ?? null
            ]);
            return ['ok' => false, 'error' => $errorBase . $debugText];
        }
    }

    /**
     * Envia correo de gestion de saldo (aprobado o cancelado).
     * $data = [
     *   'nombre' => string,
     *   'correo' => string,
     *   'accion' => string,
     *   'saldo_actual' => float,
     *   'saldo_nuevo' => float|null,
     *   'motivo' => string|null
     * ]
     * @return array{ok:bool, error?:string}
     */
    public static function enviarGestionSaldo(array $data): array
    {
        $debugLog = [];
        $mail = null;
        $html = '';
        try {
            $tplPath = __DIR__ . '/template/gestion_saldos_escuelas.html';
            $tpl = is_file($tplPath)
                ? file_get_contents($tplPath)
                : '<html><body style="font-family:Arial,sans-serif">{CONTENT}</body></html>';

            $nombre = (string)($data['nombre'] ?? '');
            $accion = (string)($data['accion'] ?? '');
            $saldoActual = self::formatMoney($data['saldo_actual'] ?? 0);
            $saldoNuevoRaw = $data['saldo_nuevo'] ?? null;
            $motivoRaw = trim((string)($data['motivo'] ?? ''));

            if ($accion === 'Aprobado') {
                $saldoNuevo = self::formatMoney($saldoNuevoRaw ?? 0);
                $detalle = '<tr><td>Saldo nuevo</td><td>' . htmlspecialchars($saldoNuevo, ENT_QUOTES, 'UTF-8') . '</td></tr>';
            } else {
                $motivo = $motivoRaw !== '' ? $motivoRaw : 'Sin observaciones';
                $detalle = '<tr><td>Motivo de cancelacion</td><td>' . htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }

            $replacements = [
                '{{title}}' => 'Gestion de saldo I\'lMana',
                '{{nombre}}' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
                '{{accion}}' => htmlspecialchars($accion, ENT_QUOTES, 'UTF-8'),
                '{{saldo_actual}}' => htmlspecialchars($saldoActual, ENT_QUOTES, 'UTF-8'),
                '{{detalle}}' => $detalle
            ];

            if (strpos($tpl, '{CONTENT}') !== false) {
                $content = sprintf(
                    '<h1>Gestion de saldo</h1>
                    <p>Hola %s, %s tu solicitud de saldo.</p>
                    <p><strong>Saldo actual:</strong> %s</p>
                    %s',
                    $replacements['{{nombre}}'],
                    $replacements['{{accion}}'],
                    $replacements['{{saldo_actual}}'],
                    $detalle
                );
                $html = str_replace('{CONTENT}', $content, $tpl);
            } else {
                $html = str_replace(array_keys($replacements), array_values($replacements), $tpl);
            }

            $mail = self::baseMailer($debugLog);
            $subject = 'Gestion de saldo I\'lMana';
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = 'Gestion de saldo I\'lMana';

            $mail->addAddress((string)($data['correo'] ?? ''), $nombre);

            $mail->send();
            self::logEmail([
                'usuario_id' => $data['usuario_id'] ?? null,
                'correo' => $data['correo'] ?? '',
                'nombre' => $nombre,
                'asunto' => $subject,
                'template' => 'gestion_saldos_escuelas',
                'mensaje_html' => $html,
                'mensaje_text' => $mail->AltBody ?? null,
                'estado' => 'enviado',
                'meta' => $data['meta'] ?? null
            ]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            $mailError = $mail instanceof PHPMailer ? trim((string)$mail->ErrorInfo) : '';
            $debugText = '';
            if (!empty($debugLog)) {
                $tail = array_slice($debugLog, -10);
                $debugText = ' SMTP Log: ' . implode(' | ', $tail);
            }
            $errorBase = $e->getMessage();
            if ($mailError !== '' && stripos($errorBase, $mailError) === false) {
                $errorBase .= ' | ErrorInfo: ' . $mailError;
            }
            self::logEmail([
                'usuario_id' => $data['usuario_id'] ?? null,
                'correo' => $data['correo'] ?? '',
                'nombre' => $nombre ?? '',
                'asunto' => 'Gestion de saldo I\'lMana',
                'template' => 'gestion_saldos_escuelas',
                'mensaje_html' => $html ?: null,
                'mensaje_text' => $mail instanceof PHPMailer ? ($mail->AltBody ?? null) : null,
                'estado' => 'fallido',
                'error' => $errorBase . $debugText,
                'meta' => $data['meta'] ?? null
            ]);
            return ['ok' => false, 'error' => $errorBase . $debugText];
        }
    }
}
