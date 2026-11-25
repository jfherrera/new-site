(function () {
  'use strict';

  function $(id) { return document.getElementById(id); }
  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body || {}),
    }).then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); });
  }
  function newSession() {
    return (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2);
  }

  // ---------- Step 2: business lookup ----------
  var search = $('search');
  if (search) {
    var suggestions = $('suggestions');
    var session = newSession();
    var timer = null;
    var placeId = null;

    function clearSuggestions() {
      suggestions.innerHTML = '';
      suggestions.hidden = true;
      search.setAttribute('aria-expanded', 'false');
    }

    search.addEventListener('input', function () {
      clearTimeout(timer);
      var q = search.value.trim();
      if (q.length < 3) { clearSuggestions(); return; }
      timer = setTimeout(function () {
        fetch('/api/places-autocomplete.php?q=' + encodeURIComponent(q) + '&session=' + encodeURIComponent(session))
          .then(function (r) { return r.json(); })
          .then(function (d) {
            suggestions.innerHTML = '';
            (d.results || []).forEach(function (s) {
              var li = document.createElement('li');
              li.setAttribute('role', 'option');
              li.tabIndex = 0;
              li.innerHTML = '<strong></strong> <span></span>';
              li.querySelector('strong').textContent = s.name;
              li.querySelector('span').textContent = s.secondary;
              li.addEventListener('click', function () { pick(s.place_id); });
              li.addEventListener('keydown', function (e) { if (e.key === 'Enter') pick(s.place_id); });
              suggestions.appendChild(li);
            });
            suggestions.hidden = suggestions.children.length === 0;
            search.setAttribute('aria-expanded', String(!suggestions.hidden));
          })
          .catch(clearSuggestions);
      }, 300);
    });

    function pick(id) {
      clearSuggestions();
      fetch('/api/places-details.php?place_id=' + encodeURIComponent(id) + '&session=' + encodeURIComponent(session))
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.ok) { throw new Error('not_found'); }
          placeId = d.place_id;
          $('business_name').value = d.name || '';
          $('mailing_address').value = d.address || '';
          $('phone').value = d.phone || '';
          $('website').value = d.website || '';
          $('confirm').hidden = false;
          session = newSession(); // a details call closes the autocomplete session
          $('recipient_name').focus();
        })
        .catch(function () { alert('We could not load that business. Please search again.'); });
    }

    $('lookup-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var err = $('form-error');
      err.hidden = true;
      if (!placeId) { return; }
      var btn = $('submit');
      btn.disabled = true;
      postJson('/api/lead.php', {
        place_id: placeId,
        mailing_address: $('mailing_address').value,
        phone: $('phone').value,
        website: $('website').value,
        recipient_name: $('recipient_name').value,
        email: $('email').value,
        email_consent: $('email_consent').checked,
      }).then(function (res) {
        if (res.data.ok) { window.location.href = res.data.redirect; return; }
        err.textContent = res.data.message || 'Something went wrong. Please try again.';
        err.hidden = false;
        btn.disabled = false;
      }).catch(function () {
        err.textContent = 'Network error. Please try again.';
        err.hidden = false;
        btn.disabled = false;
      });
    });
  }

  // ---------- Step 3: thank-you and PayPal pack upsell ----------
  // The free marketing analysis is revealed only after the upsell is bought or declined.
  function revealAnalysis() {
    var slot = $('analysis-slot');
    if (slot) { slot.hidden = false; slot.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
  }
  var decline = $('decline-upsell');
  if (decline) {
    decline.addEventListener('click', function () {
      decline.hidden = true;
      revealAnalysis();
    });
  }

  var paypalTarget = $('paypal-buttons');
  if (paypalTarget && window.KISS && window.KISS.paypalClientId && !window.KISS.paid) {
    var host = window.KISS.paypalEnv === 'sandbox' ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';
    var sdk = document.createElement('script');
    sdk.src = host + '/sdk/js?client-id=' + encodeURIComponent(window.KISS.paypalClientId) + '&currency=USD&intent=capture';
    sdk.onload = function () {
      window.paypal.Buttons({
        createOrder: function () {
          return postJson('/api/paypal-create.php', { order_id: window.KISS.orderId })
            .then(function (res) {
              if (!res.data.ok) {
                console.error('paypal-create response:', JSON.stringify(res.data));
                throw new Error(res.data.error || 'create_failed');
              }
              return res.data.id;
            });
        },
        onApprove: function (data) {
          return postJson('/api/paypal-capture.php', { order_id: window.KISS.orderId, paypal_order_id: data.orderID })
            .then(function (res) {
              if (res.data.ok) {
                paypalTarget.innerHTML = '<p class="success">' + res.data.message + '</p>';
                if (decline) { decline.hidden = true; }
                revealAnalysis();
              } else {
                console.error('paypal-capture response:', JSON.stringify(res.data));
                throw new Error(res.data.error || 'capture_failed');
              }
            });
        },
        onError: function () {
          var p = $('pay-error');
          p.textContent = 'Payment could not be completed. You have not been charged. Please try again.';
          p.hidden = false;
        },
      }).render('#paypal-buttons');
    };
    document.head.appendChild(sdk);
  }
})();
