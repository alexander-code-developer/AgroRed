<?php
/**
 * Optimización de ruta con OSRM (servicio /trip = Problema del Agente Viajero).
 *   GET api/ruta.php?ids=1,2,3   (de 1 a 20 huertos; solo recolector / coordinador / admin)
 * El último punto de la ruta es siempre el punto de distribución configurado.
 * Si OSRM no responde en 3 s se devuelve un aviso claro (RNF: el resto de la app sigue funcionando).
 */
require_once __DIR__ . '/../includes/auth.php';
if (!es_rol('recolector', 'coordinador')) json_out(['error' => 'No tienes permiso para generar rutas.'], 403);

$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')))));
if (count($ids) < 1 || count($ids) > 20) json_out(['error' => 'Selecciona de 1 a 20 huertos.'], 400);

$in = implode(',', array_fill(0, count($ids), '?'));
$st = db()->prepare("SELECT h.id, h.nombre, h.latitud, h.longitud, c.nombre colonia,
        (SELECT COALESCE(SUM(volumen_kg),0) FROM inventario WHERE huerto_id=h.id) kg
        FROM huertos h JOIN colonias c ON c.id=h.colonia_id WHERE h.id IN ($in)");
$st->execute($ids); $huertos = $st->fetchAll();
if (count($huertos) !== count($ids)) json_out(['error' => 'Alguno de los huertos no existe.'], 404);

$dist = cfg('punto_distribucion');
$puntos = $huertos;
$puntos[] = ['id' => 0, 'nombre' => $dist['nombre'], 'latitud' => $dist['lat'], 'longitud' => $dist['lng'], 'colonia' => '', 'kg' => 0];
$coords = implode(';', array_map(fn($p) => $p['longitud'] . ',' . $p['latitud'], $puntos));

$url = rtrim(cfg('osrm_url'), '/') . "/trip/v1/driving/$coords?overview=full&geometries=geojson&roundtrip=false&source=first&destination=last";
$ctx = stream_context_create(['http' => ['timeout' => 3, 'header' => "User-Agent: AgroRedHidalgo-ITSOEH\r\n", 'ignore_errors' => true]]);
$raw = @file_get_contents($url, false, $ctx);
$d = $raw ? json_decode($raw, true) : null;
if (!$d || ($d['code'] ?? '') !== 'Ok') {
    json_out(['error' => 'El servicio de ruteo (OSRM) no está disponible en este momento. El resto de la aplicación sigue funcionando; intenta de nuevo en unos minutos.'], 503);
}
$trip = $d['trips'][0];
$orden = [];
foreach ($d['waypoints'] as $i => $w) {
    $orden[$w['waypoint_index']] = ['id' => (int)$puntos[$i]['id'], 'nombre' => $puntos[$i]['nombre'], 'colonia' => $puntos[$i]['colonia'],
        'kg' => (float)$puntos[$i]['kg'], 'lat' => (float)$puntos[$i]['latitud'], 'lng' => (float)$puntos[$i]['longitud']];
}
ksort($orden);
json_out([
    'distancia_km' => round($trip['distance'] / 1000, 1),
    'duracion_min' => (int)round($trip['duration'] / 60),
    'geometria'    => $trip['geometry'],
    'orden'        => array_values($orden),
    'kg_total'     => array_sum(array_column($huertos, 'kg')),
]);
