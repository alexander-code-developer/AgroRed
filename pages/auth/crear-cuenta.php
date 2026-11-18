<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notificaciones.php';
if (logueado()) redirigir('pages/perfil.php');

$errores = [];
if (es_post()) {
    csrf_validar();
    $nombre = post('nombre'); $correo = mb_strtolower(post('correo')); $tel = post('telefono');
    $tipo = post('tipo'); $pass = $_POST['password'] ?? ''; $pass2 = $_POST['password2'] ?? '';

    if (mb_strlen($nombre) < 3)                          $errores[] = 'Escribe tu nombre completo.';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL))     $errores[] = 'Correo electrónico inválido.';
    if (!es_telefono($tel))                              $errores[] = 'El teléfono debe tener 10 dígitos.';
    if (!in_array($tipo, ['productor', 'recolector', 'usuario'], true)) $errores[] = 'Selecciona un tipo de usuario.';
    if (strlen($pass) < 8)                               $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($pass !== $pass2)                                $errores[] = 'Las contraseñas no coinciden.';
    if (empty($_POST['terminos']))                       $errores[] = 'Debes aceptar los términos y condiciones.';

    if (!$errores) {
        $pdo = db();
        $ya = $pdo->prepare('SELECT 1 FROM usuarios WHERE correo = ?'); $ya->execute([$correo]);
        if ($ya->fetch()) {
            $errores[] = 'Ese correo ya está registrado.';
        } else {
            $rol = $pdo->prepare('SELECT id FROM roles WHERE nombre = ?'); $rol->execute([$tipo]);
            $pdo->prepare('INSERT INTO usuarios (rol_id,nombre,correo,telefono,pass_hash) VALUES (?,?,?,?,?)')
                ->execute([$rol->fetchColumn(), $nombre, $correo, $tel, password_hash($pass, PASSWORD_BCRYPT)]);
            $id = (int)$pdo->lastInsertId();
            iniciar_sesion(['id' => $id, 'nombre' => $nombre, 'correo' => $correo, 'telefono' => $tel, 'rol' => $tipo]);
            notif_bienvenida(['nombre' => $nombre, 'correo' => $correo]);
            flash('success', 'Cuenta creada. ¡Bienvenido a AgroRed Hidalgo!');
            redirigir('pages/perfil.php');
        }
    }
}
$titulo = 'Crear cuenta';
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-panel ar-auth" style="max-width:520px">
    <div class="text-center">
      <div class="ar-badge-ico"><i class="bi bi-flower2"></i></div>
      <h1 class="h3">Crear cuenta</h1>
      <p class="ar-sub">Regístrate en AgroRed Hidalgo para administrar tus huertos</p>
    </div>
    <?php if ($errores): ?>
      <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errores as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_campo() ?>
      <div class="mb-3">
        <label class="form-label" for="nombre">Nombre completo</label>
        <input class="form-control" id="nombre" name="nombre" value="<?= e(post('nombre')) ?>" required autocomplete="name">
      </div>
      <div class="mb-3">
        <label class="form-label" for="correo">Correo electrónico</label>
        <input class="form-control" type="email" id="correo" name="correo" value="<?= e(post('correo')) ?>" required autocomplete="email">
      </div>
      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label" for="telefono">Teléfono (10 dígitos)</label>
          <input class="form-control" id="telefono" name="telefono" inputmode="numeric" maxlength="10" pattern="\d{10}" value="<?= e(post('telefono')) ?>" required autocomplete="tel">
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="tipo">Tipo de usuario</label>
          <select class="form-select" id="tipo" name="tipo" required>
            <?php foreach (['usuario' => 'Cliente / comprador', 'productor' => 'Productor (dueño de huerto)', 'recolector' => 'Recolector'] as $v => $t): ?>
              <option value="<?= $v ?>" <?= post('tipo') === $v ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label" for="password">Contraseña</label>
          <input class="form-control" type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="password2">Confirmar contraseña</label>
          <input class="form-control" type="password" id="password2" name="password2" minlength="8" required autocomplete="new-password">
        </div>
      </div>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="terminos" name="terminos" value="1" required>
        <label class="form-check-label small" for="terminos">Acepto los términos y condiciones de AgroRed Hidalgo</label>
      </div>
      <button class="btn btn-ar w-100" type="submit"><i class="bi bi-person-plus"></i> Crear cuenta</button>
    </form>
    <p class="text-center mt-3 mb-0 small">¿Ya tienes una cuenta? <a href="<?= url('pages/auth/login.php') ?>">Inicia sesión</a></p>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
