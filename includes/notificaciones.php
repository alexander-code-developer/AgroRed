<?php
/**
 * Avisos automáticos por correo de AgroRed Hidalgo.
 * Regla de oro: un fallo de correo NUNCA debe romper la operación del usuario
 * (guardar pedido, crear cuenta...). Todas las funciones devuelven bool y registran el error en el log.
 */
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../config/database.php';

function url_absoluta(string $ruta = ''): string
{
    $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $esquema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($ruta);
}

function notificaciones_activas(): bool
{
    $c = cfg('correo');
    return !empty($c['activo']) && ($c['notificar'] ?? true);
}

/** Envío "seguro": plantilla + try/catch + sin romper el flujo. */
function notificar(string $para, string $asunto, string $titulo, string $htmlCuerpo, string $texto,
                   ?string $responderA = null, ?string $responderNombre = null): bool
{
    if (!notificaciones_activas() || !filter_var($para, FILTER_VALIDATE_EMAIL)) return false;
    try {
        $ok = enviar_correo($para, $asunto, $texto, correo_plantilla($titulo, $htmlCuerpo), $responderA, $responderNombre);
        if (!$ok) error_log('[AgroRed notificación] ' . $asunto . ' → ' . ultimo_error_correo());
        return $ok;
    } catch (Throwable $e) {
        error_log('[AgroRed notificación] ' . $e->getMessage());
        return false;
    }
}

function _boton(string $texto, string $enlace): string
{
    return '<p><a href="' . e($enlace) . '" style="background:#2e7d32;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none">'
         . e($texto) . '</a></p>';
}

/** Bienvenida al crear cuenta. $u: ['nombre','correo'] */
function notif_bienvenida(array $u): bool
{
    return notificar($u['correo'], '¡Bienvenido a AgroRed Hidalgo!', 'Tu cuenta está lista',
        '<p>Hola ' . e($u['nombre']) . ',</p><p>Gracias por unirte a AgroRed Hidalgo. Ya puedes explorar huertos cercanos y coordinar compras directas con productores locales.</p>'
        . _boton('Ir a AgroRed', url_absoluta('index.php')),
        "Hola {$u['nombre']},\n\nGracias por unirte a AgroRed Hidalgo.\n" . url_absoluta('index.php'));
}

