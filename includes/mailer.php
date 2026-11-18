<?php
/**
 * Envío de correo por SMTP (sin librerías externas).
 * Soporta SSL directo (puerto 465) y STARTTLS (puerto 587) con AUTH LOGIN.
 * Configuración en config/config.php → 'correo'.
 *
 * Uso:
 *   require_once __DIR__ . '/mailer.php';
 *   if (!enviar_correo('alguien@correo.com', 'Asunto', 'Texto plano', '<p>HTML opcional</p>')) {
 *       echo ultimo_error_correo();
 *   }
 */
require_once __DIR__ . '/helpers.php';

function ultimo_error_correo(?string $nuevo = null): string
{
    static $err = '';
    if ($nuevo !== null) $err = $nuevo;
    return $err;
}

/** Lee una respuesta SMTP completa (puede venir en varias líneas "250-..."). */
function _smtp_leer($fp): array
{
    $resp = '';
    while (($linea = fgets($fp, 1024)) !== false) {
        $resp .= $linea;
        if (strlen($linea) < 4 || $linea[3] === ' ') break;
    }
    return [(int)substr($resp, 0, 3), trim($resp)];
}

function _smtp_cmd($fp, string $cmd, array $esperados): string
{
    if ($cmd !== '') fwrite($fp, $cmd . "\r\n");
    [$codigo, $resp] = _smtp_leer($fp);
    if (!in_array($codigo, $esperados, true)) {
        throw new RuntimeException('SMTP respondió: ' . $resp);
    }
    return $resp;
}

/** Quita saltos de línea para evitar inyección de cabeceras. */
function _mail_limpio(string $s): string { return trim(str_replace(["\r", "\n", "\0"], ' ', $s)); }
function _mail_b64(string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; }

function enviar_correo(string $para, string $asunto, string $texto, ?string $html = null, ?string $responderA = null, ?string $responderNombre = null): bool
{
    $c = cfg('correo');
    ultimo_error_correo('');
    if (empty($c['activo'])) { ultimo_error_correo('El envío de correo está desactivado (correo.activo = false).'); return false; }
    $para = _mail_limpio($para);
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) { ultimo_error_correo('Destinatario inválido.'); return false; }

    $desde  = _mail_limpio($c['desde_correo'] ?: $c['usuario']);
    $nombre = _mail_limpio($c['desde_nombre'] ?? cfg('app_nombre'));
    $host   = $c['host']; $puerto = (int)$c['port']; $seg = strtolower($c['seguridad'] ?? 'tls');

    // --- Mensaje MIME ---
    $dominio = substr(strrchr($desde, '@'), 1) ?: 'localhost';
    $h  = 'Date: ' . date('r') . "\r\n";
    $h .= 'From: ' . _mail_b64($nombre) . " <$desde>\r\n";
    $h .= "To: <$para>\r\n";
    $responderA = $responderA ? _mail_limpio($responderA) : '';
    if ($responderA !== '' && filter_var($responderA, FILTER_VALIDATE_EMAIL)) {
        $rn = _mail_limpio((string)$responderNombre);
        $h .= 'Reply-To: ' . ($rn !== '' ? _mail_b64($rn) . " <$responderA>" : "<$responderA>") . "\r\n";
    }
    $h .= 'Subject: ' . _mail_b64(_mail_limpio($asunto)) . "\r\n";
    $h .= 'Message-ID: <' . bin2hex(random_bytes(12)) . "@$dominio>\r\n";
    $h .= "MIME-Version: 1.0\r\n";
    $b64 = fn(string $s) => chunk_split(base64_encode($s), 76, "\r\n");
    if ($html === null) {
        $h .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64($texto);
    } else {
        $lim = 'ar_' . bin2hex(random_bytes(8));
        $h .= "Content-Type: multipart/alternative; boundary=\"$lim\"\r\n\r\n";
        $h .= "--$lim\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64($texto);
        $h .= "--$lim\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64($html);
        $h .= "--$lim--\r\n";
    }
    // Dot-stuffing (RFC 5321)
    $mensaje = preg_replace('/^\./m', '..', $h);

    // --- Conexión ---
    $ctx = stream_context_create(['ssl' => [
        'verify_peer'      => $c['verificar_ssl'] ?? true,
        'verify_peer_name' => $c['verificar_ssl'] ?? true,
        'peer_name'        => $host,
    ]]);
    $destino = ($seg === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $puerto;
    $fp = @stream_socket_client($destino, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { ultimo_error_correo("No se pudo conectar a $host:$puerto ($errstr)"); return false; }
    stream_set_timeout($fp, 20);

    try {
        _smtp_cmd($fp, '', [220]);
        $yo = $_SERVER['SERVER_NAME'] ?? 'localhost';
        _smtp_cmd($fp, "EHLO $yo", [250]);
        if ($seg === 'tls') {
            _smtp_cmd($fp, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('No se pudo establecer TLS (revisa correo.verificar_ssl o el certificado CA de PHP).');
            }
            _smtp_cmd($fp, "EHLO $yo", [250]);
        }
        if (!empty($c['usuario'])) {
            _smtp_cmd($fp, 'AUTH LOGIN', [334]);
            _smtp_cmd($fp, base64_encode($c['usuario']), [334]);
            _smtp_cmd($fp, base64_encode($c['clave']), [235]);
        }
        _smtp_cmd($fp, "MAIL FROM:<$desde>", [250]);
        _smtp_cmd($fp, "RCPT TO:<$para>", [250, 251]);
        _smtp_cmd($fp, 'DATA', [354]);
        fwrite($fp, $mensaje . "\r\n.\r\n");
        _smtp_cmd($fp, '', [250]);
        @_smtp_cmd($fp, 'QUIT', [221]);
        fclose($fp);
        return true;
    } catch (Throwable $e) {
        @fwrite($fp, "QUIT\r\n"); @fclose($fp);
        ultimo_error_correo($e->getMessage());
        error_log('[AgroRed correo] ' . $e->getMessage());
        return false;
    }
}

/** Plantilla HTML sencilla con la identidad de AgroRed. */
function correo_plantilla(string $titulo, string $contenidoHtml): string
{
    return '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;border:1px solid #e3e3e3;border-radius:8px;overflow:hidden">'
         . '<div style="background:#2e7d32;color:#fff;padding:16px 20px;font-size:18px">🌱 ' . e(cfg('app_nombre')) . '</div>'
         . '<div style="padding:20px;color:#333;line-height:1.5"><h2 style="margin-top:0">' . e($titulo) . '</h2>' . $contenidoHtml . '</div></div>';
}
