<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mailer.php';
$enlaceDev = null;

if (es_post()) {
    csrf_validar();
    $correo = mb_strtolower(post('correo'));
    // Siempre se muestra el mismo mensaje: no se revela si el correo existe.
    if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $pdo = db();
        $st = $pdo->prepare('SELECT id, nombre FROM usuarios WHERE correo = ?'); $st->execute([$correo]);
        if ($u = $st->fetch()) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('UPDATE password_resets SET usado=1 WHERE usuario_id=? AND usado=0')->execute([$u['id']]);
            $pdo->prepare('INSERT INTO password_resets (usuario_id, token_hash, expira) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
                ->execute([$u['id'], hash('sha256', $token)]);
            $enlace = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . url('pages/auth/restablecer.php?token=' . $token);
            $asunto = 'Recupera tu contraseña - AgroRed Hidalgo';
            $cuerpo = "Hola {$u['nombre']},\n\nPara crear una nueva contraseña abre este enlace (vence en 30 minutos):\n$enlace\n\nSi no lo solicitaste, ignora este mensaje.";
            $html = correo_plantilla('Recupera tu contraseña',
                '<p>Hola ' . e($u['nombre']) . ',</p><p>Para crear una nueva contraseña pulsa el botón (vence en 30 minutos):</p>'
              . '<p><a href="' . e($enlace) . '" style="background:#2e7d32;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none">Crear nueva contraseña</a></p>'
              . '<p style="font-size:12px;color:#777">Si no lo solicitaste, ignora este mensaje.</p>');
            enviar_correo($correo, $asunto, $cuerpo, $html);
            if (cfg('modo_dev')) $enlaceDev = $enlace;
        }
    }
    flash('success', 'Si el correo está registrado, recibirás instrucciones para recuperar tu contraseña.');
}
$titulo = 'Recuperar contraseña';
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-panel ar-auth">
    <div class="text-center">
      <div class="ar-badge-ico"><i class="bi bi-key-fill"></i></div>
      <h1 class="h3">Recuperar contraseña</h1>
      <p class="ar-sub">Ingresa el correo asociado a tu cuenta y te enviaremos las instrucciones.</p>
    </div>
    <?php if ($enlaceDev): ?>
      <div class="alert alert-info small"><strong>Modo desarrollo:</strong> como no hay servidor de correo configurado, usa este enlace:<br>
        <a href="<?= e($enlaceDev) ?>"><?= e($enlaceDev) ?></a></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_campo() ?>
      <div class="mb-3">
        <label class="form-label" for="correo">Correo electrónico</label>
        <input class="form-control" type="email" id="correo" name="correo" required autocomplete="email">
      </div>
      <button class="btn btn-ar w-100" type="submit"><i class="bi bi-send"></i> Enviar instrucciones</button>
    </form>
    <p class="text-center mt-3 mb-0 small"><a href="<?= url('pages/auth/login.php') ?>"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a></p>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
