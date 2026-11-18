<?php
/**
 * Configuración general de AgroRed Hidalgo.
 * Lee variables de entorno en Railway ($_ENV / $_SERVER / getenv) y usa valores locales de XAMPP como respaldo.
 */
$env = function (string $clave, $defecto = null) {
    if (isset($_ENV[$clave]) && $_ENV[$clave] !== '') return $_ENV[$clave];
    if (isset($_SERVER[$clave]) && $_SERVER[$clave] !== '') return $_SERVER[$clave];
    $val = getenv($clave);
    return ($val !== false && $val !== '') ? $val : $defecto;
};

$enRailway = $env('RAILWAY_ENVIRONMENT') !== null || $env('MYSQLHOST') !== null;

$config = [
    'db' => [
        'host'    => $env('MYSQLHOST', '127.0.0.1'),
        'port'    => (int)$env('MYSQLPORT', 3306),
        'name'    => $env('MYSQLDATABASE') ?? $env('MYSQL_DATABASE') ?? ($enRailway ? 'railway' : 'agrored'),
        'user'    => $env('MYSQLUSER', 'root'),
        'pass'    => $env('MYSQLPASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    // En Railway entra directo en la raíz (''); en XAMPP usa '/agrored-hidalgo'
    'base_url' => $env('BASE_URL') !== null
        ? $env('BASE_URL')
        : ($enRailway ? '' : '/agrored-hidalgo'),

    'app_nombre'   => 'AgroRed Hidalgo',
    'sesion_horas' => 8,
    'osrm_url'     => 'https://router.project-osrm.org',
    'modo_dev'     => !$enRailway,
    'punto_distribucion' => ['nombre' => 'Centro Comunitario Pachuca', 'lat' => 20.1011, 'lng' => -98.7591],

    'correo' => [
        'activo'        => $env('MAIL_ACTIVO') === 'true',
        'host'          => $env('MAIL_HOST', 'smtp.gmail.com'),
        'port'          => (int)$env('MAIL_PORT', 587),
        'seguridad'     => $env('MAIL_SEGURIDAD', 'tls'),
        'usuario'       => $env('MAIL_USER', 'tucorreo@gmail.com'),
        'clave'         => $env('MAIL_PASS', ''),
        'desde_correo'  => $env('MAIL_FROM', 'tucorreo@gmail.com'),
        'desde_nombre'  => 'AgroRed Hidalgo',
        'verificar_ssl' => true,
        'notificar'     => true,
        'copia_admin'   => '',
    ],
    'correo_entrada' => [
        'activo'        => false,
        'host'          => 'imap.gmail.com',
        'port'          => 993,
        'seguridad'     => 'ssl',
        'usuario'       => 'tucorreo@gmail.com',
        'clave'         => '',
        'carpeta'       => 'INBOX',
        'verificar_ssl' => true,
    ],
    'factor_co2'   => 0.25,
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}
return $config;