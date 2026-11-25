<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/PayPal.php';
require_once __DIR__ . '/../../app/Store.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}
$in = body_json();
$orderId = clean_str($in['order_id'] ?? '', 64);
$paypalId = clean_str($in['paypal_order_id'] ?? '', 64);
$order = $orderId !== '' ? order_get($orderId) : null;
if ($order === null || $paypalId === '') {
    json_out(['ok' => false, 'error' => 'bad_request'], 422);
}

$cap = paypal_capture_pack_order($paypalId);
if (!$cap['ok']) {
    json_out($cap, 402);
}
// Only the order this PayPal payment was created for can be marked paid.
if (($cap['customer_id'] ?? '') !== $orderId) {
    json_out(['ok' => false, 'error' => 'order_mismatch'], 409);
}
if (pack_mark_paid($orderId, $paypalId, 5, $cap['ship_name'], $cap['ship_address'])) {
    job_enqueue($orderId, 'tag_pack_buyer');
}
json_out(['ok' => true, 'message' => 'Thanks! Your 5 extra decals are on the way.']);
