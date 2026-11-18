<?php
/**
 * API JSON de huertos.
 * Privacidad (ERS): sin sesión solo se entrega ubicación aproximada (2 decimales) y sin teléfono.
 *   GET api/huertos.php               -> todos
 *   GET api/huertos.php?q=texto       -> búsqueda por nombre
 *   GET api/huertos.php?id=3          -> uno
 *   GET api/huertos.php?lat=..&lng=..&km=10 -> cercanos (fórmula Haversine)
 */
require_once __DIR__ . '/../includes/auth.php';
$log = logueado();
$q   = trim($_GET['q'] ?? '');
$id  = (int)($_GET['id'] ?? 0);

$sql = "SELECT h.id, h.nombre, c.nombre AS colonia, m.nombre AS municipio, h.facebook,
               h.latitud, h.longitud, h.telefono,
               COALESCE((SELECT ROUND(AVG(estrellas),1) FROM resenas WHERE huerto_id=h.id),0) AS promedio,
               (SELECT COUNT(*) FROM resenas WHERE huerto_id=h.id) AS total_resenas,
               (SELECT COUNT(*) FROM resenas WHERE huerto_id=h.id AND recomienda=1) AS recomendaciones,
               (SELECT GROUP_CONCAT(DISTINCT cu.nombre ORDER BY cu.nombre SEPARATOR ', ')
                  FROM inventario i JOIN cultivos cu ON cu.id=i.cultivo_id WHERE i.huerto_id=h.id) AS cultivos
        FROM huertos h
        JOIN colonias c ON c.id=h.colonia_id
        JOIN municipios m ON m.id=c.municipio_id
        WHERE 1=1";
$par = [];
if ($id)        { $sql .= ' AND h.id = ?';           $par[] = $id; }
if ($q !== '')  { $sql .= ' AND h.nombre LIKE ?';    $par[] = '%' . mb_substr($q, 0, 60) . '%'; }
$sql .= ' ORDER BY h.nombre LIMIT 300';

$st = db()->prepare($sql); $st->execute($par);
$filas = $st->fetchAll();

$lat0 = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng0 = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
$km   = min((float)($_GET['km'] ?? 10), 100);

$out = [];
foreach ($filas as $f) {
    $lat = (float)$f['latitud']; $lng = (float)$f['longitud'];
    if ($lat0 !== null && $lng0 !== null) {
        $d = 6371 * acos(min(1, cos(deg2rad($lat0)) * cos(deg2rad($lat)) * cos(deg2rad($lng) - deg2rad($lng0))
                              + sin(deg2rad($lat0)) * sin(deg2rad($lat))));
        if ($d > $km) continue;
        $f['distancia_km'] = round($d, 2);
    }
    $f['aproximada'] = !$log;
    $f['lat'] = $log ? $lat : round($lat, 2);
    $f['lng'] = $log ? $lng : round($lng, 2);
    if (!$log) $f['telefono'] = null;
    $f['promedio'] = (float)$f['promedio'];
    $f['total_resenas'] = (int)$f['total_resenas'];
    $f['recomendaciones'] = (int)$f['recomendaciones'];
    unset($f['latitud'], $f['longitud']);
    $out[] = $f;
}
if ($lat0 !== null) usort($out, fn($a, $b) => $a['distancia_km'] <=> $b['distancia_km']);
if ($id) { $out ? json_out($out[0]) : json_out(['error' => 'Huerto no encontrado.'], 404); }
json_out($out);
