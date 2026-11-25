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
$packPrice = number_format((float) env('PACK_PRICE_USD', '10.00'), 2);
$packShip = number_format((float) env('PACK_SHIPPING_USD', '7.99'), 2);
$packTotal = number_format((float) env('PACK_PRICE_USD', '10.00') + (float) env('PACK_SHIPPING_USD', '7.99'), 2);

page_head('Thank You | KISS Reviews', 'Your free decal is on its way.');
?>
<section class="thanks-wrap">
  <div class="container">

    <div class="progress" aria-label="Step 3 of 3">
      <span class="progress-dot done">&#10003;</span>
      <span class="progress-line done"></span>
      <span class="progress-dot done">&#10003;</span>
      <span class="progress-line done"></span>
      <span class="progress-dot current">3</span>
      <span class="progress-label">Step 3 of 3</span>
    </div>

    <div class="thanks-hero">
      <div class="check-circle" aria-hidden="true">&#10003;</div>
      <h1>You're all set, <?= $name ?>!</h1>
      <p class="lede">Your free decal is on its way. Allow 1 to 2 weeks for delivery. Put it where customers can scan it, like the register or the front door.</p>
    </div>

    <div class="thanks-grid">

      <div class="upsell-card">
        <span class="badge">Optional upgrade</span>
        <h2>Add 5 more decals</h2>
        <p class="upsell-lede">One at every counter, door, and register. Same design, same QR code.</p>

        <div class="price-stack">
          <div><span>5 extra decals</span><span>$<?= $packPrice ?></span></div>
          <div><span>Shipping &amp; handling</span><span>$<?= $packShip ?></span></div>
          <div class="total"><span>Total</span><span>$<?= $packTotal ?></span></div>
        </div>

        <?php if ($paid): ?>
          <p class="success big">Thank you! Your 5 extra decals are on the way.</p>
        <?php else: ?>
          <div id="paypal-buttons" data-order="<?= $oid ?>"></div>
          <p class="error" id="pay-error" role="alert" hidden></p>
          <p class="fine">Secure checkout by PayPal. You can pay with PayPal or a card.</p>
          <button type="button" class="link-btn" id="decline-upsell">No thanks, show me the next step</button>
        <?php endif; ?>
      </div>

      <div class="analysis-card" id="analysis-slot" <?= $paid ? '' : 'hidden' ?>>
        <span class="badge badge-soft">Free for you</span>
        <h2>Get a free marketing analysis</h2>
        <p>We'll perform a free marketing analysis of your business and have it ready to review with you. Book a time that works for you.</p>
        <a class="btn btn-secondary btn-lg btn-block" href="https://booklikeaboss.com" rel="noopener">Book your review call</a>
      </div>

    </div>
  </div>
</section>
<?php
echo '<script>window.KISS = ' . json_encode([
    'paypalClientId' => env('PAYPAL_CLIENT_ID'),
    'paypalEnv' => env('PAYPAL_ENV', 'live'),
    'orderId' => $order['id'],
    'paid' => $paid,
]) . ';</script>';
page_foot();
