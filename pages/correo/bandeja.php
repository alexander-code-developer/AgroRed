<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/imap.php';
requerir_rol('admin');

// Enviar correo de prueba
if (es_post()) {
    csrf_validar();
    $para = mb_strtolower(post('para'));
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) flash('danger', 'Escribe un correo válido.');
    elseif (enviar_correo($para, 'Prueba de correo - AgroRed Hidalgo',
            "Si lees esto, el envío SMTP funciona correctamente.",
            correo_plantilla('Prueba de correo', '<p>Si lees esto, el envío SMTP funciona correctamente ✅</p>')))
        flash('success', "Correo de prueba enviado a $para.");
    else flash('danger', 'No se pudo enviar: ' . ultimo_error_correo());
    redirigir('pages/correo/bandeja.php');
}

$mensajes = []; $errorBandeja = null;
try { $mensajes = leer_bandeja(15); } catch (Throwable $e) { $errorBandeja = $e->getMessage(); }

$titulo = 'Correo';
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-encabezado-pag"><div class="ar-icono naranja"><i class="bi bi-envelope-paper-fill"></i></div>
    <div><h1>Correo</h1><p>Prueba el envío SMTP y consulta la bandeja de entrada.</p></div></div>
  <div class="row g-4">
    <div class="col-lg-4"><div class="ar-panel">
      <h2 class="h5">Enviar correo de prueba</h2>
      <form method="post"><?= csrf_campo() ?>
        <div class="mb-3"><label class="form-label" for="para">Destinatario</label>
          <input class="form-control" type="email" id="para" name="para" required></div>
        <button class="btn btn-ar"><i class="bi bi-send"></i> Enviar prueba</button>
      </form></div></div>
    <div class="col-lg-8"><div class="ar-panel">
      <h2 class="h5">Bandeja de entrada</h2>
      <?php if ($errorBandeja): ?><div class="alert alert-warning small mb-0"><?= e($errorBandeja) ?></div>
      <?php else: ?><div class="d-grid gap-2">
        <?php foreach ($mensajes as $m): ?>
          <div class="resena">
            <div class="d-flex justify-content-between small"><strong><?= e($m['de']) ?></strong><span class="text-muted"><?= e($m['fecha']) ?></span></div>
            <div class="fw-semibold"><?= e($m['asunto']) ?></div>
            <p class="mb-0 small text-muted"><?= e($m['vista']) ?></p>
          </div>
        <?php endforeach; if (!$mensajes): ?><p class="text-muted mb-0">No hay mensajes.</p><?php endif; ?>
      </div><?php endif; ?>
    </div></div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
