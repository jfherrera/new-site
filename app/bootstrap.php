<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';

/**
 * Loads KEY=VALUE pairs from secrets.env in public_html/ (one level above the web
 * root public_html/public/, so it is not reachable by URL, and listed in .gitignore).
 * Values already set in the environment win. Missing file is fine (local development).
 */
function load_secrets(): void
{
    $file = dirname(__DIR__) . '/secrets.env';
    if (!is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}
load_secrets();

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function redirect_to(string $location, int $code = 302): never
{
    header('Location: ' . $location, true, $code);
    exit;
}

function body_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function clean_str(mixed $v, int $max = 200): string
{
    $s = trim((string) $v);
    $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s) ?? '';
    return mb_substr($s, 0, $max);
}

function is_valid_email(string $e): bool
{
    return $e !== '' && strlen($e) <= 254 && filter_var($e, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_place_id(string $id): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_-]{10,200}$/', $id);
}

function review_url(string $placeId): string
{
    return 'https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId);
}

/** Small cURL wrapper. Returns decoded JSON plus _status (HTTP code, 0 on transport error). */
function http_json(string $method, string $url, array $headers = [], ?array $body = null): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ];
    if ($body !== null) {
        $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['_status' => 0, '_error' => $err];
    }
    $data = json_decode((string) $raw, true);
    $data = is_array($data) ? $data : ['_raw' => substr((string) $raw, 0, 500)];
    $data['_status'] = $status;
    return $data;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $path = env('DB_PATH', APP_ROOT . '/data/kiss.sqlite');
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0750, true);
    }
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS orders (
    id TEXT PRIMARY KEY,
    created_at TEXT NOT NULL,
    place_id TEXT NOT NULL,
    business_name TEXT NOT NULL,
    formatted_address TEXT,
    phone TEXT,
    website TEXT,
    review_url TEXT NOT NULL,
    email TEXT NOT NULL,
    recipient_name TEXT,
    free_decal_qty INTEGER NOT NULL DEFAULT 1,
    pack_status TEXT NOT NULL DEFAULT 'none',
    pack_qty INTEGER NOT NULL DEFAULT 0,
    paypal_order_id TEXT,
    pack_paid_at TEXT,
    pack_ship_name TEXT,
    pack_ship_address TEXT,
    email_consent INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS sync_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id TEXT NOT NULL,
    action TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT,
    created_at TEXT NOT NULL,
    done_at TEXT
);
CREATE INDEX IF NOT EXISTS sync_jobs_status ON sync_jobs(status, id);
SQL);
    return $pdo;
}
