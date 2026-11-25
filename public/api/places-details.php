<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/Places.php';

$placeId = clean_str($_GET['place_id'] ?? '', 200);
$session = clean_str($_GET['session'] ?? '', 64);
$details = places_details($placeId, $session);
if ($details === null) {
    json_out(['ok' => false, 'error' => 'not_found'], 404);
}
json_out(['ok' => true] + $details);
