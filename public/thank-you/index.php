<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/views.php';
require_once __DIR__ . '/../../app/Store.php';

$orderId = clean_str($_GET['order'] ?? '', 64);
$order = $orderId !== '' ? order_get($orderId) : null;
if ($order === null) {
    redirect_to('/biz-lookup/', 302);
}
$name = h($order['business_name']);
$oid = h($order['id']);
$paid = $order['pack_status'] === 'paid';

page_head('Thank You | KISS Reviews', 'Your free decal is on its way.');
?>
<section class="thanks">
  <h1>Thank you, <?= $name ?>!</h1>
  <p class="lede">Your free decal is on its way. Please allow 1 to 2 weeks for delivery.</p>
</section>

<section class="upsell card">
  <h2>Add 5 more decals</h2>
  <p>Put a decal at every counter, door, and register. Five more decals for <strong>$10</strong> plus shipping and handling.</p>
  <?php if ($paid): ?>
    <p class="success">Your 5 extra decals are on the way. Thank you!</p>
  <?php else: ?>
    <div id="paypal-buttons" data-order="<?= $oid ?>"></div>
    <p class="error" id="pay-error" role="alert" hidden></p>
    <p class="fine">Payment is processed securely by PayPal.</p>
  <?php endif; ?>
</section>

<section class="analysis card">
  <h2>Get a free marketing analysis</h2>
  <p>We'll perform a free marketing analysis of your business and have it ready to review with you. Book a time that works for you.</p>
  <a class="btn btn-secondary" href="https://booklikeaboss.com" rel="noopener">Book your review call</a>
</section>
<?php
echo '<script>window.KISS = ' . json_encode([
    'paypalClientId' => env('PAYPAL_CLIENT_ID'),
    'paypalEnv' => env('PAYPAL_ENV', 'live'),
    'orderId' => $order['id'],
    'paid' => $paid,
]) . ';</script>';
page_foot();
