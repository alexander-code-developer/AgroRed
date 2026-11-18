<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notificaciones.php';
requerir_rol('productor');
$pdo = db(); $u = usuario();
if (es_post()) {
    csrf_validar();
    $dest = (int)post('huerto'); $txt = post('texto');
    $ok = $pdo->prepare('SELECT 1 FROM huertos WHERE id=?'); $ok->execute([$dest]);
    if (!$ok->fetch() || $txt === '' || mb_strlen($txt) > 300) flash('danger', 'Selecciona un destinatario y escribe tu mensaje (máx. 300 caracteres).');
    else { $pdo->prepare('INSERT INTO mensajes (de_usuario_id,para_huerto_id,texto) VALUES (?,?,?)')->execute([$u['id'], $dest, $txt]); notif_mensaje_productor($dest, $u, $txt); flash('success', 'Mensaje enviado correctamente.'); }
    redirigir('pages/productor/mensajes.php');
}
$dest = $pdo->prepare('SELECT id,nombre FROM huertos WHERE dueno_id<>? OR dueno_id IS NULL ORDER BY nombre'); $dest->execute([$u['id']]); $dest = $dest->fetchAll();
$bd = $pdo->prepare('SELECT m.texto,m.creado,u.nombre de,h.nombre huerto,(m.de_usuario_id=?) enviado
                     FROM mensajes m JOIN usuarios u ON u.id=m.de_usuario_id JOIN huertos h ON h.id=m.para_huerto_id
                     WHERE m.de_usuario_id=? OR h.dueno_id=? ORDER BY m.creado DESC LIMIT 50');
$bd->execute([$u['id'], $u['id'], $u['id']]); $msgs = $bd->fetchAll();
$titulo = 'Mensajería entre productores';
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-encabezado-pag"><div class="ar-icono naranja"><i class="bi bi-envelope-heart-fill"></i></div>
    <div><h1>Mensajería entre productores</h1><p>Propón negocios o intercambios a otros huertos y consulta tu bandeja de mensajes.</p></div></div>
  <div class="row g-4">
    <div class="col-lg-5"><div class="ar-panel">
      <h2 class="h5">Nuevo mensaje</h2>
      <form method="post"><?= csrf_campo() ?>
        <div class="mb-3"><label class="form-label" for="huerto">Huerto destinatario</label>
          <select class="form-select" id="huerto" name="huerto" required><option value="">Selecciona un huerto</option>
            <?php foreach ($dest as $d) echo '<option value="' . $d['id'] . '">' . e($d['nombre']) . '</option>'; ?></select></div>
        <div class="mb-1"><label class="form-label" for="texto">Mensaje</label>
          <textarea class="form-control" id="texto" name="texto" rows="5" maxlength="300" data-contador="#cnt" placeholder="Propuesta de negocio o intercambio" required></textarea></div>
        <div class="text-end small text-muted mb-3" id="cnt"></div>
        <button class="btn btn-ar"><i class="bi bi-send"></i> Enviar</button>
      </form></div></div>
    <div class="col-lg-7"><div class="ar-panel">
      <h2 class="h5">Bandeja de mensajes</h2>
      <div class="d-grid gap-2">
        <?php foreach ($msgs as $m): ?>
          <div class="resena" style="<?= $m['enviado'] ? 'border-left-color:var(--ar-naranja)' : '' ?>">
            <div class="d-flex justify-content-between small"><strong><?= $m['enviado'] ? 'Yo → ' . e($m['huerto']) : e($m['huerto']) . ' → Mi huerto' ?></strong><span class="text-muted"><?= fecha_corta($m['creado']) ?></span></div>
            <p class="mb-0"><?= e($m['texto']) ?></p>
          </div>
        <?php endforeach; if (!$msgs): ?><p class="text-muted">Aún no tienes mensajes.</p><?php endif; ?>
      </div></div></div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
