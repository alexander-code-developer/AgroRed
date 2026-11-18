<?php
require_once __DIR__ . '/../includes/auth.php';
requerir_rol('coordinador', 'productor');
$pdo = db();
$d = $pdo->query("SELECT (SELECT COUNT(*) FROM huertos) huertos,
   (SELECT COALESCE(SUM(volumen_kg),0) FROM inventario) kg,
   (SELECT COUNT(*) FROM pedidos WHERE estado_id=3) completados,
   (SELECT COUNT(DISTINCT telefono) FROM pedidos WHERE estado_id IN (2,3)) familias,
   (SELECT COALESCE(ROUND(AVG(estrellas),1),0) FROM resenas) calif")->fetch();
$co2 = round($d['kg'] * cfg('factor_co2'), 1);
$porCultivo = $pdo->query('SELECT cu.nombre, SUM(i.volumen_kg) kg FROM inventario i JOIN cultivos cu ON cu.id=i.cultivo_id GROUP BY cu.nombre ORDER BY kg DESC')->fetchAll();
$porMun = $pdo->query('SELECT m.nombre, COUNT(*) n FROM huertos h JOIN colonias c ON c.id=h.colonia_id JOIN municipios m ON m.id=c.municipio_id GROUP BY m.nombre ORDER BY n DESC')->fetchAll();
$max = max(1, ...array_map(fn($x) => (float)$x['kg'], $porCultivo ?: [['kg' => 1]]));
$maxM = max(1, ...array_map(fn($x) => (int)$x['n'], $porMun ?: [['n' => 1]]));
$titulo = 'Dashboard de impacto'; $activo = 'dashboard';
require __DIR__ . '/../includes/header.php';
$mets = [['bi-basket2-fill', '', number_format((float)$d['kg'], 0) . ' kg', 'Alimento rescatado'], ['bi-globe-americas', 'naranja', $co2 . ' kg CO₂', 'Huella de carbono reducida (estimada)'],
         ['bi-people-fill', '', (int)$d['familias'], 'Familias beneficiadas'], ['bi-flower1', 'naranja', (int)$d['huertos'], 'Huertos registrados']];
?>
<div class="container">
  <div class="ar-encabezado-pag"><div class="ar-icono"><i class="bi bi-bar-chart-line-fill"></i></div>
    <div><h1>Dashboard de impacto social y ambiental</h1><p>Métricas de alimento rescatado y reducción de huella de carbono (ODS 2 y ODS 12).</p></div></div>
  <div class="row g-3 mb-4">
    <?php foreach ($mets as [$ico, $cls, $num, $lbl]): ?>
      <div class="col-6 col-lg-3"><div class="ar-card metrica"><div class="ar-icono <?= $cls ?> mx-auto"><i class="bi <?= $ico ?>"></i></div><div class="num"><?= $num ?></div><div class="lbl"><?= $lbl ?></div></div></div>
    <?php endforeach; ?>
  </div>
  <div class="row g-4">
    <div class="col-lg-7"><div class="ar-panel"><h2 class="h5">Volumen por cultivo (kg)</h2>
      <?php foreach ($porCultivo as $c): ?>
        <div class="mb-2"><div class="d-flex justify-content-between small fw-bold"><span><?= e($c['nombre']) ?></span><span><?= number_format((float)$c['kg'], 0) ?> kg</span></div>
          <div class="progress" role="progressbar" aria-label="<?= e($c['nombre']) ?>" style="height:14px"><div class="progress-bar bg-success" style="width:<?= round(100 * $c['kg'] / $max) ?>%"></div></div></div>
      <?php endforeach; ?></div></div>
    <div class="col-lg-5"><div class="ar-panel"><h2 class="h5">Huertos por municipio</h2>
      <?php foreach ($porMun as $m): ?>
        <div class="mb-2"><div class="d-flex justify-content-between small fw-bold"><span><?= e($m['nombre']) ?></span><span><?= $m['n'] ?></span></div>
          <div class="progress" role="progressbar" aria-label="<?= e($m['nombre']) ?>" style="height:14px"><div class="progress-bar" style="background:var(--ar-naranja);width:<?= round(100 * $m['n'] / $maxM) ?>%"></div></div></div>
      <?php endforeach; ?>
      <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle"></i> Pedidos completados: <?= (int)$d['completados'] ?> · Calificación promedio: <?= $d['calif'] ?>/5. El factor de CO₂ (<?= cfg('factor_co2') ?> kg por kg rescatado) es un estimado configurable en <code>config/config.php</code>.</p></div></div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
