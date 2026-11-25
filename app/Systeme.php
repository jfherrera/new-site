<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * systeme.io API wrapper. The free plan allows 30 requests per minute, so
 * writes are queued in sync_jobs and drained by tools/sync_systeme.php (cron).
 * Endpoint paths should be confirmed against systeme.io's API docs in your account
 * on first run; the first failed job shows the response in sync_jobs.last_error.
 */
function systeme_call(string $method, string $path, ?array $body = null): array
{
    $key = env('SYSTEME_API_KEY');
    if ($key === null) {
        return ['_status' => 0, '_error' => 'no_api_key'];
    }
    // Free plan: 30 requests/minute. Keep at least 2.1 s between calls.
    static $last = 0.0;
    $wait = 2.1 - (microtime(true) - $last);
    if ($wait > 0) {
        usleep((int) ($wait * 1000000));
    }
    $last = microtime(true);
    return http_json($method, 'https://api.systeme.io/api/' . ltrim($path, '/'), [
        'X-API-Key: ' . $key,
        'Accept: application/json',
    ], $body);
}

function systeme_tag_id(string $name): ?int
{
    static $cache = [];
    if (isset($cache[$name])) {
        return $cache[$name];
    }
    $res = systeme_call('GET', '/tags?limit=100');
    foreach ($res['items'] ?? [] as $tag) {
        if (($tag['name'] ?? '') === $name) {
            return $cache[$name] = (int) $tag['id'];
        }
    }
    $created = systeme_call('POST', '/tags', ['name' => $name]);
    return $cache[$name] = isset($created['id']) ? (int) $created['id'] : null;
}

/** Creates or updates the contact with the order's details. Returns the contact id. */
function systeme_upsert_contact(array $order): ?int
{
    $fields = [
        ['slug' => 'business_name', 'value' => $order['business_name']],
        ['slug' => 'business_address', 'value' => $order['formatted_address'] ?? ''],
        ['slug' => 'business_phone', 'value' => $order['phone'] ?? ''],
        ['slug' => 'business_website', 'value' => $order['website'] ?? ''],
        ['slug' => 'google_review_url', 'value' => $order['review_url']],
        ['slug' => 'place_id', 'value' => $order['place_id']],
        ['slug' => 'kiss_order_id', 'value' => $order['id']],
    ];
    $res = systeme_call('POST', '/contacts', [
        'email' => $order['email'],
        'fields' => $fields,
    ]);
    if (isset($res['id'])) {
        return (int) $res['id'];
    }
    // Contact already exists: look it up by email and update.
    $found = systeme_call('GET', '/contacts?email=' . rawurlencode($order['email']));
    $id = $found['items'][0]['id'] ?? null;
    if ($id === null) {
        return null;
    }
    systeme_call('PATCH', '/contacts/' . (int) $id, ['fields' => $fields]);
    return (int) $id;
}

function systeme_add_tag(int $contactId, string $tagName): bool
{
    $tagId = systeme_tag_id($tagName);
    if ($tagId === null) {
        return false;
    }
    $res = systeme_call('POST', '/contacts/' . $contactId . '/tags', ['tagId' => $tagId]);
    return in_array($res['_status'] ?? 0, [200, 201, 204], true);
}
