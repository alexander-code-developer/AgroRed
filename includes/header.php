<?php
/**
 * Encabezado común. Variables opcionales antes del include:
 *   $titulo  -> título de la página
 *   $activo  -> clave del menú activo (inicio, huertos, compra, inventario, logistica, dashboard, perfil)
 *   $leaflet -> true para cargar Leaflet
 */
require_once __DIR__ . '/auth.php';
$titulo ??= 'Inicio'; $activo ??= ''; $leaflet ??= false;
$u = usuario();
$menu = [
    'inicio'     => ['index.php',                   'bi-house-door',   'Inicio'],
    'huertos'    => ['pages/huertos/registrar.php', 'bi-geo-alt',      'Registro'],
    'compra'     => ['pages/huertos/compra.php',    'bi-basket3',      'Compra'],
    'inventario' => ['pages/huertos/inventario.php','bi-clipboard2-data','Inventario'],
    'logistica'  => ['pages/logistica/rutas.php',   'bi-truck',        'Logística'],
    'dashboard'  => ['pages/dashboard.php',         'bi-bar-chart-line','Dashboard'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo) ?> · AgroRed Hidalgo</title>
  <link rel="icon" type="image/png" href="<?= url('assets/img/favicon.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <?php if ($leaflet): ?><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"><?php endif; ?>
  <link href="<?= url('assets/css/agrored.css') ?>" rel="stylesheet">
</head>
<body>
<a class="visually-hidden-focusable" href="#contenido">Saltar al contenido</a>

<header class="ar-header">
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('index.php') ?>">
        <img src="<?= url('assets/img/logo.png') ?>" alt="Logo AgroRed Hidalgo" class="ar-logo">
        <span class="lh-1">
          <span class="ar-marca">AgroRed Hidalgo</span>
          <small class="d-none d-md-block ar-lema">Conectando huertos, alimentando comunidades</small>
        </span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-label="Abrir menú">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menuPrincipal">
        <ul class="navbar-nav ms-lg-3 me-auto gap-lg-1">
          <?php foreach ($menu as $k => [$href, $ico, $txt]): ?>
            <li class="nav-item">
              <a class="nav-link ar-link <?= $activo === $k ? 'active' : '' ?>" href="<?= url($href) ?>">
                <i class="bi <?= $ico ?>"></i> <?= $txt ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <form class="ar-buscar d-flex my-2 my-lg-0 me-lg-3" action="<?= url('pages/huertos/compra.php') ?>" method="get" role="search">
          <input class="form-control" type="search" name="q" placeholder="Buscar huerto…" aria-label="Buscar huerto">
          <button class="btn" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
        <?php if ($u): ?>
          <div class="dropdown">
            <a class="btn btn-ar-claro dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i> <?= e(explode(' ', $u['nombre'])[0]) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= url('pages/perfil.php') ?>"><i class="bi bi-person"></i> Mi perfil</a></li>
              <?php if (es_rol('productor')): ?>
                <li><a class="dropdown-item" href="<?= url('pages/productor/pedidos.php') ?>"><i class="bi bi-telephone"></i> Pedidos</a></li>
                <li><a class="dropdown-item" href="<?= url('pages/productor/mensajes.php') ?>"><i class="bi bi-envelope"></i> Mensajes</a></li>
                <li><a class="dropdown-item" href="<?= url('pages/productor/finanzas.php') ?>"><i class="bi bi-wallet2"></i> Finanzas</a></li>
              <?php endif; ?>
              <?php if (es_rol('admin')): ?>
                <li><a class="dropdown-item" href="<?= url('pages/correo/bandeja.php') ?>"><i class="bi bi-envelope-paper"></i> Correo</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= url('pages/auth/logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-ar-claro" href="<?= url('pages/auth/login.php') ?>"><i class="bi bi-box-arrow-in-right"></i> Iniciar sesión</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>

<main id="contenido">
<?php if ($f = flashes()): ?>
  <div class="container mt-3">
    <?php foreach ($f as [$tipo, $msg]): ?>
      <div class="alert alert-<?= e($tipo) ?> alert-dismissible fade show" role="alert">
        <?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
