<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * PayPal REST v2 (Orders API). Set PAYPAL_ENV=sandbox while testing.
 */
function paypal_base(): string
{
    return env('PAYPAL_ENV', 'live') === 'sandbox'
        ? 'https://api-m.sandbox.paypal.com'
        : 'https://api-m.paypal.com';
}

function paypal_token(): ?string
{
    $id = env('PAYPAL_CLIENT_ID');
    $secret = env('PAYPAL_SECRET');
    if ($id === null || $secret === null) {
        $GLOBALS['paypal_diag'] = ['stage' => 'auth', 'reason' => 'credentials_missing', 'env' => env('PAYPAL_ENV', 'live')];
        return null;
    }
    $ch = curl_init(paypal_base() . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => $id . ':' . $secret,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $data = json_decode((string) $raw, true) ?: [];
    if ($status === 200 && !empty($data['access_token'])) {
        return $data['access_token'];
    }
    // Safe to show: PayPal's error name and description. No secrets.
    $GLOBALS['paypal_diag'] = [
        'stage' => 'auth',
        'status' => $status,
        'error' => $data['error'] ?? null,
        'error_description' => $data['error_description'] ?? null,
        'env' => env('PAYPAL_ENV', 'live'),
    ];
    return null;
}

function paypal_call(string $method, string $path, ?array $body, string $token, ?string $requestId = null): array
{
    $headers = ['Authorization: Bearer ' . $token];
    if ($requestId !== null) {
        $headers[] = 'PayPal-Request-Id: ' . $requestId;
    }
    return http_json($method, paypal_base() . $path, $headers, $body);
}

/**
 * Creates the 5-pack order: $10 pack price plus shipping and handling.
 * custom_id carries our internal order id so the webhook/capture can match it back.
 */
function paypal_create_pack_order(string $orderId, string $businessName): array
{
    $token = paypal_token();
    if ($token === null) {
        return ['ok' => false, 'error' => 'paypal_auth_failed', 'detail' => $GLOBALS['paypal_diag'] ?? []];
    }
    $pack = number_format((float) env('PACK_PRICE_USD', '10.00'), 2, '.', '');
    $ship = number_format((float) env('PACK_SHIPPING_USD', '7.99'), 2, '.', '');
    $total = number_format((float) $pack + (float) $ship, 2, '.', '');

    $res = paypal_call('POST', '/v2/checkout/orders', [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $orderId,
            'custom_id' => $orderId,
            'description' => 'KISS Reviews: 5 additional Google review decals',
            'amount' => [
                'currency_code' => 'USD',
                'value' => $total,
                'breakdown' => [
                    'item_total' => ['currency_code' => 'USD', 'value' => $pack],
                    'shipping' => ['currency_code' => 'USD', 'value' => $ship],
                ],
            ],
            'items' => [[
                'name' => '5 Google review decals',
                'quantity' => '1',
                'unit_amount' => ['currency_code' => 'USD', 'value' => $pack],
                'category' => 'PHYSICAL_GOODS',
            ]],
            'soft_descriptor' => 'KISSREVIEWS',
        ]],
        'payment_source' => [
            'paypal' => [
                'experience_context' => [
                    'shipping_preference' => 'GET_FROM_FILE',
                    'user_action' => 'PAY_NOW',
                    'brand_name' => 'KISS Reviews',
                ],
            ],
        ],
    ], $token, $orderId . '-create');

    if (!in_array($res['_status'] ?? 0, [200, 201], true) || empty($res['id'])) {
        return [
            'ok' => false,
            'error' => 'paypal_create_failed',
            'detail' => [
                'stage' => 'create',
                'status' => $res['_status'] ?? 0,
                'name' => $res['name'] ?? null,
                'message' => $res['message'] ?? null,
                'issue' => $res['details'][0]['issue'] ?? null,
                'description' => $res['details'][0]['description'] ?? null,
                'debug_id' => $res['debug_id'] ?? null,
                'env' => env('PAYPAL_ENV', 'live'),
                // Preview only, for diagnosis. The create response holds no secrets.
                'preview' => mb_substr((string) json_encode($res, JSON_UNESCAPED_SLASHES), 0, 400),
            ],
        ];
    }
    return ['ok' => true, 'paypal_order_id' => $res['id']];
}

/** Captures an approved order and returns the verified capture details. */
function paypal_capture_pack_order(string $paypalOrderId): array
{
    $token = paypal_token();
    if ($token === null) {
        return ['ok' => false, 'error' => 'payments_not_configured'];
    }
    $res = paypal_call('POST', '/v2/checkout/orders/' . rawurlencode($paypalOrderId) . '/capture', [], $token, $paypalOrderId . '-capture');
    $unit = $res['purchase_units'][0] ?? [];
    $capture = $unit['payments']['captures'][0] ?? [];
    $ok = in_array($res['_status'] ?? 0, [200, 201], true) && ($capture['status'] ?? '') === 'COMPLETED';
    if (!$ok) {
        return ['ok' => false, 'error' => 'capture_not_completed'];
    }
    $expected = number_format((float) env('PACK_PRICE_USD', '10.00') + (float) env('PACK_SHIPPING_USD', '7.99'), 2, '.', '');
    if (($capture['amount']['value'] ?? '') !== $expected || ($capture['amount']['currency_code'] ?? '') !== 'USD') {
        return ['ok' => false, 'error' => 'amount_mismatch'];
    }
    $ship = $res['purchase_units'][0]['shipping'] ?? [];
    $addr = $ship['address'] ?? [];
    return [
        'ok' => true,
        'customer_id' => $unit['reference_id'] ?? '',
        'amount' => $capture['amount']['value'] ?? '',
        'ship_name' => $ship['name']['full_name'] ?? '',
        'ship_address' => implode(', ', array_filter([
            $addr['address_line_1'] ?? '',
            $addr['address_line_2'] ?? '',
            $addr['admin_area_2'] ?? '',
            $addr['admin_area_1'] ?? '',
            $addr['postal_code'] ?? '',
            $addr['country_code'] ?? '',
        ])),
    ];
}
