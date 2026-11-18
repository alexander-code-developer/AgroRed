<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notificaciones.php';
requerir_rol('productor');
$pdo = db(); $u = usuario();
// Máquina de estados FIJA en código (restricción de negocio del ERS): solicitado -> contactado -> completado | cancelado
const FLUJO = ['solicitado' => ['contactado', 'cancelado'], 'contactado' => ['completado', 'cancelado']];

if (es_post()) {
    csrf_validar();
    $pid = (int)post('id'); $nuevo = post('estado');
    $st = $pdo->prepare('SELECT e.nombre estado FROM pedidos p JOIN huertos h ON h.id=p.huerto_id JOIN estados_pedido e ON e.id=p.estado_id WHERE p.id=? AND h.dueno_id=?');
    $st->execute([$pid, $u['id']]); $actual = $st->fetchColumn();
    if (!$actual) flash('danger', 'Pedido no encontrado.');
    elseif (!in_array($nuevo, FLUJO[$actual] ?? [], true)) flash('danger', 'Cambio no permitido: el pedido debe seguir el flujo solicitado → contactado → completado. No se pueden reabrir pedidos cerrados.');
    else {
        $pdo->prepare('UPDATE pedidos SET estado_id=(SELECT id FROM estados_pedido WHERE nombre=?) WHERE id=?')->execute([$nuevo, $pid]);
        notif_pedido_estado($pid, $nuevo);
        flash('success', "Pedido #$pid marcado como $nuevo.");
    }
    redirigir('pages/productor/pedidos.php');
}
$f = $_GET['estado'] ?? '';
$sql = 'SELECT p.id,p.creado,p.telefono,e.nombre estado,h.nombre huerto FROM pedidos p JOIN huertos h ON h.id=p.huerto_id JOIN estados_pedido e ON e.id=p.estado_id WHERE h.dueno_id=?';
$par = [$u['id']];
if (in_array($f, ['solicitado', 'contactado', 'completado', 'cancelado'], true)) { $sql .= ' AND e.nombre=?'; $par[] = $f; }
$st = $pdo->prepare($sql . ' ORDER BY p.creado DESC'); $st->execute($par); $pedidos = $st->fetchAll();

$titulo = 'Seguimiento de pedidos';
require __DIR__ . '/../../includes/header.php';
?>
<div class="container">
  <div class="ar-encabezado-pag"><div class="ar-icono"><i class="bi bi-telephone-fill"></i></div>
    <div><h1>Seguimiento de pedidos</h1><p>Atiende las solicitudes de compra de tu huerto y actualiza el estado de cada pedido.</p></div></div>
  <div class="ar-panel">
    <form class="row g-2 align-items-center mb-3" method="get">
      <div class="col-auto"><label class="col-form-label fw-bold" for="estado">Filtrar por estado</label></div>
      <div class="col-auto"><select class="form-select" id="estado" name="estado" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach (['solicitado', 'contactado', 'completado', 'cancelado'] as $s): ?><option value="<?= $s ?>" <?= $f === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
      </select></div>
    </form>
    <div class="table-responsive">
      <table class="table table-ar align-middle">
        <thead><tr><th>Pedido</th><th>Fecha</th><th>Huerto</th><th>Teléfono del comprador</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($pedidos as $p): $sig = FLUJO[$p['estado']] ?? []; ?>
          <tr><td>#<?= str_pad($p['id'], 3, '0', STR_PAD_LEFT) ?></td><td><?= fecha_corta($p['creado']) ?></td><td><?= e($p['huerto']) ?></td>
            <td><a href="tel:<?= e($p['telefono']) ?>"><i class="bi bi-telephone"></i> <?= e($p['telefono']) ?></a></td>
            <td><span class="estado <?= e($p['estado']) ?>"><?= e($p['estado']) ?></span></td>
            <td>
              <?php if ($sig): ?>
                <form method="post" class="d-flex gap-1 flex-wrap"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <?php if (in_array('contactado', $sig)): ?><button name="estado" value="contactado" class="btn btn-sm btn-ar">Marcar contactado</button><?php endif; ?>
                  <?php if (in_array('completado', $sig)): ?><button name="estado" value="completado" class="btn btn-sm btn-ar">Marcar completado</button><?php endif; ?>
                  <button name="estado" value="cancelado" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Cancelar este pedido?')">Cancelar</button>
                </form>
              <?php else: ?><span class="text-muted small fst-italic">— sin acciones (pedido cerrado)</span><?php endif; ?>
            </td></tr>
        <?php endforeach; if (!$pedidos): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay pedidos con este filtro.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="alert alert-success py-2 small mb-0"><i class="bi bi-diagram-3"></i> Flujo permitido: solicitado → contactado → completado | cancelado (solo desde solicitado o contactado)</div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
