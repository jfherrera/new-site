<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/Places.php';
require_once __DIR__ . '/../../app/Store.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}
$in = body_json();
$placeId = clean_str($in['place_id'] ?? '', 200);
$email = clean_str($in['email'] ?? '', 254);
$recipient = clean_str($in['recipient_name'] ?? '', 120);
$consent = !empty($in['email_consent']);

if (!is_valid_email($email)) {
    json_out(['ok' => false, 'error' => 'invalid_email', 'message' => 'Please enter a valid email.'], 422);
}
if ($recipient === '') {
    json_out(['ok' => false, 'error' => 'missing_recipient', 'message' => 'Please enter a recipient name for shipping.'], 422);
}

// Re-fetch the place on the server so the stored business data comes from Google, not the browser.
$place = places_details($placeId);
if ($place === null) {
    json_out(['ok' => false, 'error' => 'place_not_found', 'message' => 'We could not find that business. Please search again.'], 422);
}

// Let the user correct the mailing address and phone; the business name stays as Google has it.
$address = clean_str($in['mailing_address'] ?? '', 300) ?: $place['address'];
$phone = clean_str($in['phone'] ?? '', 40) ?: $place['phone'];
$website = clean_str($in['website'] ?? '', 200) ?: $place['website'];

$orderId = bin2hex(random_bytes(12));
order_create([
    'id' => $orderId,
    'place_id' => $place['place_id'],
    'business_name' => $place['name'],
    'formatted_address' => $address,
    'phone' => $phone,
    'website' => $website,
    'review_url' => review_url($place['place_id']),
    'email' => $email,
    'recipient_name' => $recipient,
    'email_consent' => $consent,
]);
job_enqueue($orderId, 'sync_lead');

json_out(['ok' => true, 'order_id' => $orderId, 'redirect' => '/thank-you/?order=' . $orderId]);
