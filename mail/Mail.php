<?php

declare(strict_types=1);

namespace SVE\Mail;

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/lib/PHPMailer.php';
require_once __DIR__ . '/lib/SMTP.php';
require_once __DIR__ . '/lib/Exception.php';

final class Maill
{
    private static function baseMailer(?array &$debugLog = null): PHPMailer
    {
        $m = new PHPMailer(true);
        $host = getenv('SMTP_HOST') ?: '';
        $user = getenv('SMTP_USERNAME') ?: '';
        $pass = getenv('SMTP_PASSWORD') ?: '';
        $port = (int)(getenv('SMTP_PORT') ?: 0);
        $secure = getenv('SMTP_SECURE') ?: '';
        $debugEnabled = getenv('SMTP_DEBUG') === '1';

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

        if ($debugEnabled && $debugLog !== null) {
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
            $mail->Subject = 'Bienvenido a Il\'mana Gastronomia [' . $nombre . ']';
            $mail->Body    = $html;
            $mail->AltBody = 'Bienvenido a Il\'mana Gastronomia [' . $nombre . '] - Usuario: ' . $usuario;

            $mail->addAddress((string)($data['correo'] ?? ''), $nombre);

            $mail->send();
            return ['ok' => true];
        } catch (\Throwable $e) {
            $debugText = '';
            if (!empty($debugLog)) {
                $tail = array_slice($debugLog, -10);
                $debugText = ' SMTP Log: ' . implode(' | ', $tail);
            }
            return ['ok' => false, 'error' => $e->getMessage() . $debugText];
        }
    }
}
