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
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('<h3 style="font-family:sans-serif">Error real de conexión:</h3>'
           . '<pre style="background:#eee;padding:12px;border-radius:6px">'
           . htmlspecialchars("Host: {$c['host']} | Puerto: {$c['port']} | BD: {$c['name']} | Usuario: {$c['user']}\n")
           . htmlspecialchars("Mensaje: " . $e->getMessage())
           . '</pre>');
    }
    return $pdo;
}