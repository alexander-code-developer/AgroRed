<?php
/**
 * Configuración general de AgroRed Hidalgo.
 * Ajusta estos valores a tu entorno (XAMPP / WAMP / Laragon / hosting).
 */
$config = [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'agrored',
        'user'    => 'root',
        'pass'    => '',            // En XAMPP el root no trae contraseña
        'charset' => 'utf8mb4',
    ],
    // Carpeta del proyecto dentro del servidor. Si lo abres en http://localhost/agrored-hidalgo/
    // déjalo así. Si lo pones en la raíz del servidor, usa ''.
    'base_url' => '/agrored-hidalgo',

    'app_nombre'   => 'AgroRed Hidalgo',
    'sesion_horas' => 8,                        // ERS: sesión de 8 horas
    'osrm_url'     => 'https://router.project-osrm.org', // Servidor público de demostración
    // Para producción levanta tu propio OSRM y cambia a: 'http://localhost:5000'
    'modo_dev'     => true,                     // true = el enlace de recuperación se muestra en pantalla
    'punto_distribucion' => ['nombre' => 'Centro Comunitario Pachuca', 'lat' => 20.1011, 'lng' => -98.7591],

    // ---- Correo saliente (SMTP) ----
    // Gmail: host smtp.gmail.com · puerto 587 + 'tls' (o 465 + 'ssl') · usa una "Contraseña de aplicación"
    //        (requiere verificación en 2 pasos), NO tu contraseña normal.
    // Hosting (cPanel): host mail.tudominio.com · usuario = correo completo.
    'correo' => [
        'activo'        => false,                  // pon true cuando llenes los datos
        'host'          => 'smtp.gmail.com',
        'port'          => 587,
        'seguridad'     => 'tls',                  // 'tls' (587) | 'ssl' (465) | '' (sin cifrado, no recomendado)
        'usuario'       => 'tucorreo@gmail.com',
        'clave'         => '',                     // contraseña de aplicación
        'desde_correo'  => 'tucorreo@gmail.com',
        'desde_nombre'  => 'AgroRed Hidalgo',
        'verificar_ssl' => true,                   // si falla el certificado en XAMPP/Windows, ver guía (no desactivar en producción)
        'notificar'     => true,                   // avisos automáticos (pedidos, mensajes, bienvenida) cuando 'activo' = true
        'copia_admin'   => '',                     // opcional: correo que recibe copia de cada pedido nuevo
    ],
    // ---- Correo entrante (IMAP, opcional; requiere extensión php_imap) ----
    'correo_entrada' => [
        'activo'        => false,
        'host'          => 'imap.gmail.com',
        'port'          => 993,
        'seguridad'     => 'ssl',
        'usuario'       => 'tucorreo@gmail.com',
        'clave'         => '',                     // la misma contraseña de aplicación
        'carpeta'       => 'INBOX',
        'verificar_ssl' => true,
    ],
    'factor_co2'   => 0.25,                     // kg CO2 evitados por kg rescatado (estimado)
];

// Credenciales reales (correo, BD) van en config/config.local.php (NO se sube a GitHub).
// Ese archivo solo necesita los valores que quieras cambiar. Ver config.local.php.example
if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}
return $config;
