<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../app/Store.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$password = env('ADMIN_PASSWORD');
if ($password === null || strlen($password) < 12) {
    http_response_code(503);
    echo 'Admin is not configured. Set ADMIN_PASSWORD (12+ characters) in secrets.env.';
    exit;
}
// Cookie value is derived from the password, so the password itself is never stored in the browser.
$token = hash_hmac('sha256', 'kiss-admin-session', $password);
$cookieOpts = ['path' => '/admin/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (hash_equals($password, (string) ($_POST['password'] ?? ''))) {
        setcookie('kiss_admin', $token, $cookieOpts + ['expires' => time() + 8 * 3600]);
        redirect_to('/admin/orders/', 303);
    }
    $loginError = true;
}

if (isset($_GET['logout'])) {
    setcookie('kiss_admin', '', $cookieOpts + ['expires' => time() - 3600]);
    redirect_to('/admin/orders/', 303);
}

$authed = isset($_COOKIE['kiss_admin']) && hash_equals($token, (string) $_COOKIE['kiss_admin']);

if (!$authed) {
    $err = !empty($loginError) ? '<p class="err">Wrong password.</p>' : '';
    echo <<<HTML
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Orders login</title>
<style>body{font:16px system-ui,sans-serif;max-width:360px;margin:80px auto;padding:0 16px}input{width:100%;padding:10px;font-size:16px;box-sizing:border-box}button{margin-top:12px;padding:10px 16px;font-size:16px}.err{color:#b3262f}</style>
</head><body><h1>Orders</h1>{$err}
<form method="post"><input type="hidden" name="action" value="login"><label>Password<br><input type="password" name="password" autofocus required></label><br><button type="submit">Sign in</button></form>
</body></html>
HTML;
    exit;
}

$rows = db()->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();

if (($_GET['format'] ?? '') === 'csv') {
    // Guard against spreadsheet formula injection in exported cells.
    $safe = static function ($v): string {
        $s = (string) ($v ?? '');
        return ($s !== '' && strpbrk($s[0], '=+-@') !== false) ? "'" . $s : $s;
    };
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kiss-orders-' . gmdate('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['created_at', 'order_id', 'business_name', 'mailing_address', 'recipient_name', 'email', 'phone', 'website', 'free_decal_qty', 'pack_status', 'pack_qty', 'pack_paid_at', 'pack_ship_name', 'pack_ship_address', 'email_consent', 'review_url', 'place_id']);
    foreach ($rows as $r) {
        fputcsv($out, array_map($safe, [
            $r['created_at'], $r['id'], $r['business_name'], $r['formatted_address'], $r['recipient_name'],
            $r['email'], $r['phone'], $r['website'], $r['free_decal_qty'], $r['pack_status'], $r['pack_qty'],
            $r['pack_paid_at'], $r['pack_ship_name'], $r['pack_ship_address'], $r['email_consent'] ? 'yes' : 'no',
            $r['review_url'], $r['place_id'],
        ]));
    }
    fclose($out);
    exit;
}

$body = '';
foreach ($rows as $r) {
    $packCell = $r['pack_status'] === 'paid'
        ? '<span class="paid">Paid ' . h($r['pack_paid_at']) . '</span><br>' . h($r['pack_ship_name']) . '<br>' . h($r['pack_ship_address'])
        : '<span class="muted">Not purchased</span>';
    $body .= '<tr>'
        . '<td>' . h(substr((string) $r['created_at'], 0, 16)) . '</td>'
        . '<td><strong>' . h($r['business_name']) . '</strong><br><span class="muted">' . h($r['website']) . '</span></td>'
        . '<td>' . h($r['formatted_address']) . '</td>'
        . '<td>' . h($r['recipient_name']) . '</td>'
        . '<td>' . h($r['email']) . '<br>' . h($r['phone']) . '</td>'
        . '<td>' . (int) $r['free_decal_qty'] . ' free</td>'
        . '<td>' . $packCell . '</td>'
        . '</tr>';
}
$count = count($rows);
$paidCount = count(array_filter($rows, static fn($r) => $r['pack_status'] === 'paid'));

echo <<<HTML
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Orders | KISS Reviews</title>
<style>
body{font:15px/1.45 system-ui,sans-serif;margin:24px;color:#1f2026}
header{display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
h1{font-size:22px;margin:0 16px 0 0}
a.btn{display:inline-block;padding:9px 14px;border-radius:8px;text-decoration:none;font-weight:600}
.dl{background:#e8475f;color:#fff}.out{border:1px solid #c9ceda;color:#1f2026}
table{border-collapse:collapse;width:100%}
th,td{border-bottom:1px solid #e6e8ee;padding:8px;text-align:left;vertical-align:top}
th{background:#f6f7fa;position:sticky;top:0}
.muted{color:#5d6270}.paid{color:#17743a;font-weight:700}
.summary{color:#5d6270}
</style></head><body>
<header>
  <h1>Orders</h1>
  <span class="summary">{$count} leads, {$paidCount} upsell buyers</span>
  <a class="btn dl" href="/admin/orders/?format=csv">Download CSV</a>
  <a class="btn out" href="/admin/orders/?logout=1">Sign out</a>
</header>
<table>
<thead><tr><th>Date (UTC)</th><th>Business</th><th>Mailing address</th><th>Recipient</th><th>Contact</th><th>Free decal</th><th>5-pack</th></tr></thead>
<tbody>{$body}</tbody>
</table>
</body></html>
HTML;