/** Pedido nuevo: avisa al productor y, si hay correo del comprador, le confirma. */
function notif_pedido_nuevo(int $pedidoId): bool
{
    if (!notificaciones_activas()) return false;
    try {
        $st = db()->prepare('SELECT p.id, p.telefono, p.correo AS correo_pedido, h.nombre AS huerto,
                                    d.correo AS correo_dueno, d.nombre AS nombre_dueno,
                                    c.correo AS correo_comprador, c.nombre AS nombre_comprador
                             FROM pedidos p
                             JOIN huertos h ON h.id = p.huerto_id
                             LEFT JOIN usuarios d ON d.id = h.dueno_id
                             LEFT JOIN usuarios c ON c.id = p.usuario_id
                             WHERE p.id = ?');
        $st->execute([$pedidoId]);
        $p = $st->fetch();
        if (!$p) return false;

        $correoComprador = $p['correo_comprador'] ?: $p['correo_pedido'];
        $num = '#' . str_pad((string)$p['id'], 3, '0', STR_PAD_LEFT);
        $ok = false;

        if ($p['correo_dueno']) {
            $ok = notificar($p['correo_dueno'], "Nueva solicitud de compra $num - {$p['huerto']}", 'Tienes una solicitud de compra',
                '<p>Hola ' . e($p['nombre_dueno']) . ',</p><p>Alguien quiere comprar en <strong>' . e($p['huerto']) . '</strong>.</p>'
                . '<ul><li>Teléfono: <strong>' . e($p['telefono']) . '</strong></li>'
                . ($correoComprador ? '<li>Correo: ' . e($correoComprador) . '</li>' : '') . '</ul>'
                . '<p>Contáctalo y actualiza el estado del pedido en la plataforma.</p>'
                . _boton('Ver mis pedidos', url_absoluta('pages/productor/pedidos.php')),
                "Nueva solicitud $num en {$p['huerto']}.\nTeléfono: {$p['telefono']}" . ($correoComprador ? "\nCorreo: $correoComprador" : '') . "\n" . url_absoluta('pages/productor/pedidos.php'),
                $correoComprador ?: null, $p['nombre_comprador'] ?: null);
        }
        $copia = cfg('correo')['copia_admin'] ?? '';
        if ($copia) notificar($copia, "Pedido $num en {$p['huerto']}", 'Pedido nuevo', '<p>Pedido ' . e($num) . ' en ' . e($p['huerto']) . '. Tel: ' . e($p['telefono']) . '</p>', "Pedido $num en {$p['huerto']}. Tel: {$p['telefono']}");

        if ($correoComprador) {
            notificar($correoComprador, "Recibimos tu solicitud $num - {$p['huerto']}", 'Solicitud enviada',
                '<p>Tu solicitud de compra en <strong>' . e($p['huerto']) . '</strong> fue enviada.</p><p>El productor te contactará al teléfono <strong>' . e($p['telefono']) . '</strong> para acordar la entrega.</p>',
                "Tu solicitud $num en {$p['huerto']} fue enviada. El productor te contactará al {$p['telefono']}.");
        }
        return $ok;
    } catch (Throwable $e) { error_log('[AgroRed notificación] ' . $e->getMessage()); return false; }
}

/** Cambio de estado: avisa al comprador (si dejó o tiene correo). */
function notif_pedido_estado(int $pedidoId, string $estado): bool
{
    if (!notificaciones_activas()) return false;
    try {
        $st = db()->prepare('SELECT p.id, p.correo AS correo_pedido, h.nombre AS huerto, c.correo AS correo_comprador
                             FROM pedidos p JOIN huertos h ON h.id = p.huerto_id LEFT JOIN usuarios c ON c.id = p.usuario_id WHERE p.id = ?');
        $st->execute([$pedidoId]);
        $p = $st->fetch();
        $para = $p['correo_comprador'] ?? $p['correo_pedido'] ?? null;
        if (!$p || !$para) return false;
        $msg = [
            'contactado' => 'El productor ya revisó tu solicitud y se pondrá en contacto contigo.',
            'completado' => '¡Tu pedido se completó! Gracias por comprar directo a productores locales. Puedes dejar una reseña del huerto.',
            'cancelado'  => 'Tu solicitud fue cancelada. Si fue un error, puedes generar una nueva desde la página del huerto.',
        ][$estado] ?? ('Tu pedido cambió a: ' . $estado);
        $num = '#' . str_pad((string)$p['id'], 3, '0', STR_PAD_LEFT);
        return notificar($para, "Tu pedido $num está $estado - {$p['huerto']}", 'Actualización de tu pedido',
            '<p>' . e($msg) . '</p><p style="color:#777;font-size:13px">Huerto: ' . e($p['huerto']) . '</p>', $msg);
    } catch (Throwable $e) { error_log('[AgroRed notificación] ' . $e->getMessage()); return false; }
}

/** Mensaje entre productores: llega por correo al dueño del huerto destino; "Responder" va al remitente. */
function notif_mensaje_productor(int $huertoId, array $remitente, string $texto): bool
{
    if (!notificaciones_activas()) return false;
    try {
        $st = db()->prepare('SELECT h.nombre AS huerto, d.correo, d.nombre FROM huertos h JOIN usuarios d ON d.id = h.dueno_id WHERE h.id = ?');
        $st->execute([$huertoId]);
        $d = $st->fetch();
        if (!$d) return false;
        return notificar($d['correo'], 'Nuevo mensaje de ' . $remitente['nombre'] . ' en AgroRed', 'Tienes un mensaje nuevo',
            '<p>Hola ' . e($d['nombre']) . ', <strong>' . e($remitente['nombre']) . '</strong> escribió a <strong>' . e($d['huerto']) . '</strong>:</p>'
            . '<blockquote style="border-left:4px solid #2e7d32;margin:0;padding:6px 12px;background:#f6faf6">' . nl2br(e($texto)) . '</blockquote>'
            . '<p style="font-size:13px;color:#777">Puedes responder directamente a este correo.</p>'
            . _boton('Abrir mensajes', url_absoluta('pages/productor/mensajes.php')),
            "{$remitente['nombre']} escribió a {$d['huerto']}:\n\n$texto\n\n" . url_absoluta('pages/productor/mensajes.php'),
            $remitente['correo'] ?? null, $remitente['nombre']);
    } catch (Throwable $e) { error_log('[AgroRed notificación] ' . $e->getMessage()); return false; }
}
