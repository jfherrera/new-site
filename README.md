# KISS Reviews funnel

Three-step funnel for a free Google review decal, with a 5-pack upsell.

| Step | URL | What it does |
|---|---|---|
| 1 | `/` | Landing page |
| 2 | `/biz-lookup/` | Google Places autocomplete, confirm details, shipping and contact |
| 3 | `/thank-you/?order=…` | Thank-you, PayPal 5-pack upsell ($10 + S&H), free marketing analysis CTA (booklikeaboss.com) |

Old URLs `/biz-infomx-1` and `/mx-thank-you` return 301 to `/biz-lookup/`.

## Layout

- `public/` is the web root. Each page is a folder with `index.php`, so the URLs work without rewrite rules.
- `public/api/` holds the JSON endpoints.
- `app/` holds the Places, PayPal, Systeme.io, and storage code. `app/bootstrap.php` creates the SQLite schema on first use.
- `tools/sync_systeme.php` is the cron worker that sends contacts and tags to systeme.io.
- `tools/decal/` builds the print file for a business from the design PSD.

## Cloudways (Lightning stack, PHP 8.x)

1. Create a PHP app. Set the document root to `public/`.
2. Enable the `curl` and `pdo_sqlite` PHP extensions (usually on by default).
3. Create `secrets.env` in the app folder above `public_html` (for example `/home/master/applications/<app>/secrets.env`), using `.env.example` as the template. Cloudways has no environment-variable screen for plain PHP apps, so this file is how the keys are loaded. It's outside the web root and outside git.
4. Set `DB_PATH` to a folder outside `public/`. The app creates the folder.
5. Add a cron job, every minute: `php /home/master/applications/<app>/public_html/tools/sync_systeme.php`
   (adjust to the app's path).
6. Test each step on a Cloudways staging clone before pointing `go.kissreviews.com` at it.

Check on staging that `/biz-lookup` (no trailing slash) redirects to `/biz-lookup/`. This relies on Lightning's default `index.php` handling, which should be confirmed there.

## Services to set up

**Google Places (New).** In the KissReviews project, enable *Places API (New)* and create an API key. Restrict it to that API and to the server's IP. Set `GOOGLE_PLACES_API_KEY`.

**PayPal.** Create a REST app in the PayPal Developer dashboard. Test with `PAYPAL_ENV=sandbox` and sandbox credentials first, then switch to `live`. Set `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET`, `PACK_PRICE_USD=10.00`, and `PACK_SHIPPING_USD=7.99` (shipping and handling).

**systeme.io (free plan, 30 requests/minute).** Create these custom fields (slugs must match exactly): `business_name`, `business_address`, `business_phone`, `business_website`, `google_review_url`, `place_id`, `kiss_order_id`. Create the tags `kiss-free-decal` and `kiss-pack-buyer`, or let the worker create them. Set `SYSTEME_API_KEY`.
The endpoint paths in `app/Systeme.php` follow systeme.io's public API. Check them against the API docs in your account on the first run. If a job fails, `sync_jobs.last_error` in the database shows the response.

## Decal

```bash
pip install psd-tools[composite] qrcode pillow
DECAL_PSD=/path/to/design.psd python3 tools/decal/build_decal.py \
  "https://search.google.com/local/writereview?placeid=PLACE_ID" out/business-name
```

This writes `out/business-name.png` (1170 × 2010 px, 3.9 × 6.7 in at 300 dpi) and `.pdf`. The QR is generated per business with error correction H. The design PSD isn't stored in the repo.

`public/assets/decal-sample.png` is the landing page sample. Its QR code points to a placeholder place, so it won't scan to a real review page. Replace it with a real sample before launch.

## Open items before launch

- Privacy policy and terms pages. The funnel collects emails, phone numbers, and addresses.
- Confirm the booklikeaboss.com booking URL (the CTA currently links to the root).
- Replace the sample decal QR (see above).
- PayPal webhook for payments not captured by the thank-you page (optional hardening).
