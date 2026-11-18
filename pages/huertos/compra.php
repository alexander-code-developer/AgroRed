<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notificaciones.php';
$pdo = db(); $u = usuario();
$id = (int)($_GET['id'] ?? 0);

// ---------- Acciones POST: pedido directo y reseña ----------
if (es_post()) {
    csrf_validar();
    $hid = (int)post('huerto_id'); $accion = post('accion');
    $existe = $pdo->prepare('SELECT 1 FROM huertos WHERE id=?'); $existe->execute([$hid]);
    if (!$existe->fetch()) { flash('danger', 'Huerto no encontrado.'); redirigir('pages/huertos/compra.php'); }

    if ($accion === 'pedido') {
        $tel = post('telefono'); $mail = mb_strtolower(post('correo'));
        if (!es_telefono($tel)) flash('danger', 'Escribe un teléfono de 10 dígitos.');
        elseif ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) flash('danger', 'El correo no es válido (puedes dejarlo vacío).');
        else {
            $pdo->prepare('INSERT INTO pedidos (huerto_id,usuario_id,estado_id,telefono,correo) VALUES (?,?,1,?,?)')
                ->execute([$hid, $u['id'] ?? null, $tel, $mail ?: null]);
            notif_pedido_nuevo((int)$pdo->lastInsertId());
            flash('success', '¡Listo! El productor te contactará a tu teléfono para coordinar la compra.');
        }
    } elseif ($accion === 'resena') {
        requerir_login();
        $est = (int)post('estrellas'); $com = post('comentario'); $rec = isset($_POST['recomienda']) ? 1 : 0;
        if ($est < 1 || $est > 5 || $com === '' || mb_strlen($com) > 200) flash('danger', 'Selecciona una calificación y escribe un comentario (máx. 200 caracteres).');
        else {
            // Una reseña por usuario y huerto: si ya existe, se actualiza.
            $pdo->prepare('INSERT INTO resenas (huerto_id,usuario_id,estrellas,comentario,recomienda) VALUES (?,?,?,?,?)
                           ON DUPLICATE KEY UPDATE estrellas=VALUES(estrellas), comentario=VALUES(comentario), recomienda=VALUES(recomienda), creado=NOW()')
                ->execute([$hid, $u['id'], $est, $com, $rec]);
            flash('success', 'Gracias por tu reseña.');
        }
    }
    redirigir('pages/huertos/compra.php?id=' . $hid);
}

