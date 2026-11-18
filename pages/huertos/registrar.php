<?php
require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('productor');
$pdo = db(); $u = usuario(); $errores = [];
$municipios = $pdo->query('SELECT id, nombre FROM municipios ORDER BY nombre')->fetchAll();
$cultivos   = $pdo->query('SELECT id, nombre FROM cultivos ORDER BY nombre')->fetchAll();

if (es_post()) {
    csrf_validar();
    $nombre = post('nombre'); $mun = (int)post('municipio'); $col = post('colonia');
    $tel = post('telefono'); $fb = post('facebook'); $cult = (int)post('cultivo');
    $vol = (float)post('volumen'); $lat = post('latitud'); $lng = post('longitud');

    if (mb_strlen($nombre) < 3)                       $errores[] = 'Escribe el nombre del huerto.';
    if (!$mun)                                        $errores[] = 'Selecciona el municipio.';
    if (mb_strlen($col) < 2)                          $errores[] = 'Escribe la colonia o poblado.';
    if (!es_telefono($tel))                           $errores[] = 'El teléfono de contacto debe tener 10 dígitos.';
    if ($fb !== '' && !preg_match('#^https?://\S+$#', $fb)) $errores[] = 'El enlace de la red social debe empezar con http:// o https://';
    if (!is_numeric($lat) || !is_numeric($lng) || abs($lat) > 90 || abs($lng) > 180) $errores[] = 'Marca la ubicación del huerto en el mapa.';
    if ($vol < 0)                                     $errores[] = 'El volumen no puede ser negativo.';

    if (!$errores) {
        $pdo->beginTransaction();
        $c = $pdo->prepare('SELECT id FROM colonias WHERE municipio_id=? AND nombre=?'); $c->execute([$mun, $col]);
        $colId = $c->fetchColumn();
        if (!$colId) { $pdo->prepare('INSERT INTO colonias (municipio_id,nombre) VALUES (?,?)')->execute([$mun, $col]); $colId = $pdo->lastInsertId(); }
        $pdo->prepare('INSERT INTO huertos (dueno_id,colonia_id,nombre,telefono,facebook,latitud,longitud) VALUES (?,?,?,?,?,?,?)')
            ->execute([$u['id'], $colId, $nombre, $tel, $fb ?: null, $lat, $lng]);
        $hid = $pdo->lastInsertId();
        if ($cult && $vol > 0) {
            $pdo->prepare('INSERT INTO inventario (huerto_id,cultivo_id,volumen_kg) VALUES (?,?,?)')->execute([$hid, $cult, $vol]);
        }
        $pdo->commit();
        flash('success', 'Huerto registrado correctamente.');
        redirigir('pages/huertos/compra.php?id=' . $hid);
    }
}
$titulo = 'Registro de huertos'; $activo = 'huertos'; $leaflet = true;
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-encabezado-pag">
    <div class="ar-icono"><i class="bi bi-geo-alt-fill"></i></div>
    <div><h1>Registro y geolocalización de huertos</h1><p>Completa los datos para dar de alta tu huerto familiar o mercantil.</p></div>
  </div>
  <?php if ($errores): ?>
    <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errores as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div>
  <?php endif; ?>
  <form method="post" class="row g-4" novalidate>
    <?= csrf_campo() ?>
    <div class="col-lg-6">
      <div class="ar-panel">
        <div class="mb-3"><label class="form-label">Nombre del productor</label>
          <input class="form-control" value="<?= e($u['nombre']) ?>" disabled></div>
        <div class="mb-3"><label class="form-label" for="nombre">Nombre del huerto</label>
          <input class="form-control" id="nombre" name="nombre" value="<?= e(post('nombre')) ?>" required></div>
        <div class="row g-3 mb-3">
          <div class="col-sm-6"><label class="form-label" for="municipio">Municipio</label>
            <select class="form-select" id="municipio" name="municipio" required>
              <option value="">Selecciona…</option>
              <?php foreach ($municipios as $m): ?><option value="<?= $m['id'] ?>" <?= (int)post('municipio') === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['nombre']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="col-sm-6"><label class="form-label" for="colonia">Colonia o poblado</label>
            <input class="form-control" id="colonia" name="colonia" value="<?= e(post('colonia')) ?>" required></div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-sm-6"><label class="form-label" for="cultivo">Tipo de cultivo principal</label>
            <select class="form-select" id="cultivo" name="cultivo">
              <option value="">(Lo agrego después)</option>
              <?php foreach ($cultivos as $c): ?><option value="<?= $c['id'] ?>" <?= (int)post('cultivo') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="col-sm-6"><label class="form-label" for="volumen">Volumen estimado (kg)</label>
            <input class="form-control" type="number" min="0" step="0.5" id="volumen" name="volumen" value="<?= e(post('volumen')) ?>"></div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-sm-6"><label class="form-label" for="telefono">Teléfono de contacto</label>
            <input class="form-control" id="telefono" name="telefono" maxlength="10" inputmode="numeric" value="<?= e(post('telefono', $u['telefono'])) ?>" required></div>
          <div class="col-sm-6"><label class="form-label" for="facebook">Facebook u otra red (opcional)</label>
            <input class="form-control" id="facebook" name="facebook" placeholder="https://facebook.com/…" value="<?= e(post('facebook')) ?>"></div>
        </div>
        <label class="form-label" for="latitud">Coordenadas (latitud, longitud)</label>
        <div class="input-group mb-2">
          <input class="form-control" id="latitud" name="latitud" placeholder="20.12340" value="<?= e(post('latitud')) ?>" required>
          <input class="form-control" id="longitud" name="longitud" placeholder="-98.56780" value="<?= e(post('longitud')) ?>" required>
        </div>
        <button type="button" id="btnUbicacion" class="btn btn-outline-success btn-sm mb-3"><i class="bi bi-crosshair"></i> Usar mi ubicación actual</button>
        <div><button class="btn btn-ar btn-lg" type="submit"><i class="bi bi-check2-circle"></i> Registrar huerto</button></div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="ar-panel">
        <h2 class="h5"><i class="bi bi-cursor"></i> Toca el mapa para marcar tu huerto</h2>
        <p class="ar-sub small">Acerca el mapa y haz clic en el lugar exacto. Puedes arrastrar el marcador para ajustarlo.</p>
        <div id="mapaRegistro" class="ar-mapa"></div>
      </div>
    </div>
  </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const mapa = AR.mapa('mapaRegistro', 9), lat = document.getElementById('latitud'), lng = document.getElementById('longitud');
  let pin = null;
  const poner = (la, lo, zoom) => {
    if (pin) pin.setLatLng([la, lo]);
    else { pin = L.marker([la, lo], { icon: AR.pin(true), draggable: true }).addTo(mapa);
           pin.on('dragend', () => fijar(pin.getLatLng().lat, pin.getLatLng().lng)); }
    fijar(la, lo); if (zoom) mapa.setView([la, lo], zoom);
  };
  const fijar = (la, lo) => { lat.value = (+la).toFixed(6); lng.value = (+lo).toFixed(6); };
  mapa.on('click', e => poner(e.latlng.lat, e.latlng.lng));
  if (lat.value && lng.value && !isNaN(+lat.value)) poner(+lat.value, +lng.value, 13);
  document.getElementById('btnUbicacion').addEventListener('click', () => {
    if (!navigator.geolocation) return alert('Tu navegador no permite geolocalización.');
    navigator.geolocation.getCurrentPosition(p => poner(p.coords.latitude, p.coords.longitude, 15),
      () => alert('No se pudo obtener tu ubicación. Marca el punto en el mapa.'));
  });
});
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
