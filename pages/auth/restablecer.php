<?php
require_once __DIR__ . '/../../includes/auth.php';
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$valido = false; $reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT id, usuario_id FROM password_resets WHERE token_hash=? AND usado=0 AND expira > NOW()');
    $st->execute([hash('sha256', $token)]);
    $reset = $st->fetch(); $valido = (bool)$reset;
}
if ($valido && es_post()) {
    csrf_validar();
    $p1 = $_POST['password'] ?? ''; $p2 = $_POST['password2'] ?? '';
    if (strlen($p1) < 8)      flash('danger', 'La contraseña debe tener al menos 8 caracteres.');
    elseif ($p1 !== $p2)      flash('danger', 'Las contraseñas no coinciden.');
    else {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE usuarios SET pass_hash=? WHERE id=?')->execute([password_hash($p1, PASSWORD_BCRYPT), $reset['usuario_id']]);
        $pdo->prepare('UPDATE password_resets SET usado=1 WHERE usuario_id=?')->execute([$reset['usuario_id']]);
        $pdo->commit();
        flash('success', 'Contraseña actualizada. Ya puedes iniciar sesión.');
        redirigir('pages/auth/login.php');
    }
}
$titulo = 'Nueva contraseña';
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-panel ar-auth">
    <div class="text-center">
      <div class="ar-badge-ico"><i class="bi bi-shield-lock-fill"></i></div>
      <h1 class="h3">Nueva contraseña</h1>
    </div>
    <?php if (!$valido): ?>
      <div class="alert alert-danger">El enlace no es válido o ya venció. <a href="<?= url('pages/auth/recuperar.php') ?>">Solicita uno nuevo</a>.</div>
    <?php else: ?>
      <form method="post" novalidate>
        <?= csrf_campo() ?><input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="mb-3"><label class="form-label" for="password">Nueva contraseña</label>
          <input class="form-control" type="password" id="password" name="password" minlength="8" required autocomplete="new-password"></div>
        <div class="mb-3"><label class="form-label" for="password2">Confirmar contraseña</label>
          <input class="form-control" type="password" id="password2" name="password2" minlength="8" required autocomplete="new-password"></div>
        <button class="btn btn-ar w-100" type="submit"><i class="bi bi-check2-circle"></i> Guardar contraseña</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
