<?php
/* distance.php: Endpoint para cálculo de distância em km */
require __DIR__ . '/../../../../main.inc.php';
header('Content-Type: application/json');
$origLat = GETPOST('orig_lat','alpha');
$origLon = GETPOST('orig_lon','alpha');
$destLat = GETPOST('dest_lat','alpha');
$destLon = GETPOST('dest_lon','alpha');
if ($origLat === '' || $origLon === '' || $destLat === '' || $destLon === '') {
    http_response_code(400);
    print json_encode(['error' => 'Missing parameters']);
    exit;
}
$origLat = (float)$origLat;
$origLon = (float)$origLon;
$destLat = (float)$destLat;
$destLon = (float)$destLon;
$deg2rad = function($deg) { return $deg * pi() / 180; };
$dLat = $deg2rad($destLat - $origLat);
$dLon = $deg2rad($destLon - $origLon);
$a = sin($dLat/2) * sin($dLat/2)
   + cos($deg2rad($origLat)) * cos($deg2rad($destLat))
   * sin($dLon/2) * sin($dLon/2);
$c = 2 * atan2(sqrt($a), sqrt(1-$a));
$R = 6371; // raio da Terra em km
$distance_km = round($R * $c, 2);
print json_encode(['distance_km' => $distance_km]); 