<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * Google Places API (New). The key lives only in the server environment.
 * Field masks keep billing to the fields we use.
 */
function places_key(): string
{
    $key = env('GOOGLE_PLACES_API_KEY');
    if ($key === null) {
        json_out(['error' => 'server_not_configured'], 500);
    }
    return $key;
}

function places_autocomplete(string $query, string $sessionToken): array
{
    $res = http_json('POST', 'https://places.googleapis.com/v1/places:autocomplete', [
        'X-Goog-Api-Key: ' . places_key(),
        'X-Goog-FieldMask: suggestions.placePrediction.placeId,suggestions.placePrediction.text.text,suggestions.placePrediction.structuredFormat.secondaryText.text',
    ], [
        'input' => $query,
        'sessionToken' => $sessionToken,
        'includedRegionCodes' => ['us'],
        'languageCode' => 'en',
    ]);

    if (($res['_status'] ?? 0) !== 200) {
        return ['ok' => false, 'error' => 'lookup_unavailable'];
    }

    $out = [];
    foreach ($res['suggestions'] ?? [] as $s) {
        $p = $s['placePrediction'] ?? null;
        if (!$p || empty($p['placeId'])) {
            continue;
        }
        $out[] = [
            'place_id' => $p['placeId'],
            'name' => $p['text']['text'] ?? '',
            'secondary' => $p['structuredFormat']['secondaryText']['text'] ?? '',
        ];
    }
    return ['ok' => true, 'results' => array_slice($out, 0, 6)];
}

/** Returns normalized place details, or null if the place can't be fetched. */
function places_details(string $placeId, ?string $sessionToken = null): ?array
{
    if (!is_valid_place_id($placeId)) {
        return null;
    }
    $url = 'https://places.googleapis.com/v1/places/' . rawurlencode($placeId);
    if ($sessionToken !== null && $sessionToken !== '') {
        $url .= '?sessionToken=' . rawurlencode($sessionToken);
    }
    $res = http_json('GET', $url, [
        'X-Goog-Api-Key: ' . places_key(),
        'X-Goog-FieldMask: id,displayName,formattedAddress,nationalPhoneNumber,websiteUri',
    ]);
    if (($res['_status'] ?? 0) !== 200 || empty($res['id'])) {
        return null;
    }
    return [
        'place_id' => $res['id'],
        'name' => $res['displayName']['text'] ?? '',
        'address' => $res['formattedAddress'] ?? '',
        'phone' => $res['nationalPhoneNumber'] ?? '',
        'website' => $res['websiteUri'] ?? '',
    ];
}
