<?php
/* geocode.php: Endpoint para retorno de lat/lng via Nominatim */
require '../../../main.inc.php';
header('Content-Type: application/json');
if (empty(GETPOST('address'))) {
    http_response_code(400);
    print json_encode(['error' => 'Parameter address is missing']);
    exit;
}
$address = urlencode(GETPOST('address'));
$url = 'https://nominatim.openstreetmap.org/search?q='.$address.'&format=json&limit=1';
$response = dol_httpclientGET($url);
$data = json_decode($response, true);
if (empty($data)) {
    print json_encode(['error' => 'No result']);
} else {
    $lat = $data[0]['lat'];
    $lon = $data[0]['lon'];
    print json_encode(['latitude' => $lat, 'longitude' => $lon]);
} 