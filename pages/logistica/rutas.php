<?php
require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('recolector', 'coordinador');
$huertos = db()->query("SELECT h.id,h.nombre,c.nombre colonia,m.nombre municipio,h.latitud,h.longitud,
   (SELECT COALESCE(SUM(volumen_kg),0) FROM inventario WHERE huerto_id=h.id) kg
   FROM huertos h JOIN colonias c ON c.id=h.colonia_id JOIN municipios m ON m.id=c.municipio_id ORDER BY m.nombre,h.nombre")->fetchAll();
$dist = cfg('punto_distribucion');
$titulo = 'Logística de recolección'; $activo = 'logistica'; $leaflet = true;
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-encabezado-pag"><div class="ar-icono naranja"><i class="bi bi-truck-front-fill"></i></div>
    <div><h1>Logística de recolección</h1><p>Generación de hojas de ruta optimizadas mediante OSRM (máximo 20 puntos).</p></div></div>
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="ar-panel">
        <h2 class="h5"><i class="bi bi-check2-square"></i> 1. Elige los huertos</h2>
        <div class="list-group mb-3" style="max-height:340px;overflow:auto" id="listaHuertos">
          <?php foreach ($huertos as $h): ?>
            <label class="list-group-item d-flex gap-2 align-items-start">
              <input class="form-check-input mt-1 chk" type="checkbox" value="<?= $h['id'] ?>" data-lat="<?= $h['latitud'] ?>" data-lng="<?= $h['longitud'] ?>">
              <span><strong><?= e($h['nombre']) ?></strong><br><small class="text-muted"><?= e($h['colonia']) ?>, <?= e($h['municipio']) ?> · <?= number_format((float)$h['kg'], 0) ?> kg</small></span>
            </label>
          <?php endforeach; ?>
        </div>
        <button id="btnRuta" class="btn btn-ar w-100 mb-2"><i class="bi bi-signpost-split"></i> 2. Generar ruta optimizada</button>
        <p class="small text-muted mb-0"><i class="bi bi-flag"></i> Destino: <?= e($dist['nombre']) ?></p>
      </div>
      <div id="hoja" class="ar-panel mt-4 d-none">
        <h2 class="h5"><i class="bi bi-list-ol"></i> Hoja de ruta</h2>
        <ol id="paradas" class="mb-3 ps-3"></ol>
        <p class="mb-1"><strong>Distancia estimada:</strong> <span id="rKm"></span> km · <strong>Tiempo:</strong> <span id="rMin"></span> min</p>
        <p class="mb-3"><strong>Carga a recoger:</strong> <span id="rKg"></span> kg</p>
        <div class="d-grid gap-2">
          <button id="btnSim" class="btn btn-naranja"><i class="bi bi-play-circle"></i> Simular recorrido</button>
          <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir hoja de ruta</button>
        </div>
      </div>
      <div class="ar-panel mt-4">
        <h2 class="h6"><i class="bi bi-broadcast"></i> Simulación de telemetría de flota</h2>
        <p class="small text-muted">Emula vehículos virtuales sobre la ruta (sin hardware GPS, según el ERS).</p>
        <label class="form-label small" for="nVeh">Vehículos: <strong id="nVehTxt">10</strong></label>
        <input type="range" class="form-range" id="nVeh" min="10" max="50" value="10">
        <button id="btnFlota" class="btn btn-ar w-100 btn-sm"><i class="bi bi-play-fill"></i> Iniciar flota virtual</button>
      </div>
    </div>
    <div class="col-lg-8">
      <div id="aviso"></div>
      <div id="mapaRuta" class="ar-mapa alto"></div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const mapa = AR.mapa('mapaRuta', 9), DIST = <?= json_encode($dist) ?>;
  const capa = L.layerGroup().addTo(mapa); let linea = [], timer = null, flota = [], timerF = null, coords = [];
  L.marker([DIST.lat, DIST.lng], { icon: L.divIcon({ className: '', html: '<div class="pin-huerto sel"><i class="bi bi-flag-fill"></i></div>', iconSize: [34, 34], iconAnchor: [17, 34] }) })
    .addTo(mapa).bindPopup('<strong>' + AR.esc(DIST.nombre) + '</strong><br>Punto de distribución');
  document.querySelectorAll('.chk').forEach(c => L.marker([c.dataset.lat, c.dataset.lng], { icon: AR.pin() }).addTo(mapa));
  const aviso = (t, tipo = 'danger') => document.getElementById('aviso').innerHTML = t ? `<div class="alert alert-${tipo}"><i class="bi bi-exclamation-triangle"></i> ${AR.esc(t)}</div>` : '';

  document.getElementById('btnRuta').addEventListener('click', async e => {
    const ids = [...document.querySelectorAll('.chk:checked')].map(c => c.value);
    if (!ids.length) return aviso('Selecciona al menos un huerto.');
    if (ids.length > 20) return aviso('El máximo es de 20 puntos por ruta.');
    aviso(''); const b = e.currentTarget; b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Calculando…';
    try {
      const r = await AR.json('api/ruta.php?ids=' + ids.join(','));
      pintar(r);
    } catch (er) { aviso(er.message); }
    finally { b.disabled = false; b.innerHTML = '<i class="bi bi-signpost-split"></i> 2. Generar ruta optimizada'; }
  });

  function pintar(r) {
    capa.clearLayers(); detener(); coords = r.geometria.coordinates.map(c => [c[1], c[0]]);
    L.polyline(coords, { color: '#1f5c2e', weight: 5, opacity: .85 }).addTo(capa);
    r.orden.forEach((p, i) => L.marker([p.lat, p.lng], { icon: L.divIcon({ className: '', iconSize: [30, 30], iconAnchor: [15, 15],
      html: `<div style="background:${i === r.orden.length - 1 ? '#e07b1f' : '#1f5c2e'};color:#fff;width:30px;height:30px;border-radius:50%;display:grid;place-items:center;font-weight:700;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4)">${i === r.orden.length - 1 ? '<i class=\'bi bi-flag-fill\'></i>' : i + 1}</div>` }) })
      .addTo(capa).bindPopup(AR.esc(p.nombre)));
    mapa.fitBounds(coords, { padding: [40, 40] });
    document.getElementById('paradas').innerHTML = r.orden.map((p, i) => i === r.orden.length - 1
      ? `<li><strong>Entregar en:</strong> ${AR.esc(p.nombre)}</li>` : `<li>${AR.esc(p.nombre)} <small class="text-muted">— ${AR.esc(p.colonia)} · ${p.kg} kg</small></li>`).join('');
    document.getElementById('rKm').textContent = r.distancia_km; document.getElementById('rMin').textContent = r.duracion_min; document.getElementById('rKg').textContent = r.kg_total;
    document.getElementById('hoja').classList.remove('d-none');
  }
  const camion = () => L.marker(coords[0], { icon: L.divIcon({ className: '', iconSize: [34, 34], iconAnchor: [17, 17], html: '<div class="pin-huerto sel" style="transform:none;border-radius:50%"><i class="bi bi-truck" style="transform:none"></i></div>' }) }).addTo(mapa);
  function detener() { clearInterval(timer); clearInterval(timerF); linea.forEach(m => mapa.removeLayer(m)); flota.forEach(v => mapa.removeLayer(v.m)); linea = []; flota = []; }
  document.getElementById('btnSim').addEventListener('click', () => {
    if (!coords.length) return; detener(); const m = camion(); linea.push(m); let i = 0, paso = Math.max(1, Math.floor(coords.length / 200));
    timer = setInterval(() => { i += paso; if (i >= coords.length) { i = coords.length - 1; clearInterval(timer); } m.setLatLng(coords[i]); }, 60);
  });
  const n = document.getElementById('nVeh'); n.addEventListener('input', () => document.getElementById('nVehTxt').textContent = n.value);
  document.getElementById('btnFlota').addEventListener('click', () => {
    if (!coords.length) return aviso('Primero genera una ruta para que la flota circule sobre ella.', 'warning');
    aviso(''); detener(); const total = +n.value;
    for (let k = 0; k < total; k++) { const m = camion(); flota.push({ m, i: Math.floor(Math.random() * coords.length), v: 1 + Math.floor(Math.random() * 3) }); }
    timerF = setInterval(() => flota.forEach(v => { v.i = (v.i + v.v) % coords.length; v.m.setLatLng(coords[v.i]); }), 150);   // los vehículos siguen la topología vial (sin brincos)
  });
});
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
