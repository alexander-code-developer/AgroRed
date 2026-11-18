<?php
/**
 * Configuración general de AgroRed Hidalgo.
 * Lee variables de entorno en Railway y usa valores locales de XAMPP como respaldo.
 */
$enRailway = getenv('RAILWAY_ENVIRONMENT') !== false || getenv('MYSQLHOST') !== false;

$config = [
    'db' => [
        'host'    => getenv('MYSQLHOST') ?: '127.0.0.1',
        'port'    => (int)(getenv('MYSQLPORT') ?: 3306),
        'name'    => getenv('MYSQLDATABASE') ?: 'agrored',
        'user'    => getenv('MYSQLUSER') ?: 'root',
        'pass'    => getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '',
        'charset' => 'utf8mb4',
    ],
    // En Railway entra directo en la raíz (''); en XAMPP usa '/agrored-hidalgo'
    'base_url' => getenv('BASE_URL') !== false
        ? getenv('BASE_URL')
        : ($enRailway ? '' : '/agrored-hidalgo'),

    'app_nombre'   => 'AgroRed Hidalgo',
    'sesion_horas' => 8,
    'osrm_url'     => 'https://router.project-osrm.org',
    'modo_dev'     => !$enRailway,
    'punto_distribucion' => ['nombre' => 'Centro Comunitario Pachuca', 'lat' => 20.1011, 'lng' => -98.7591],

    'correo' => [
        'activo'        => getenv('MAIL_ACTIVO') === 'true',
        'host'          => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port'          => (int)(getenv('MAIL_PORT') ?: 587),
        'seguridad'     => getenv('MAIL_SEGURIDAD') ?: 'tls',
        'usuario'       => getenv('MAIL_USER') ?: 'tucorreo@gmail.com',
        'clave'         => getenv('MAIL_PASS') ?: '',
        'desde_correo'  => getenv('MAIL_FROM') ?: 'tucorreo@gmail.com',
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