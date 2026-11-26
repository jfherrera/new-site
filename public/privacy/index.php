<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/views.php';

page_head('Privacy Policy | KISS Reviews', 'How KISS Reviews collects, uses, and protects your information.');
?>
<section class="legal container narrow">
  <p class="draft-note">DRAFT for review. Bracketed items need to be filled in before launch.</p>
  <h1>Privacy Policy</h1>
  <p>Last updated: [date]</p>

  <h2>Who we are</h2>
  <p>KISS Reviews ("we", "us") is operated by [legal entity name]. You can reach us at [contact email].</p>

  <h2>What we collect</h2>
  <ul>
    <li><strong>Business details:</strong> business name, address, phone number, and website. We get these from Google when you pick your business, and you can correct them.</li>
    <li><strong>Shipping details:</strong> the recipient name and mailing address for your decal.</li>
    <li><strong>Contact details:</strong> your email address, and whether you agreed to marketing emails.</li>
    <li><strong>Order details:</strong> which decals you ordered, and the payment record from PayPal. If you buy extra decals, PayPal sends us the shipping address you chose.</li>
  </ul>
  <p>We do not receive or store your card or bank details. PayPal handles payment.</p>

  <h2>How we use it</h2>
  <ul>
    <li>To send your free decal and any extra decals you order.</li>
    <li>To send your order confirmation and shipping updates.</li>
    <li>To send marketing emails, only if you agreed to them. We do not send SMS messages.</li>
  </ul>

  <h2>Who we share it with</h2>
  <p>We use these service providers to run the site. They process information only to provide their service to us:</p>
  <ul>
    <li><strong>Google</strong> for business search and map information, and for the fonts on our pages.</li>
    <li><strong>PayPal</strong> for payments.</li>
    <li><strong>systeme.io</strong> for email and contact management.</li>
    <li><strong>Cloudways</strong> for hosting.</li>
  </ul>
  <p>We do not sell your personal information.</p>

  <h2>Cookies</h2>
  <p>We do not use advertising or tracking cookies. Our site stores one sign-in cookie for our internal admin area. PayPal may set its own cookies when you pay.</p>

  <h2>How long we keep it</h2>
  <p>We keep order records for [period, e.g. 3 years] for shipping and bookkeeping. You can ask us to delete your information at any time, subject to any records we must keep by law.</p>

  <h2>Your choices</h2>
  <ul>
    <li>Unsubscribe from marketing emails at any time using the link in them.</li>
    <li>Ask us for a copy of your information, or ask us to correct or delete it, by emailing [contact email].</li>
  </ul>

  <h2>Security</h2>
  <p>The site uses HTTPS. Access to order information is limited to the people who need it to ship your decals.</p>

  <h2>Changes</h2>
  <p>If we change this policy, we will update the date above.</p>
</section>
<?php page_foot();
