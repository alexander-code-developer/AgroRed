<?php
require_once __DIR__ . '/../../includes/auth.php';
if (logueado()) redirigir('pages/perfil.php');

if (es_post()) {
    csrf_validar();
    if (demasiados_intentos()) {
        flash('danger', 'Demasiados intentos fallidos. Espera 10 minutos e inténtalo de nuevo.');
    } else {
        $st = db()->prepare('SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.correo = ?');
        $st->execute([mb_strtolower(post('correo'))]);
        $u = $st->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['pass_hash'])) {
            registrar_intento(true);
            iniciar_sesion($u);
            flash('success', '¡Bienvenido, ' . explode(' ', $u['nombre'])[0] . '!');
            redirigir('pages/perfil.php');
        }
        registrar_intento(false);
        flash('danger', 'Correo o contraseña incorrectos.');
    }
}
$titulo = 'Iniciar sesión';
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-panel ar-auth">
    <div class="text-center">
      <div class="ar-badge-ico"><i class="bi bi-lock-fill"></i></div>
      <h1 class="h3">Iniciar sesión</h1>
      <p class="ar-sub">Accede a tu cuenta de AgroRed Hidalgo</p>
    </div>
    <form method="post" novalidate>
      <?= csrf_campo() ?>
      <div class="mb-3">
        <label class="form-label" for="correo">Correo electrónico</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input class="form-control" type="email" id="correo" name="correo" value="<?= e(post('correo')) ?>" required autocomplete="email">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Contraseña</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-key"></i></span>
          <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
          <button class="btn btn-outline-secondary" type="button" data-ver="#password" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-end mb-3 small">
        <a href="<?= url('pages/auth/recuperar.php') ?>">¿Olvidaste tu contraseña?</a>
      </div>
      <button class="btn btn-ar w-100" type="submit"><i class="bi bi-box-arrow-in-right"></i> Iniciar sesión</button>
    </form>
    <p class="text-center mt-3 mb-0 small">¿No tienes una cuenta? <a href="<?= url('pages/auth/crear-cuenta.php') ?>">Crear cuenta</a></p>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
