<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/views.php';

page_head('Find Your Business | KISS Reviews', 'Find your business on Google to get your free review decal.');
?>
<section class="lookup">
  <h1>Find your business</h1>
  <p class="lede">Start typing your business name and pick it from the list.</p>

  <form id="lookup-form" novalidate>
    <label for="search">Business name</label>
    <input id="search" type="search" autocomplete="off" placeholder="e.g. Joe's Plumbing Torrance" aria-controls="suggestions" aria-expanded="false">
    <ul id="suggestions" class="suggestions" role="listbox" hidden></ul>

    <div id="confirm" hidden>
      <h2>Confirm your details</h2>
      <label for="business_name">Business name</label>
      <input id="business_name" readonly>
      <label for="mailing_address">Mailing address for the decal</label>
      <input id="mailing_address" autocomplete="street-address">
      <label for="phone">Business phone</label>
      <input id="phone" type="tel" autocomplete="tel">
      <label for="website">Website (optional)</label>
      <input id="website" type="url" autocomplete="url">

      <h2>Where should we send it?</h2>
      <label for="recipient_name">Recipient name</label>
      <input id="recipient_name" autocomplete="name" required>
      <label for="email">Email</label>
      <input id="email" type="email" autocomplete="email" required>
      <label class="check"><input id="email_consent" type="checkbox"> Email me about KISS Reviews and my free marketing analysis (optional)</label>

      <p class="error" id="form-error" role="alert" hidden></p>
      <button class="btn btn-primary" id="submit" type="submit">Send my free decal</button>
    </div>
  </form>
</section>
<?php page_foot();
