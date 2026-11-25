<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/Places.php';

$q = clean_str($_GET['q'] ?? '', 120);
$session = clean_str($_GET['session'] ?? '', 64);
if (mb_strlen($q) < 3 || $session === '') {
    json_out(['ok' => true, 'results' => []]);
}
json_out(places_autocomplete($q, $session));
