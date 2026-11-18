<?php
/**
 * Lectura de bandeja de entrada por IMAP (opcional).
 * Requiere la extensión php_imap (en XAMPP: php.ini → extension=imap, reiniciar Apache).
 * Configuración en config/config.php → 'correo_entrada'.
 */
require_once __DIR__ . '/helpers.php';

function imap_disponible(): bool { return function_exists('imap_open'); }

function _imap_texto_parte($i, int $n): string
{
    $est = imap_fetchstructure($i, $n);
    if (!$est) return '';
    $buscar = function ($parte, string $ruta) use (&$buscar, $i, $n) {
        if (!empty($parte->parts)) {
            foreach ($parte->parts as $k => $sub) {
                $r = $buscar($sub, $ruta === '' ? (string)($k + 1) : $ruta . '.' . ($k + 1));
                if ($r !== null) return $r;
            }
            return null;
        }
        if ($parte->type === 0 && strtolower($parte->subtype ?? '') === 'plain') {
            $txt = imap_fetchbody($i, $n, $ruta === '' ? '1' : $ruta, FT_PEEK);
            $txt = match ($parte->encoding) { 3 => base64_decode($txt), 4 => quoted_printable_decode($txt), default => $txt };
            foreach ($parte->parameters ?? [] as $p) {
                if (strtolower($p->attribute) === 'charset' && strtoupper($p->value) !== 'UTF-8') {
                    $txt = @mb_convert_encoding($txt, 'UTF-8', $p->value);
                }
            }
            return $txt;
        }
        return null;
    };
    return trim((string)$buscar($est, ''));
}

/** Devuelve los últimos $limite correos: [['uid','de','asunto','fecha','vista'], ...] */
function leer_bandeja(int $limite = 15): array
{
    $c = cfg('correo_entrada');
    if (!imap_disponible()) throw new RuntimeException('La extensión PHP "imap" no está habilitada.');
    if (empty($c['activo'])) throw new RuntimeException('La lectura de correo está desactivada (correo_entrada.activo = false).');

    $flags = '/imap' . (($c['seguridad'] ?? 'ssl') === 'ssl' ? '/ssl' : '/tls') . (($c['verificar_ssl'] ?? true) ? '' : '/novalidate-cert');
    $buzon = sprintf('{%s:%d%s}%s', $c['host'], $c['port'], $flags, $c['carpeta'] ?? 'INBOX');
    $i = @imap_open($buzon, $c['usuario'], $c['clave'], OP_READONLY, 1);
    if (!$i) throw new RuntimeException('No se pudo abrir el buzón: ' . imap_last_error());

    $total = imap_num_msg($i);
    $salida = [];
    for ($n = $total; $n > max(0, $total - $limite); $n--) {
        $cab = imap_headerinfo($i, $n);
        if (!$cab) continue;
        $de = isset($cab->from[0]) ? (imap_utf8($cab->from[0]->personal ?? '') ?: ($cab->from[0]->mailbox . '@' . $cab->from[0]->host)) : '—';
        $salida[] = [
            'uid'    => imap_uid($i, $n),
            'de'     => $de,
            'asunto' => imap_utf8($cab->subject ?? '(sin asunto)'),
            'fecha'  => date('d/m/Y H:i', strtotime($cab->date ?? 'now')),
            'vista'  => mb_substr(preg_replace('/\s+/', ' ', _imap_texto_parte($i, $n)), 0, 220),
        ];
    }
    imap_close($i);
    return $salida;
}
