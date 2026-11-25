<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/PayPal.php';
require_once __DIR__ . '/../../app/Store.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}
$in = body_json();
$orderId = clean_str($in['order_id'] ?? '', 64);
$order = $orderId !== '' ? order_get($orderId) : null;
if ($order === null) {
    json_out(['ok' => false, 'error' => 'order_not_found'], 404);
}
if ($order['pack_status'] === 'paid') {
    json_out(['ok' => false, 'error' => 'already_paid'], 409);
}
$res = paypal_create_pack_order($orderId, $order['business_name']);
if (!$res['ok']) {
    json_out($res, 502);
}
json_out(['ok' => true, 'id' => $res['paypal_order_id']]);
