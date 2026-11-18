<?php
/** Conexión PDO única (patrón singleton simple). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = (require __DIR__ . '/config.php')['db'];
    $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // consultas preparadas reales (anti SQL injection)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('<h3 style="font-family:sans-serif">No se pudo conectar a la base de datos.</h3>'
           . '<p style="font-family:sans-serif">Revisa <code>config/config.php</code> e importa <code>database/schema.sql</code>.</p>');
    }
    return $pdo;
}
