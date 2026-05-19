<?php

/**
 * Quick check: decrypt guardian_contact for a user id.
 * Usage: php scripts/test-decrypt-contact.php [user_id]
 */

$userId = (int) ($argv[1] ?? 14);

$envFile = __DIR__ . '/../.env';
if (! is_readable($envFile)) {
    fwrite(STDERR, "Missing .env\n");
    exit(1);
}

$vars = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    $eq = strpos($line, '=');
    if ($eq === false) {
        continue;
    }
    $key = trim(substr($line, 0, $eq));
    $val = trim(substr($line, $eq + 1));
    $vars[$key] = $val;
}

require_once __DIR__ . '/../app/Libraries/DataEncryptor.php';

$mysqli = new mysqli(
    $vars['database.default.hostname'] ?? 'localhost',
    $vars['database.default.username'] ?? 'root',
    $vars['database.default.password'] ?? '',
    $vars['database.default.database'] ?? 'AjesDB'
);

if ($mysqli->connect_error) {
    fwrite(STDERR, 'DB connect failed: ' . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

// Load env for DataEncryptor::getKey()
foreach ($vars as $k => $v) {
    putenv($k . '=' . $v);
    $_ENV[$k]    = $v;
    $_SERVER[$k] = $v;
}

if (! function_exists('env')) {
    function env(string $key, $default = null)
    {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($v === false || $v === null || $v === '') {
            return $default;
        }

        return $v;
    }
}

$stmt = $mysqli->prepare('SELECT id, guardian_contact FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (! $row) {
    fwrite(STDERR, "User {$userId} not found.\n");
    exit(1);
}

$raw = (string) ($row['guardian_contact'] ?? '');
$display = \App\Libraries\DataEncryptor::decryptForDisplay($raw);

echo "User ID: {$userId}\n";
echo 'Stored (prefix): ' . substr($raw, 0, 40) . (strlen($raw) > 40 ? '...' : '') . "\n";
echo 'Decrypt for display: ' . ($display !== '' ? $display : '(empty — key mismatch or corrupt data)') . "\n";
