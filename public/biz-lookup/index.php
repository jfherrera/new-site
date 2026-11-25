<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/views.php';

page_head('Find Your Business | KISS Reviews', 'Find your business on Google to get your free review decal.');
?>
<section class="lookup-wrap">
  <div class="container lookup-grid">

    <div class="lookup-main">
      <div class="progress" aria-label="Step 2 of 3">
        <span class="progress-dot done">1</span>
        <span class="progress-line done"></span>
        <span class="progress-dot current">2</span>
        <span class="progress-line"></span>
        <span class="progress-dot">3</span>
        <span class="progress-label">Step 2 of 3</span>
      </div>

      <h1>Let's get your free decal to you</h1>
      <p class="lede">Search for your business. Google fills in the details, so there's nothing to type twice.</p>

      <form id="lookup-form" class="form-card" novalidate>
        <div class="field-group">
          <label for="search">Business name</label>
          <input id="search" type="search" autocomplete="off" placeholder="Start typing, e.g. Joe's Plumbing" aria-controls="suggestions" aria-expanded="false">
          <ul id="suggestions" class="suggestions" role="listbox" hidden></ul>
        </div>

        <div id="confirm" hidden>
          <div class="found-card">
            <div class="found-head">
              <span class="found-badge">&#10003; Found on Google</span>
              <span class="found-hint">Not right? Search again above.</span>
            </div>
            <label for="business_name">Business name</label>
            <input id="business_name" readonly>
            <label for="mailing_address">Mailing address for the decal</label>
            <input id="mailing_address" autocomplete="street-address">
            <div class="two-col">
              <div>
                <label for="phone">Business phone</label>
                <input id="phone" type="tel" autocomplete="tel">
              </div>
              <div>
                <label for="website">Website <span class="optional">(optional)</span></label>
                <input id="website" type="url" autocomplete="url">
              </div>
            </div>
          </div>

          <div class="form-card-inner">
            <h2>Who should we send it to?</h2>
            <label for="recipient_name">Recipient name</label>
            <input id="recipient_name" autocomplete="name" placeholder="The person who receives the mail" required>
            <label for="email">Email for your confirmation</label>
            <input id="email" type="email" autocomplete="email" placeholder="you@yourbusiness.com" required>
            <label class="check"><input id="email_consent" type="checkbox" checked> Email me about KISS Reviews and my free marketing analysis (you can unsubscribe anytime)</label>

            <p class="error" id="form-error" role="alert" hidden></p>
            <button class="btn btn-primary btn-lg btn-block" id="submit" type="submit">Send my free decal &rarr;</button>
            <p class="reassure">No payment needed for your free decal. We only use your details to ship it and send your confirmation.</p>
          </div>
        </div>
      </form>
    </div>

    <aside class="lookup-side">
      <div class="value-card">
        <h2>Your free decal includes</h2>
        <ul class="checks">
          <li>One 3.9 &times; 6.7 in decal, printed on sticker paper</li>
          <li>A QR code that opens your Google review page</li>
          <li>Free shipping, delivered in 1 to 2 weeks</li>
        </ul>
        <div class="upgrade-note">
          <strong>Want more?</strong> On the next page you can add 5 more decals for $10, plus $7.99 shipping and handling.
        </div>
      </div>
      <div class="value-card soft">
        <p class="stat-inline"><span class="stat">98%</span> of consumers consult reviews before choosing a local business.</p>
        <p class="source"><a href="https://go.gatherup.com/beyond-the-stars-2025" rel="noopener">GatherUp, 2025</a></p>
      </div>
    </aside>

  </div>
</section>
<?php page_foot();
