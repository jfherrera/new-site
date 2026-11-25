<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function order_get(string $id): ?array
{
    $st = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

function order_create(array $o): void
{
    $st = db()->prepare('INSERT INTO orders
        (id, created_at, place_id, business_name, formatted_address, phone, website, review_url, email, recipient_name, email_consent)
        VALUES (:id, :created_at, :place_id, :business_name, :formatted_address, :phone, :website, :review_url, :email, :recipient_name, :email_consent)');
    $st->execute([
        ':id' => $o['id'],
        ':created_at' => gmdate('c'),
        ':place_id' => $o['place_id'],
        ':business_name' => $o['business_name'],
        ':formatted_address' => $o['formatted_address'],
        ':phone' => $o['phone'],
        ':website' => $o['website'],
        ':review_url' => $o['review_url'],
        ':email' => $o['email'],
        ':recipient_name' => $o['recipient_name'],
        ':email_consent' => $o['email_consent'] ? 1 : 0,
    ]);
}

function job_enqueue(string $orderId, string $action): void
{
    $st = db()->prepare('INSERT INTO sync_jobs (order_id, action, created_at) VALUES (?, ?, ?)');
    $st->execute([$orderId, $action, gmdate('c')]);
}

function pack_mark_paid(string $orderId, string $paypalOrderId, int $qty, string $shipName, string $shipAddress): bool
{
    // Only transitions once, so a repeated capture call can't double-count.
    $st = db()->prepare("UPDATE orders SET pack_status = 'paid', pack_qty = ?, paypal_order_id = ?,
        pack_paid_at = ?, pack_ship_name = ?, pack_ship_address = ?
        WHERE id = ? AND pack_status != 'paid'");
    $st->execute([$qty, $paypalOrderId, gmdate('c'), $shipName, $shipAddress, $orderId]);
    return $st->rowCount() === 1;
}
