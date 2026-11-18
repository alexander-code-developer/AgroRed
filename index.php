<?php
$titulo = 'Inicio'; $activo = 'inicio'; $leaflet = true;
require __DIR__ . '/includes/header.php';
$tot = db()->query("SELECT (SELECT COUNT(*) FROM huertos) h, (SELECT COALESCE(SUM(volumen_kg),0) FROM inventario) kg")->fetch();
$modulos = [
  ['bi-geo-alt-fill',  '',        'Registro y geolocalización', 'Da de alta tu huerto familiar o mercantil y ubícalo en el mapa por colonia o poblado.', 'pages/huertos/registrar.php'],
  ['bi-basket3-fill',  'naranja', 'Compra directa',             'Elige un huerto, deja tu teléfono y pasa a comprar. Después deja tu reseña.',        'pages/huertos/compra.php'],
  ['bi-clipboard2-data-fill', '', 'Inventario y nutrición',     'Controla el volumen estimado de cosecha y consulta el aporte nutricional de cada cultivo.', 'pages/huertos/inventario.php'],
  ['bi-truck-front-fill','naranja','Logística de recolección',  'Genera hojas de ruta optimizadas con OSRM para los recolectores.',                    'pages/logistica/rutas.php'],
  ['bi-bar-chart-line-fill','',   'Dashboard de impacto',       'Mide el alimento rescatado, la huella de carbono evitada y las familias beneficiadas.', 'pages/dashboard.php'],
  ['bi-chat-heart-fill','naranja','Red de productores',         'Mensajería para proponer intercambios y negocios entre huertos.',                      'pages/productor/mensajes.php'],
];
?>
<section class="ar-hero">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <h1 class="mb-3">Del huerto de tu colonia a la mesa de tu comunidad</h1>
        <p class="lead mb-4">Conectamos a productores de Hidalgo, optimizamos la recolección y promovemos la seguridad alimentaria en el Valle del Mezquital y toda la región.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('pages/huertos/registrar.php') ?>" class="btn btn-naranja btn-lg"><i class="bi bi-plus-circle"></i> Registrar huerto</a>
          <a href="<?= url('pages/dashboard.php') ?>" class="btn btn-ar-claro btn-lg"><i class="bi bi-bar-chart-line"></i> Ver impacto</a>
        </div>
        <div class="d-flex gap-4 mt-4 text-white-50">
          <div><strong class="fs-4 text-white"><?= (int)$tot['h'] ?></strong><br>huertos en el mapa</div>
          <div><strong class="fs-4 text-white"><?= number_format((float)$tot['kg'], 0) ?> kg</strong><br>de cosecha estimada</div>
        </div>
      </div>
      <div class="col-lg-5 text-center">
        <img class="ar-hero-logo" src="<?= url('assets/img/logo.png') ?>" alt="Logotipo de AgroRed Hidalgo: un árbol frutal sobre campos de cultivo">
      </div>
    </div>
  </div>
</section>

<section class="container my-5">
  <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
    <div>
      <h2 class="ar-titulo mb-1"><i class="bi bi-map text-success"></i> Mapa de huertos en Hidalgo</h2>
      <p class="ar-sub mb-0">Toca un marcador para ver el huerto, sus reseñas y comprar directo.</p>
    </div>
    <?php if (!logueado()): ?>
      <span class="badge text-bg-light border"><i class="bi bi-shield-lock"></i> Ubicaciones aproximadas · <a href="<?= url('pages/auth/login.php') ?>">inicia sesión</a> para ver la exacta</span>
    <?php endif; ?>
  </div>
  <div id="mapa" class="ar-mapa alto" role="region" aria-label="Mapa de huertos registrados"></div>
</section>

<section class="container my-5">
  <h2 class="ar-titulo mb-4">Módulos del sistema</h2>
  <div class="row g-3">
    <?php foreach ($modulos as [$ico, $cls, $t, $d, $href]): ?>
      <div class="col-md-6 col-lg-4">
        <a class="ar-card" href="<?= url($href) ?>">
          <div class="ar-icono <?= $cls ?>"><i class="bi <?= $ico ?>"></i></div>
          <h3><?= $t ?></h3>
          <p class="ar-sub mb-2"><?= $d ?></p>
          <span class="fw-bold text-success">Ir al módulo <i class="bi bi-arrow-right"></i></span>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="container my-5">
  <div class="ar-panel">
    <div class="row g-4 align-items-center">
      <div class="col-md-8">
        <h2 class="ar-titulo">Comprometidos con los ODS</h2>
        <p class="ar-sub mb-0"><strong>ODS 2 · Hambre cero:</strong> acercamos alimento fresco a quien más lo necesita.<br>
        <strong>ODS 12 · Producción y consumo responsables:</strong> aprovechamos excedentes y reducimos la huella de carbono del traslado.</p>
      </div>
      <div class="col-md-4 text-md-end">
        <a href="<?= url('pages/auth/crear-cuenta.php') ?>" class="btn btn-ar btn-lg"><i class="bi bi-person-plus"></i> Crear cuenta</a>
      </div>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', () => AR.marcadores(AR.mapa('mapa', 9)));
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
