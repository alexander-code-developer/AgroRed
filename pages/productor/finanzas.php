<?php
require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('productor');
$pdo = db(); $u = usuario();
if (es_post()) {
    csrf_validar();
    $tipo = post('tipo'); $con = post('concepto'); $monto = (float)post('monto'); $fec = post('fecha');
    if (!in_array($tipo, ['ingreso', 'gasto'], true) || $con === '' || $monto <= 0 || !strtotime($fec)) flash('danger', 'Completa todos los campos con valores válidos.');
    else {
        $pdo->prepare('INSERT INTO finanzas_movimientos (usuario_id,tipo_id,concepto,monto,fecha) VALUES (?,(SELECT id FROM tipos_movimiento WHERE nombre=?),?,?,?)')
            ->execute([$u['id'], $tipo, $con, $monto, $fec]);
        flash('success', 'Movimiento registrado.');
    }
    redirigir('pages/productor/finanzas.php');
}
$mes = $_GET['mes'] ?? '';
$mov = $pdo->prepare("SELECT m.*, t.nombre tipo FROM finanzas_movimientos m JOIN tipos_movimiento t ON t.id=m.tipo_id
                      WHERE m.usuario_id=? " . ($mes ? "AND DATE_FORMAT(m.fecha,'%Y-%m')=? " : '') . 'ORDER BY m.fecha DESC');
$mov->execute($mes ? [$u['id'], $mes] : [$u['id']]); $movs = $mov->fetchAll();
$res = $pdo->prepare('SELECT mes, balance FROM v_finanzas_mensual WHERE usuario_id=? ORDER BY mes'); $res->execute([$u['id']]); $meses = $res->fetchAll();
$total = array_sum(array_column($meses, 'balance'));
// incremento / decremento respecto al mes anterior
foreach ($meses as $i => &$m) { $prev = $meses[$i - 1]['balance'] ?? null; $m['cambio'] = $prev === null ? null : $m['balance'] - $prev;
  $m['pct'] = ($prev === null || (float)$prev == 0) ? null : round(100 * $m['cambio'] / abs($prev), 1); } unset($m);
$titulo = 'Finanzas del productor';
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-encabezado-pag"><div class="ar-icono"><i class="bi bi-wallet2"></i></div>
    <div><h1>Mi perfil financiero</h1><p>Ganancias y ahorros estimados de la red comunitaria.</p></div></div>
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="ar-panel mb-4 text-center"><div class="text-muted fw-bold">Balance general acumulado</div>
        <div class="ar-serif display-5 <?= $total >= 0 ? 'text-success' : 'text-danger' ?>">$<?= number_format($total, 0) ?> <small class="fs-6">MXN</small></div></div>
      <div class="ar-panel"><h2 class="h5"><i class="bi bi-plus-circle"></i> Registrar movimiento</h2>
        <form method="post"><?= csrf_campo() ?>
          <div class="mb-2"><label class="form-label" for="tipo">Tipo</label><select class="form-select" id="tipo" name="tipo"><option value="ingreso">Ingreso / ahorro</option><option value="gasto">Gasto</option></select></div>
          <div class="mb-2"><label class="form-label" for="concepto">Concepto</label><input class="form-control" id="concepto" name="concepto" maxlength="200" placeholder="Venta local de tomate" required></div>
          <div class="row g-2 mb-3"><div class="col-6"><label class="form-label" for="monto">Monto (MXN)</label><input class="form-control" type="number" min="0.01" step="0.01" id="monto" name="monto" required></div>
            <div class="col-6"><label class="form-label" for="fecha">Fecha</label><input class="form-control" type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required></div></div>
          <button class="btn btn-ar w-100"><i class="bi bi-save"></i> Guardar</button></form></div>
    </div>
    <div class="col-lg-8">
      <div class="ar-panel mb-4"><h2 class="h5">Balance por mes</h2>
        <div class="table-responsive"><table class="table table-ar mb-0"><thead><tr><th>Mes</th><th class="text-end">Balance</th><th class="text-end">Cambio vs. mes anterior</th></tr></thead><tbody>
          <?php foreach (array_reverse($meses) as $m): ?><tr><td><a href="?mes=<?= e($m['mes']) ?>"><?= e($m['mes']) ?></a></td><td class="text-end">$<?= number_format((float)$m['balance'], 0) ?></td>
            <td class="text-end"><?php if ($m['cambio'] === null): ?>—<?php else: ?><span class="<?= $m['cambio'] >= 0 ? 'text-success' : 'text-danger' ?>"><i class="bi bi-arrow-<?= $m['cambio'] >= 0 ? 'up' : 'down' ?>-short"></i> $<?= number_format(abs($m['cambio']), 0) ?><?= $m['pct'] !== null ? ' (' . $m['pct'] . '%)' : '' ?></span><?php endif; ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
      <div class="ar-panel"><div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0">Historial de ingresos / gastos<?= $mes ? ' (' . e($mes) . ')' : '' ?></h2><?php if ($mes): ?><a href="?" class="btn btn-sm btn-outline-secondary">Ver todo</a><?php endif; ?></div>
        <div class="table-responsive"><table class="table table-ar align-middle mb-0"><thead><tr><th>Fecha</th><th>Concepto</th><th>Tipo</th><th class="text-end">Monto</th></tr></thead><tbody>
          <?php foreach ($movs as $m): ?><tr><td><?= fecha_corta($m['fecha']) ?></td><td><?= e($m['concepto']) ?></td>
            <td><span class="badge <?= $m['tipo'] === 'ingreso' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= e($m['tipo']) ?></span></td>
            <td class="text-end"><?= $m['tipo'] === 'ingreso' ? '+' : '−' ?>$<?= number_format((float)$m['monto'], 0) ?></td></tr><?php endforeach; if (!$movs): ?><tr><td colspan="4" class="text-center text-muted py-3">Sin movimientos.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