// ---------- Datos del huerto seleccionado ----------
$h = null; $inv = []; $res = []; $stats = ['prom' => 0, 'n' => 0, 'rec' => 0];
if ($id) {
    $st = $pdo->prepare('SELECT h.*, c.nombre AS colonia, m.nombre AS municipio FROM huertos h
                         JOIN colonias c ON c.id=h.colonia_id JOIN municipios m ON m.id=c.municipio_id WHERE h.id=?');
    $st->execute([$id]); $h = $st->fetch();
    if ($h) {
        $i = $pdo->prepare('SELECT cu.nombre, i.volumen_kg FROM inventario i JOIN cultivos cu ON cu.id=i.cultivo_id WHERE i.huerto_id=? ORDER BY cu.nombre');
        $i->execute([$id]); $inv = $i->fetchAll();
        $r = $pdo->prepare('SELECT r.*, us.nombre AS autor FROM resenas r JOIN usuarios us ON us.id=r.usuario_id WHERE r.huerto_id=? ORDER BY r.creado DESC LIMIT 20');
        $r->execute([$id]); $res = $r->fetchAll();
        $s = $pdo->prepare('SELECT COALESCE(ROUND(AVG(estrellas),1),0) prom, COUNT(*) n, COALESCE(SUM(recomienda),0) rec FROM resenas WHERE huerto_id=?');
        $s->execute([$id]); $stats = $s->fetch();
    }
}
$mia = null;
if ($u && $h) { foreach ($res as $x) if ((int)$x['usuario_id'] === $u['id']) $mia = $x; }
$pct = $stats['n'] ? round(100 * $stats['rec'] / $stats['n']) : 0;

$titulo = $h ? $h['nombre'] : 'Compra directa'; $activo = 'compra'; $leaflet = true;
require __DIR__ . '/../../includes/header.php';
$todos = $pdo->query('SELECT id,nombre FROM huertos ORDER BY nombre')->fetchAll();
?>
<div class="container">
  <div class="ar-encabezado-pag">
    <div class="ar-icono naranja"><i class="bi bi-basket3-fill"></i></div>
    <div><h1>Compra directa de huerto</h1><p>Elige el huerto, déjanos tu teléfono y pasa a comprar. Deja tu reseña y recomienda.</p></div>
  </div>

  <div class="ar-panel mb-4">
    <div class="row g-2 mb-3">
      <div class="col-md-7">
        <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="search" id="filtroTexto" class="form-control" placeholder="Busca huerto por nombre" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Buscar huerto por nombre"></div>
      </div>
      <div class="col-md-5">
        <select id="filtroHuerto" class="form-select" aria-label="Elegir huerto">
          <option value="">Todos los huertos</option>
          <?php foreach ($todos as $t): ?><option value="<?= $t['id'] ?>" <?= $id === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div id="mapaCompra" class="ar-mapa"></div>
    <?php if (!logueado()): ?><p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-lock"></i> Estás viendo ubicaciones aproximadas. <a href="<?= url('pages/auth/login.php') ?>">Inicia sesión</a> para ver la ubicación exacta y el teléfono del huerto.</p><?php endif; ?>
  </div>

  <?php if ($h): ?>
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="ar-panel mb-4">
        <h2 class="h4 mb-1"><?= e($h['nombre']) ?></h2>
        <p class="text-muted mb-2"><i class="bi bi-geo-alt"></i> <?= e($h['colonia']) ?>, <?= e($h['municipio']) ?>, Hidalgo<?= logueado() ? '' : ' · ubicación aproximada' ?></p>
        <p class="mb-2"><?= estrellas((float)$stats['prom']) ?> <strong><?= $stats['prom'] ?></strong> · <?= (int)$stats['n'] ?> reseñas · <?= $pct ?>% lo recomienda</p>
        <?php if ($inv): ?>
          <p class="mb-1 fw-bold">Cultivos disponibles</p>
          <div class="d-flex flex-wrap gap-2 mb-3"><?php foreach ($inv as $x): ?>
            <span class="badge text-bg-success-subtle border text-success-emphasis fs-6 fw-semibold"><i class="bi bi-flower1"></i> <?= e($x['nombre']) ?> · <?= number_format((float)$x['volumen_kg'], 0) ?> kg</span>
          <?php endforeach; ?></div>
        <?php endif; ?>
        <?php if (logueado()): ?><p class="mb-2"><i class="bi bi-telephone"></i> <strong><?= e($h['telefono']) ?></strong></p><?php endif; ?>
        <?php if ($h['facebook']): ?>
          <a class="btn btn-sm btn-primary" href="<?= e($h['facebook']) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-facebook"></i> Ver en Facebook</a>
        <?php else: ?><p class="small text-muted mb-0"><i class="bi bi-info-circle"></i> Este huerto no tiene red social registrada.</p><?php endif; ?>
      </div>

      <div class="ar-panel">
        <h2 class="h5"><i class="bi bi-telephone-outbound"></i> Quiero comprar</h2>
        <p class="ar-sub small">Déjanos tu teléfono y el productor te llamará para acordar cuándo pasar.</p>
        <form method="post">
          <?= csrf_campo() ?><input type="hidden" name="accion" value="pedido"><input type="hidden" name="huerto_id" value="<?= $h['id'] ?>">
          <div class="input-group mb-2"><span class="input-group-text"><i class="bi bi-phone"></i></span>
            <input class="form-control" name="telefono" inputmode="numeric" maxlength="10" placeholder="10 dígitos" value="<?= e($u['telefono'] ?? '') ?>" required aria-label="Tu teléfono"></div>
          <?php if (!logueado()): ?><div class="input-group mb-2"><span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input class="form-control" type="email" name="correo" maxlength="150" placeholder="Correo (opcional, para confirmación)" aria-label="Tu correo (opcional)"></div><?php endif; ?>
          <button class="btn btn-naranja w-100"><i class="bi bi-send"></i> Enviar solicitud</button>
        </form>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="ar-panel">
        <h2 class="h5 mb-3"><i class="bi bi-chat-square-heart"></i> Reseñas y recomendaciones</h2>
        <?php if (logueado()): ?>
          <form method="post" class="mb-4">
            <?= csrf_campo() ?><input type="hidden" name="accion" value="resena"><input type="hidden" name="huerto_id" value="<?= $h['id'] ?>">
            <p class="form-label mb-1"><?= $mia ? 'Actualiza tu reseña' : 'Deja tu reseña' ?></p>
            <div class="estrellas-input mb-2" role="radiogroup" aria-label="Calificación">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" id="e<?= $i ?>" name="estrellas" value="<?= $i ?>" <?= ($mia['estrellas'] ?? 0) == $i ? 'checked' : '' ?>>
                <label for="e<?= $i ?>" title="<?= $i ?> estrellas"><i class="bi bi-star-fill"></i></label>
              <?php endfor; ?>
            </div>
            <textarea class="form-control" name="comentario" rows="3" maxlength="200" data-contador="#cnt" placeholder="Escribe tu comentario…" required><?= e($mia['comentario'] ?? '') ?></textarea>
            <div class="text-end small text-muted" id="cnt"></div>
            <div class="form-check my-2"><input class="form-check-input" type="checkbox" id="rec" name="recomienda" <?= !empty($mia['recomienda']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="rec">Recomiendo este huerto</label></div>
            <button class="btn btn-ar"><i class="bi bi-send-check"></i> Enviar reseña</button>
            <span class="small text-muted ms-2">Solo una reseña por usuario y huerto.</span>
          </form>
        <?php else: ?>
          <div class="alert alert-light border"><i class="bi bi-info-circle"></i> ¿Sin sesión? Para reseñar debes <a href="<?= url('pages/auth/login.php') ?>">iniciar sesión</a>.</div>
        <?php endif; ?>
        <div class="d-grid gap-2">
          <?php foreach ($res as $x): ?>
            <div class="resena">
              <div class="d-flex justify-content-between"><strong><?= e($x['autor']) ?></strong><small class="text-muted"><?= fecha_corta($x['creado']) ?></small></div>
              <div><?= estrellas((float)$x['estrellas']) ?></div>
              <p class="mb-1"><?= e($x['comentario']) ?></p>
              <small class="<?= $x['recomienda'] ? 'text-success fw-bold' : 'text-muted' ?>"><?= $x['recomienda'] ? '<i class="bi bi-check-circle-fill"></i> Recomienda este huerto' : 'No lo recomienda' ?></small>
            </div>
          <?php endforeach; if (!$res): ?><p class="text-muted mb-0">Todavía no hay reseñas. ¡Sé la primera persona en opinar!</p><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php else: ?>
    <p class="text-center text-muted"><i class="bi bi-hand-index"></i> Elige un huerto en el mapa o en la lista para ver sus detalles y comprar.</p>
  <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const mapa = AR.mapa('mapaCompra', 9), txt = document.getElementById('filtroTexto'), sel = document.getElementById('filtroHuerto');
  const seleccionado = <?= $id ?: 'null' ?>;
  let capa = L.layerGroup().addTo(mapa), marcas = {};
  async function cargar(q = '') {
    capa.clearLayers(); marcas = {};
    try {
      const hs = await AR.json('api/huertos.php' + (q ? '?q=' + encodeURIComponent(q) : '')), pts = [];
      hs.forEach(h => { const m = L.marker([h.lat, h.lng], { icon: AR.pin(h.id === seleccionado) }).addTo(capa).bindPopup(AR.popup(h)); marcas[h.id] = m; pts.push([h.lat, h.lng]); });
      if (pts.length) mapa.fitBounds(pts, { padding: [40, 40], maxZoom: 13 });
      if (seleccionado && marcas[seleccionado]) marcas[seleccionado].openPopup();
    } catch (e) { console.warn(e.message); }
  }
  let t; txt.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => cargar(txt.value.trim()), 300); });
  sel.addEventListener('change', () => { location.href = sel.value ? '?id=' + sel.value : 'compra.php'; });
  cargar(txt.value.trim());
});
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
