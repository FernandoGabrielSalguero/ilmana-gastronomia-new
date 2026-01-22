<?php

    declare(strict_types=1);

    namespace SVE\Mail;

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once __DIR__ . '/lib/PHPMailer.php';
    require_once __DIR__ . '/lib/SMTP.php';
    require_once __DIR__ . '/lib/Exception.php';

    final class Maill
    {
        private static function baseMailer(): PHPMailer
        {
            $m = new PHPMailer(true);
            $m->isSMTP();
            $m->Host       = \MAIL_HOST;
            $m->SMTPAuth   = true;
            $m->Username   = \MAIL_USER;
            $m->Password   = \MAIL_PASS;
            $m->SMTPSecure = \MAIL_SECURE;
            $m->Port       = \MAIL_PORT;
            $m->CharSet    = 'UTF-8';
            $m->setFrom(\MAIL_FROM, \MAIL_FROM_NAME);
            $m->addReplyTo(\MAIL_FROM, \MAIL_FROM_NAME);
            $m->isHTML(true);
            $m->Encoding   = 'base64';

            return $m;
        }

        private static function formatDateShort(?string $value): string
        {
            if ($value === null || $value === '') {
                return '-';
            }

            $s = (string)$value;
            $soloFecha = explode(' ', $s)[0];
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $soloFecha) === 1) {
                [$y, $m, $d] = explode('-', $soloFecha);
                return $d . '/' . $m . '/' . $y;
            }

            return $s;
        }

        private static function normalizarSeguroFlete(?string $valor): string
        {
            $raw = strtolower(trim((string)$valor));
            if ($raw === '' || $raw === 'sin definir' || $raw === 'sin_definir') {
                return 'Sin definir';
            }
            if ($raw === 'si' || $raw === '1') {
                return 'Si';
            }
            if ($raw === 'no' || $raw === '0') {
                return 'No';
            }
            return 'Sin definir';
        }

        /**
         * Envia correo de cierre de operativo de Cosecha Mecanica con detalle del contrato.
         * $data = [
         *   'cooperativa_nombre' => string,
         *   'cooperativa_correo' => string,
         *   'operativo' => [id,nombre,fecha_apertura,fecha_cierre,descripcion,estado],
         *   'participaciones' => [ ['productor'=>..., 'finca_id'=>..., 'superficie'=>..., 'variedad'=>..., 'prod_estimada'=>..., 'fecha_estimada'=>..., 'km_finca'=>..., 'flete'=>..., 'seguro_flete'=>...], ... ],
         *   'firma_fecha' => ?string
         * ]
         * @return array{ok:bool, error?:string}
         */
        public static function enviarCierreCosechaMecanica(array $data): array
        {
            try {
                $tplPath = __DIR__ . '/template/base.html';
                $tpl = is_file($tplPath) ? file_get_contents($tplPath) : '<html><body style="font-family:Arial,sans-serif">{{content}}</body></html>';

                $op = $data['operativo'] ?? [];
                $participaciones = $data['participaciones'] ?? [];
                $nombreCoop = (string)($data['cooperativa_nombre'] ?? 'Cooperativa');

                $descripcionRaw = (string)($op['descripcion'] ?? '');
                $descripcionHtml = trim($descripcionRaw);
                if ($descripcionHtml !== '') {
                    $descripcionHtml = html_entity_decode($descripcionHtml, ENT_QUOTES, 'UTF-8');
                    $descripcionHtml = strip_tags($descripcionHtml, '<p><br><strong><b><em><i><u><ul><ol><li><span><div><table><thead><tbody><tr><th><td>');
                } else {
                    $descripcionHtml = '-';
                }

                $fechaApertura = self::formatDateShort($op['fecha_apertura'] ?? null);
                $fechaCierre = self::formatDateShort($op['fecha_cierre'] ?? null);
                $fechaFirma = self::formatDateShort($data['firma_fecha'] ?? null);
                $estado = (string)($op['estado'] ?? 'cerrado');

                $rows = '';
                foreach ((array)$participaciones as $p) {
                    $rows .= sprintf(
                        '<tr>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                        </tr>',
                        htmlspecialchars((string)($p['productor'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)($p['finca_id'] ?? '-'), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)($p['superficie'] ?? 0), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)($p['variedad'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)($p['prod_estimada'] ?? 0), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars(self::formatDateShort($p['fecha_estimada'] ?? null), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)($p['km_finca'] ?? 0), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)($p['flete'] ?? 0), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars(self::normalizarSeguroFlete($p['seguro_flete'] ?? null), ENT_QUOTES, 'UTF-8')
                    );
                }
                if ($rows === '') {
                    $rows = '<tr><td colspan="9" style="text-align:center;color:#6b7280;">Sin productores inscriptos</td></tr>';
                }

                $content = sprintf(
                    '<h1>Cierre de operativo - Cosecha Mecanica</h1>
                    <p>Hola %s, el operativo ya fue cerrado. Este es el detalle del contrato:</p>
                    <h2 style="font-size:14px;margin:16px 0 8px 0;">Datos del contrato</h2>
                    <table cellpadding="8" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;">
                        <tbody>
                            <tr><td style="width:32%%;background:#f9fafb;">Operativo</td><td>%s</td></tr>
                            <tr><td style="background:#f9fafb;">Apertura</td><td>%s</td></tr>
                            <tr><td style="background:#f9fafb;">Cierre</td><td>%s</td></tr>
                            <tr><td style="background:#f9fafb;">Estado</td><td>%s</td></tr>
                            <tr><td style="background:#f9fafb;">Descripcion</td><td style="white-space:pre-wrap;">%s</td></tr>
                            <tr><td style="background:#f9fafb;">Contrato firmado</td><td>Si</td></tr>
                            <tr><td style="background:#f9fafb;">Fecha firma</td><td>%s</td></tr>
                        </tbody>
                    </table>
                    <h2 style="font-size:14px;margin:16px 0 8px 0;">Anexo 1 - Productores inscriptos</h2>
                    <table cellpadding="8" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f3f4f6;">
                                <th style="text-align:left;">Productor</th>
                                <th style="text-align:left;">Finca ID</th>
                                <th style="text-align:left;">Superficie</th>
                                <th style="text-align:left;">Variedad</th>
                                <th style="text-align:left;">Prod. estimada</th>
                                <th style="text-align:left;">Fecha estimada</th>
                                <th style="text-align:left;">KM finca</th>
                                <th style="text-align:left;">Flete</th>
                                <th style="text-align:left;">Seguro flete</th>
                            </tr>
                        </thead>
                        <tbody>%s</tbody>
                    </table>',
                    htmlspecialchars($nombreCoop, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($op['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($fechaApertura, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($fechaCierre, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'),
                    $descripcionHtml,
                    htmlspecialchars($fechaFirma, ENT_QUOTES, 'UTF-8'),
                    $rows
                );

                $html = str_replace(
                    ['{{title}}', '{{content}}'],
                    ['Cierre operativo Cosecha Mecanica', $content],
                    $tpl
                );

                $mail = self::baseMailer();
                $mail->Subject = 'SVE: Cierre de operativo de Cosecha Mecanica';
                $mail->Body    = $html;
                $mail->AltBody = 'Cierre de operativo de Cosecha Mecanica - ' . (string)($op['nombre'] ?? '');

                $mail->addAddress((string)($data['cooperativa_correo'] ?? ''), $nombreCoop);

                $mail->send();
                return ['ok' => true];
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => $e->getMessage()];
            }
        }

        /**
         * Envía correo “Pedido creado de compra conjuntas”.
         * $data = [
         *   'cooperativa_nombre' => string,
         *   'cooperativa_correo' => string|null,
         *   'operativo_nombre'   => string,
         *   'items' => [ ['nombre'=>..., 'cantidad'=>float, 'unidad'=>string, 'precio'=>float, 'alicuota'=>float, 'subtotal'=>float, 'iva'=>float, 'total'=>float], ... ],
         *   'totales' => ['sin_iva'=>float,'iva'=>float,'con_iva'=>float],
         * ]
         * @return array{ok:bool, error?:string}
         */
        public static function enviarPedidoCreado(array $data): array
        {
            try {
                $tplPath = __DIR__ . '/template/pedido_creado.html';
                $tpl = is_file($tplPath) ? file_get_contents($tplPath) : '<html><body style="font-family:Arial,sans-serif">{CONTENT}</body></html>';

                $rows = '';
                foreach ($data['items'] as $it) {
                    $rows .= sprintf(
                        '<tr><td>%s</td><td style="text-align:right;">%s</td><td>%s</td><td style="text-align:right;">$%0.2f</td><td style="text-align:right;">%0.2f%%</td><td style="text-align:right;">$%0.2f</td><td style="text-align:right;">$%0.2f</td></tr>',
                        htmlspecialchars((string)$it['nombre'], ENT_QUOTES, 'UTF-8'),
                        number_format((float)$it['cantidad'], 2, ',', '.'),
                        htmlspecialchars((string)($it['unidad'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        (float)$it['precio'],
                        (float)$it['alicuota'],
                        (float)$it['subtotal'],
                        (float)$it['total']
                    );
                }

                $content = sprintf(
                    '<h2>Nuevo pedido en Mercado Digital</h2>
                    <p>La cooperativa <strong>%s</strong> generó un pedido para el operativo <strong>%s</strong>.</p>
                    <table cellpadding="8" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f3f4f6;">
                                <th style="text-align:left;">Producto</th>
                                <th style="text-align:right;">Cant.</th>
                                <th>Unidad</th>
                                <th style="text-align:right;">Precio</th>
                                <th style="text-align:right;">IVA</th>
                                <th style="text-align:right;">Subtotal</th>
                                <th style="text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>%s</tbody>
                    </table>
                    <p style="margin-top:12px;">
                    <strong>Total s/IVA:</strong> $%0.2f<br/>
                    <strong>IVA:</strong> $%0.2f<br/>
                    <strong>Total c/IVA:</strong> $%0.2f
                    </p>',
                    htmlspecialchars((string)$data['cooperativa_nombre'], ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)$data['operativo_nombre'], ENT_QUOTES, 'UTF-8'),
                    $rows,
                    (float)$data['totales']['sin_iva'],
                    (float)$data['totales']['iva'],
                    (float)$data['totales']['con_iva']
                );

                $html = str_replace('{CONTENT}', $content, $tpl);

                $mail = self::baseMailer();
                $mail->Subject = '🟣 SVE: Nuevo pedido creado';
                $mail->Body    = $html;
                $mail->AltBody = 'Nuevo pedido creado - ' . $data['cooperativa_nombre'] . ' - ' . $data['operativo_nombre'];

                // Destinatarios
                if (!empty($data['cooperativa_correo'])) {
                    $mail->addAddress((string)$data['cooperativa_correo']);
                }
                $mail->addAddress('lacruzg@coopsve.com', 'La Cruz');

                $mail->send();
                return ['ok' => true];
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => $e->getMessage()];
            }
        }

        /**
         * Envía correo “Solicitud de pulverización con dron”.
         * @param array $data
         *  [
         *    'solicitud_id'=>int,
         *    'productor'=>['nombre'=>string,'correo'=>string],
         *    'cooperativa'=>['nombre'=>string,'correo'=>?string],
         *    'superficie_ha'=>float,
         *    'forma_pago'=>string,
         *    'motivos'=>string[],
         *    'rangos'=>string[],
         *    'productos'=> [ ['patologia'=>string,'fuente'=>'sve'|'yo','detalle'=>string], ... ],
         *    'direccion'=> ['provincia'=>?string,'localidad'=>?string,'calle'=>?string,'numero'=>?string],
         *    'ubicacion'=> ['en_finca'=>'si'|'no', 'lat'=>?string,'lng'=>?string,'acc'=>?string,'timestamp'=>?string],
         *    'costos'=> ['moneda'=>string,'base'=>float,'productos'=>float,'total'=>float,'costo_ha'=>float],
         *  ]
         * @return array{ok:bool,error?:string}
         */
        public static function enviarSolicitudDron(array $data): array
        {
            try {
                $tplPath = __DIR__ . '/template/dron_solicitud.html';
                $tpl = is_file($tplPath) ? file_get_contents($tplPath) : '<html><body style="font-family:Arial,sans-serif">{CONTENT}</body></html>';

                $prodRows = '';
                foreach ((array)($data['productos'] ?? []) as $p) {
                    $prodRows .= sprintf(
                        '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                        htmlspecialchars((string)($p['patologia'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)($p['fuente'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)($p['detalle'] ?? ''), ENT_QUOTES, 'UTF-8')
                    );
                }
                if ($prodRows === '') {
                    $prodRows = '<tr><td colspan="3" style="text-align:center;color:#6b7280;">Sin productos</td></tr>';
                }

                $motivos = implode(', ', array_map(fn($m) => htmlspecialchars((string)$m, ENT_QUOTES, 'UTF-8'), (array)($data['motivos'] ?? [])));
                $rangos  = implode(', ', array_map(fn($r) => htmlspecialchars((string)$r, ENT_QUOTES, 'UTF-8'), (array)($data['rangos'] ?? [])));
                $dir     = $data['direccion'] ?? [];
                $dirText = trim(
                    (($dir['calle'] ?? '') . ' ' . ($dir['numero'] ?? '')) . ', ' .
                        ($dir['localidad'] ?? '') . ', ' . ($dir['provincia'] ?? ''),
                    " ,"
                );

                $ubi     = $data['ubicacion'] ?? [];
                $ubiText = sprintf(
                    'En finca: %s%s',
                    (($ubi['en_finca'] ?? '') === 'si' ? 'Sí' : 'No'),
                    (!empty($ubi['lat']) && !empty($ubi['lng'])) ? sprintf(' — (%.6f, %.6f)', (float)$ubi['lat'], (float)$ubi['lng']) : ''
                );

                $costos = $data['costos'] ?? ['moneda' => 'Pesos', 'base' => 0, 'productos' => 0, 'total' => 0, 'costo_ha' => 0];

                $content = sprintf(
                    '<h2 style="margin:0 0 8px 0;">Nueva solicitud de pulverización con dron</h2>
                    <p style="margin:0 0 14px 0;color:#374151;">ID solicitud: <strong>#%d</strong></p>

                    <table cellpadding="8" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;margin-bottom:12px;">
                    <tbody>
                        <tr><td style="width:35%%;background:#f9fafb;">Productor</td><td><strong>%s</strong> &lt;%s&gt;</td></tr>
                        <tr><td style="background:#f9fafb;">Cooperativa</td><td>%s</td></tr>
                        <tr><td style="background:#f9fafb;">Superficie</td><td>%0.2f ha</td></tr>
                        <tr><td style="background:#f9fafb;">Forma de pago</td><td>%s</td></tr>
                        <tr><td style="background:#f9fafb;">Motivo(s)</td><td>%s</td></tr>
                        <tr><td style="background:#f9fafb;">Rango deseado</td><td>%s</td></tr>
                        <tr><td style="background:#f9fafb;">Dirección</td><td>%s</td></tr>
                        <tr><td style="background:#f9fafb;">Ubicación</td><td>%s</td></tr>
                    </tbody>
                    </table>

                    <h3 style="margin:14px 0 6px 0;">Productos</h3>
                    <table cellpadding="8" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f3f4f6;">
                        <th style="text-align:left;">Patología</th>
                        <th style="text-align:left;">Fuente</th>
                        <th style="text-align:left;">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>%s</tbody>
                    </table>

                    <h3 style="margin:16px 0 6px 0;">Costo estimado</h3>
                    <table cellpadding="8" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;">
                    <tbody>
                        <tr><td style="width:35%%;background:#f9fafb;">Servicio base</td><td>%s %0.2f</td></tr>
                        <tr><td style="background:#f9fafb;">Productos SVE</td><td>%s %0.2f</td></tr>
                        <tr><td style="background:#f9fafb;"><strong>Total</strong></td><td><strong>%s %0.2f</strong></td></tr>
                    </tbody>
                    </table>',
                    (int)($data['solicitud_id'] ?? 0),
                    htmlspecialchars((string)($data['productor']['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($data['productor']['correo'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($data['cooperativa']['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    (float)($data['superficie_ha'] ?? 0),
                    htmlspecialchars((string)($data['forma_pago'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    $motivos ?: '—',
                    $rangos ?: '—',
                    htmlspecialchars($dirText ?: '—', ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($ubiText, ENT_QUOTES, 'UTF-8'),
                    $prodRows,
                    htmlspecialchars((string)$costos['moneda'], ENT_QUOTES, 'UTF-8'),
                    (float)$costos['base'],
                    htmlspecialchars((string)$costos['moneda'], ENT_QUOTES, 'UTF-8'),
                    (float)$costos['productos'],
                    htmlspecialchars((string)$costos['moneda'], ENT_QUOTES, 'UTF-8'),
                    (float)$costos['total']
                );

                                $htmlBase = str_replace('{CONTENT}', $content, $tpl);

                // ¿Pago por cooperativa? Si sí, agregamos bloque extra + botones para cooperativa/drones
                $esPagoCoop = (bool)($data['pago_por_coop'] ?? false);
                $ctaUrl     = (string)($data['cta_url'] ?? 'https://compraconjunta.sve.com.ar/index.php');
                $coopTexto  = nl2br(htmlspecialchars((string)($data['coop_texto_extra'] ?? ''), ENT_QUOTES, 'UTF-8'));

                $botones = sprintf(
                    '<div style="margin-top:16px;display:flex;gap:12px;">
                        <a href="%1$s" style="background:#10b981;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;display:inline-block;font-weight:600;">Aprobar Solicitud</a>
                        <a href="%1$s" style="background:#ef4444;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;display:inline-block;font-weight:600;">Declinar Solicitud</a>
                    </div>',
                    htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8')
                );

                $coopBlock = ($esPagoCoop && $coopTexto !== '')
                    ? ('<h3 style="margin:16px 0 6px 0;">Acción requerida (Cooperativa)</h3>'
                       . '<div style="color:#111;line-height:1.5;">' . $coopTexto . '</div>' . $botones)
                    : '';

                $htmlCoop = str_replace('{CONTENT}', $content . $coopBlock, $tpl);

                // 1) Enviar SIEMPRE versión "cooperativa" a drones, y a la cooperativa cuando aplique.
                $mailCoop = self::baseMailer();
                $mailCoop->Subject = '🟣 SVE: Nueva solicitud de pulverización con dron';
                $mailCoop->Body    = $esPagoCoop ? $htmlCoop : $htmlBase;
                $mailCoop->AltBody = 'Nueva solicitud de dron - ID #' . (int)($data['solicitud_id'] ?? 0);

                // Drones (siempre)
                $mailCoop->addAddress('dronesvecoop@gmail.com', 'Drones SVE');

                // Cooperativa (solo si hay correo y/o aunque no sea pago por coop, mantenemos comportamiento previo)
                if (!empty($data['cooperativa']['correo'])) {
                    // Si es pago por coop, esta cooperativa es la seleccionada por el productor (controller ya la resolvió)
                    $mailCoop->addAddress((string)$data['cooperativa']['correo'], (string)($data['cooperativa']['nombre'] ?? ''));
                }

                // 2) Enviar versión "productor" al productor (si hay correo).
                $mailOk = true;

                if (!empty($data['productor']['correo'])) {
                    $mailProd = self::baseMailer();
                    $mailProd->Subject = '🟣 SVE: Solicitaste un nuevo servicio de pulverización con drones';
                    $mailProd->Body    = $htmlBase;
                    $mailProd->AltBody = 'Nueva solicitud de dron - ID #' . (int)($data['solicitud_id'] ?? 0);
                    $mailProd->addAddress((string)$data['productor']['correo'], (string)($data['productor']['nombre'] ?? ''));
                    $mailOk = $mailOk && $mailProd->send();
                }

                $mailOk = $mailOk && $mailCoop->send();

                return ['ok' => (bool)$mailOk];

            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => $e->getMessage()];
            }
        }

        /**
         * Envía correo “Solicitud de dron ACTUALIZADA” con el resumen de cambios.
         * $data = [
         *   'solicitud_id' => int,
         *   'estado_anterior' => ?string,
         *   'estado_actual' => ?string,
         *   'productor' => ['nombre'=>?string,'correo'=>?string],
         *   'cooperativas' => [ ['usuario'=>?string,'correo'=>?string], ... ],
         *   'cambios' => [ ['campo'=>string,'antes'=>string,'despues'=>string], ... ],
         *   'costos' => ['moneda'=>string,'base_total'=>float,'productos_total'=>float,'total'=>float]
         * ]
         * @return array{ok:bool,error?:string}
         */
        public static function enviarSolicitudDronActualizada(array $data): array
        {
            try {
                $tplPath = __DIR__ . '/template/dron_actualizada.html';
                $tpl = is_file($tplPath) ? file_get_contents($tplPath) : '<html><body style="font-family:Arial,sans-serif">{CONTENT}</body></html>';

                $rows = '';
                foreach ((array)($data['cambios'] ?? []) as $c) {
                    $rows .= sprintf(
                        '<tr>
                            <td style="padding:8px;border-bottom:1px solid #eee;">%s</td>
                            <td style="padding:8px;border-bottom:1px solid #eee;color:#6b7280;">%s</td>
                            <td style="padding:8px;border-bottom:1px solid #eee;"><strong>%s</strong></td>
                        </tr>',
                        htmlspecialchars((string)$c['campo'], ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)$c['antes'], ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)$c['despues'], ENT_QUOTES, 'UTF-8')
                    );
                }
                if ($rows === '') {
                    $rows = '<tr><td colspan="3" style="padding:8px;text-align:center;color:#6b7280;">Sin cambios detectados</td></tr>';
                }

                $costos = $data['costos'] ?? ['moneda' => 'Pesos', 'base_total' => 0, 'productos_total' => 0, 'total' => 0];

                $content = sprintf(
                    '<h2 style="margin:0 0 6px 0;">Solicitud de dron actualizada</h2>
                    <p style="margin:0 0 12px 0;color:#374151;">ID solicitud: <strong>#%d</strong></p>
                    <p style="margin:0 0 12px 0;color:#374151;">
                    Estado: <span style="background:#eef;padding:2px 8px;border-radius:999px;">%s</span>
                    &nbsp;→&nbsp;
                    <span style="background:#dcfce7;padding:2px 8px;border-radius:999px;">%s</span>
                    </p>

                    <h3 style="margin:12px 0 6px 0;">Resumen de cambios</h3>
                    <table cellpadding="0" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f3f4f6;">
                        <th style="text-align:left;padding:8px;">Campo</th>
                        <th style="text-align:left;padding:8px;">Antes</th>
                        <th style="text-align:left;padding:8px;">Después</th>
                        </tr>
                    </thead>
                    <tbody>%s</tbody>
                    </table>

                    <h3 style="margin:16px 0 6px 0;">Costos (snapshot)</h3>
                    <table cellpadding="8" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;">
                    <tbody>
                        <tr><td style="width:35%%;background:#f9fafb;">Base total</td><td>%s %0.2f</td></tr>
                        <tr><td style="background:#f9fafb;">Productos total</td><td>%s %0.2f</td></tr>
                        <tr><td style="background:#f9fafb;"><strong>Total</strong></td><td><strong>%s %0.2f</strong></td></tr>
                    </tbody>
                    </table>',
                    (int)($data['solicitud_id'] ?? 0),
                    htmlspecialchars((string)($data['estado_anterior'] ?? '—'), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($data['estado_actual'] ?? '—'), ENT_QUOTES, 'UTF-8'),
                    $rows,
                    htmlspecialchars((string)($costos['moneda'] ?? 'Pesos'), ENT_QUOTES, 'UTF-8'),
                    (float)($costos['base_total'] ?? 0),
                    htmlspecialchars((string)($costos['moneda'] ?? 'Pesos'), ENT_QUOTES, 'UTF-8'),
                    (float)($costos['productos_total'] ?? 0),
                    htmlspecialchars((string)($costos['moneda'] ?? 'Pesos'), ENT_QUOTES, 'UTF-8'),
                    (float)($costos['total'] ?? 0)
                );

                $html = str_replace('{CONTENT}', $content, $tpl);

                $mail = self::baseMailer();
                $mail->Subject = '🟣 SVE: Solicitud de dron actualizada';
                $mail->Body    = $html;
                $mail->AltBody = 'Solicitud actualizada - ID #' . (int)($data['solicitud_id'] ?? 0);

                // Destinatarios: siempre casilla de drones
                $mail->addAddress('dronesvecoop@gmail.com', 'Drones SVE');

                // Productor (si hay)
                $pCorreo = (string)($data['productor']['correo'] ?? '');
                $pNombre = (string)($data['productor']['nombre'] ?? '');
                if ($pCorreo !== '') {
                    $mail->addAddress($pCorreo, $pNombre);
                }

                // Cooperativas (si hay correo). Evitar duplicados.
                $added = [];
                foreach ((array)($data['cooperativas'] ?? []) as $c) {
                    $cc = trim((string)($c['correo'] ?? ''));
                    $nn = (string)($c['usuario'] ?? '');
                    if ($cc !== '' && !isset($added[$cc])) {
                        $mail->addAddress($cc, $nn);
                        $added[$cc] = true;
                    }
                }

                $mail->send();
                return ['ok' => true];
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => $e->getMessage()];
            }
        }
    }
