<?php
require_once __DIR__ . '/../includes/auth.php';
requerir_login();
$u = usuario(); $pdo = db(); $herr = null;
if (es_rol('productor')) {
    $q = $pdo->prepare("SELECT (SELECT COUNT(*) FROM pedidos p JOIN huertos h ON h.id=p.huerto_id WHERE h.dueno_id=? AND p.estado_id=1) nuevos,
                               (SELECT COUNT(*) FROM mensajes m JOIN huertos h ON h.id=m.para_huerto_id WHERE h.dueno_id=?) msgs,
                               (SELECT COALESCE(SUM(CASE WHEN t.nombre='ingreso' THEN f.monto ELSE -f.monto END),0)
                                  FROM finanzas_movimientos f JOIN tipos_movimiento t ON t.id=f.tipo_id WHERE f.usuario_id=?) balance");
    $q->execute([$u['id'], $u['id'], $u['id']]); $herr = $q->fetch();
    $hs = $pdo->prepare('SELECT h.id,h.nombre,c.nombre colonia,m.nombre municipio FROM huertos h JOIN colonias c ON c.id=h.colonia_id JOIN municipios m ON m.id=c.municipio_id WHERE h.dueno_id=?');
    $hs->execute([$u['id']]); $misHuertos = $hs->fetchAll();
}
$roles = ['admin' => 'Administrador', 'productor' => 'Productor / Dueño de huerto', 'recolector' => 'Recolector', 'coordinador' => 'Coordinador / Analista social', 'usuario' => 'Cliente'];
$titulo = 'Mi perfil'; $activo = '';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="ar-encabezado-pag">
    <div class="ar-icono"><i class="bi bi-person-circle"></i></div>
    <div><h1>Mi perfil</h1><p><?= e($roles[$u['rol']] ?? $u['rol']) ?><?= !empty($misHuertos) ? ' · Huerto: ' . e($misHuertos[0]['nombre']) . ' (' . e($misHuertos[0]['colonia']) . '), ' . e($misHuertos[0]['municipio']) : '' ?></p></div>
  </div>
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="ar-panel">
        <h2 class="h5 text-success mb-3">Datos de la cuenta</h2>
        <dl class="row mb-3">
          <dt class="col-5">Nombre completo</dt><dd class="col-7"><?= e($u['nombre']) ?></dd>
          <dt class="col-5">Correo electrónico</dt><dd class="col-7 text-break"><?= e($u['correo']) ?></dd>
          <dt class="col-5">Teléfono</dt><dd class="col-7"><?= e($u['telefono']) ?></dd>
          <dt class="col-5">Rol</dt><dd class="col-7"><?= e($roles[$u['rol']] ?? $u['rol']) ?></dd>
        </dl>
        <a href="<?= url('pages/auth/logout.php') ?>" class="btn btn-ar"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
      </div>
    </div>
    <div class="col-lg-7">
      <?php if ($herr): ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 text-success mb-0">Mis herramientas de productor</h2>
          <span class="badge text-bg-warning-subtle border border-warning-subtle text-warning-emphasis">Visible solo para el rol productor</span>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-sm-4"><a class="ar-card" href="<?= url('pages/productor/pedidos.php') ?>"><div class="ar-icono"><i class="bi bi-telephone-fill"></i></div><h3>Pedidos</h3><p class="mb-1 small text-muted"><?= (int)$herr['nuevos'] ?> solicitudes nuevas</p><span class="small fw-bold">Ir a pedidos <i class="bi bi-arrow-right"></i></span></a></div>
          <div class="col-sm-4"><a class="ar-card" href="<?= url('pages/productor/mensajes.php') ?>"><div class="ar-icono naranja"><i class="bi bi-envelope-fill"></i></div><h3>Mensajes</h3><p class="mb-1 small text-muted"><?= (int)$herr['msgs'] ?> mensajes recibidos</p><span class="small fw-bold">Ir a mensajes <i class="bi bi-arrow-right"></i></span></a></div>
          <div class="col-sm-4"><a class="ar-card" href="<?= url('pages/productor/finanzas.php') ?>"><div class="ar-icono"><i class="bi bi-wallet2"></i></div><h3>Finanzas</h3><p class="mb-1 small text-muted">Balance: $<?= number_format((float)$herr['balance'], 0) ?> MXN</p><span class="small fw-bold">Ir a finanzas <i class="bi bi-arrow-right"></i></span></a></div>
        </div>
        <div class="ar-panel">
          <h3 class="h6">Mis huertos</h3>
          <?php foreach ($misHuertos as $h): ?>
            <a class="d-block py-1" href="<?= url('pages/huertos/compra.php?id=' . $h['id']) ?>"><i class="bi bi-flower1"></i> <?= e($h['nombre']) ?> <span class="text-muted small">· <?= e($h['colonia']) ?>, <?= e($h['municipio']) ?></span></a>
          <?php endforeach; if (!$misHuertos): ?><p class="text-muted mb-0">Aún no registras huertos. <a href="<?= url('pages/huertos/registrar.php') ?>">Registra el primero</a>.</p><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="ar-panel">
          <h2 class="h5 text-success">Otros estados de la pantalla</h2>
          <p class="mb-0 text-muted">Con tu rol solo se muestran los datos de la cuenta y las opciones permitidas. Las herramientas de productor (pedidos, mensajes y finanzas) están ocultas.</p>
          <?php if (es_rol('recolector', 'coordinador')): ?><a class="btn btn-ar mt-3" href="<?= url('pages/logistica/rutas.php') ?>"><i class="bi bi-truck"></i> Ir a logística</a><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
