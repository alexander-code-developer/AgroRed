<?php
require_once __DIR__ . '/../../includes/auth.php';
$pdo = db(); $u = usuario();

// ---- Alta de inventario (solo productor, solo en sus huertos) ----
if (es_post()) {
    requerir_rol('productor');
    csrf_validar();
    $hid = (int)post('huerto'); $cid = (int)post('cultivo'); $vol = (float)post('volumen'); $fec = post('cosecha');
    $ok = $pdo->prepare('SELECT 1 FROM huertos WHERE id=? AND dueno_id=?'); $ok->execute([$hid, $u['id']]);
    if (!$ok->fetch() && $u['rol'] !== 'admin') flash('danger', 'Ese huerto no es tuyo.');
    elseif (!$cid || $vol < 0)                  flash('danger', 'Selecciona un cultivo y un volumen válido.');
    else {
        $pdo->prepare('INSERT INTO inventario (huerto_id,cultivo_id,volumen_kg,cosecha_estimada) VALUES (?,?,?,?)')
            ->execute([$hid, $cid, $vol, $fec ?: null]);
        flash('success', 'Inventario actualizado.');
    }
    redirigir('pages/huertos/inventario.php');
}

$filtro = (int)($_GET['huerto'] ?? 0);
$sql = "SELECT h.nombre AS huerto, cu.nombre AS cultivo, i.volumen_kg, i.cosecha_estimada,
               cu.kcal, cu.proteina_g, cu.vitamina_c_mg,
               ROUND(i.volumen_kg*10*cu.kcal) AS kcal_total
        FROM inventario i JOIN huertos h ON h.id=i.huerto_id JOIN cultivos cu ON cu.id=i.cultivo_id"
     . ($filtro ? ' WHERE h.id=?' : '') . ' ORDER BY h.nombre, cu.nombre';
$st = $pdo->prepare($sql); $st->execute($filtro ? [$filtro] : []); $filas = $st->fetchAll();
$huertos = $pdo->query('SELECT id,nombre FROM huertos ORDER BY nombre')->fetchAll();
$mios = []; $cultivos = [];
if (es_rol('productor')) {
    $m = $pdo->prepare('SELECT id,nombre FROM huertos WHERE dueno_id=? ORDER BY nombre'); $m->execute([$u['id']]); $mios = $m->fetchAll();
    $cultivos = $pdo->query('SELECT id,nombre FROM cultivos ORDER BY nombre')->fetchAll();
}
$totKg = array_sum(array_column($filas, 'volumen_kg'));

$titulo = 'Inventario y nutrición'; $activo = 'inventario';
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-encabezado-pag">
    <div class="ar-icono"><i class="bi bi-clipboard2-data-fill"></i></div>
    <div><h1>Inventario y predicción nutricional</h1><p>Control del volumen estimado de cosecha e información nutricional de cada cultivo.</p></div>
  </div>

  <div class="row g-4">
    <div class="<?= $mios ? 'col-lg-8' : 'col-12' ?>">
      <div class="ar-panel">
        <form class="row g-2 align-items-end mb-3" method="get">
          <div class="col-sm-8"><label class="form-label" for="huerto">Filtrar por huerto</label>
            <select class="form-select" id="huerto" name="huerto" onchange="this.form.submit()">
              <option value="0">Todos los huertos</option>
              <?php foreach ($huertos as $h): ?><option value="<?= $h['id'] ?>" <?= $filtro === (int)$h['id'] ? 'selected' : '' ?>><?= e($h['nombre']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="col-sm-4 text-sm-end"><span class="badge text-bg-success fs-6"><i class="bi bi-box-seam"></i> <?= number_format($totKg, 0) ?> kg en total</span></div>
        </form>
        <div class="table-responsive">
          <table class="table table-ar align-middle">
            <thead><tr><th>Huerto</th><th>Cultivo</th><th class="text-end">Volumen (kg)</th><th class="text-end">Calorías*</th><th class="text-end">Proteínas*</th><th class="text-end">Vitamina C*</th><th>Cosecha</th></tr></thead>
            <tbody>
              <?php foreach ($filas as $f): ?>
                <tr><td><?= e($f['huerto']) ?></td><td><strong><?= e($f['cultivo']) ?></strong></td>
                  <td class="text-end"><?= number_format((float)$f['volumen_kg'], 0) ?></td>
                  <td class="text-end"><?= (float)$f['kcal'] ?> kcal</td>
                  <td class="text-end"><?= (float)$f['proteina_g'] ?> g</td>
                  <td class="text-end"><?= (float)$f['vitamina_c_mg'] ?> mg</td>
                  <td><?= fecha_corta($f['cosecha_estimada']) ?></td></tr>
              <?php endforeach; if (!$filas): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Aún no hay inventario registrado.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <p class="small text-muted mb-0">* Valores nutricionales por cada 100 g de producto.</p>
      </div>
    </div>
    <?php if ($mios): ?>
    <div class="col-lg-4">
      <div class="ar-panel">
        <h2 class="h5"><i class="bi bi-plus-circle"></i> Agregar cosecha</h2>
        <form method="post">
          <?= csrf_campo() ?>
          <div class="mb-2"><label class="form-label" for="h2">Huerto</label>
            <select class="form-select" id="h2" name="huerto"><?php foreach ($mios as $h) echo '<option value="' . $h['id'] . '">' . e($h['nombre']) . '</option>'; ?></select></div>
          <div class="mb-2"><label class="form-label" for="c2">Cultivo</label>
            <select class="form-select" id="c2" name="cultivo"><?php foreach ($cultivos as $c) echo '<option value="' . $c['id'] . '">' . e($c['nombre']) . '</option>'; ?></select></div>
          <div class="mb-2"><label class="form-label" for="v2">Volumen (kg)</label>
            <input class="form-control" type="number" id="v2" name="volumen" min="0" step="0.5" required></div>
          <div class="mb-3"><label class="form-label" for="f2">Cosecha estimada</label>
            <input class="form-control" type="date" id="f2" name="cosecha"></div>
          <button class="btn btn-ar w-100"><i class="bi bi-save"></i> Guardar</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
