<?php
/**
 * Drains queued systeme.io writes. Run from cron every minute:
 *   * * * * * php /path/to/app/tools/sync_systeme.php >/dev/null 2>&1
 * Free plan limit is 30 requests/minute, so this worker processes at most
 * MAX_JOBS_PER_RUN jobs and waits between API calls.
 */
declare(strict_types=1);
require_once __DIR__ . '/../app/Systeme.php';
require_once __DIR__ . '/../app/Store.php';


const MAX_JOBS_PER_RUN = 8;
const MAX_ATTEMPTS = 5;

$logFile = data_dir() . '/sync.log';
file_put_contents($logFile, gmdate('c') . ' worker started' . PHP_EOL, FILE_APPEND | LOCK_EX);

$lock = fopen(data_dir() . '/sync.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit(0); // previous run still going
}

$jobs = db()->query("SELECT * FROM sync_jobs WHERE status = 'pending' AND attempts < " . MAX_ATTEMPTS . ' ORDER BY id LIMIT ' . MAX_JOBS_PER_RUN)->fetchAll();

foreach ($jobs as $job) {
    $order = order_get($job['order_id']);
    $ok = false;
    $error = null;

    if ($order === null) {
        $error = 'order_missing';
    } elseif ($job['action'] === 'sync_lead') {
        $contactId = systeme_upsert_contact($order);
        if ($contactId === null) {
            $error = 'contact_upsert_failed';
        } else {
            $ok = systeme_add_tag($contactId, 'kiss-free-decal');
            if (!$ok) {
                $error = 'tag_failed';
            }
        }
    } elseif ($job['action'] === 'tag_pack_buyer') {
        $contactId = systeme_upsert_contact($order);
        if ($contactId !== null) {
            $ok = systeme_add_tag($contactId, 'kiss-pack-buyer');
        }
        if (!$ok) {
            $error = 'pack_tag_failed';
        }
    } else {
        $error = 'unknown_action';
    }

    if (!$ok) {
        // Plain-text log in data/ (not web-accessible) so failures can be read over SFTP.
        $line = gmdate('c') . " job={$job['id']} action={$job['action']} error={$error} last_call=" . ($GLOBALS['systeme_last'] ?? 'none') . PHP_EOL;
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    $attempts = (int) $job['attempts'] + 1;
    $status = $ok ? 'done' : ($attempts >= MAX_ATTEMPTS || $error === 'unknown_action' ? 'failed' : 'pending');
    db()->prepare('UPDATE sync_jobs SET status = ?, attempts = ?, last_error = ?, done_at = ? WHERE id = ?')
        ->execute([$status, $attempts, $error, $ok ? gmdate('c') : null, $job['id']]);
}
flock($lock, LOCK_UN);
