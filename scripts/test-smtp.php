<?php

/**
 * Minimal Gmail SMTP test — run: php scripts/test-smtp.php
 */

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
    if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
        $val = substr($val, 1, -1);
    }
    $vars[$key] = $val;
}

$host = $vars['SMTP_HOST'] ?? '';
$user = $vars['SMTP_USER'] ?? '';
$pass = str_replace(' ', '', $vars['SMTP_PASS'] ?? '');
$port = (int) ($vars['SMTP_PORT'] ?? 587);
$crypto = strtolower($vars['SMTP_CRYPTO'] ?? 'tls');

echo "OpenSSL: " . (extension_loaded('openssl') ? 'yes' : 'NO') . PHP_EOL;
echo "Host: {$host}:{$port} ({$crypto}), user: {$user}, pass set: " . ($pass !== '' ? 'yes' : 'no') . PHP_EOL;

if ($host === '' || $user === '' || $pass === '') {
    fwrite(STDERR, "SMTP_HOST, SMTP_USER, or SMTP_PASS missing in .env\n");
    exit(1);
}

$remote = ($crypto === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
$ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
$fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
if (! $fp) {
    fwrite(STDERR, "Connect failed: {$errstr} ({$errno})\n");
    exit(1);
}

$read = function () use ($fp): string {
    $out = '';
    while ($line = fgets($fp, 515)) {
        $out .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $out;
};
$cmd = function (string $c) use ($fp, $read): string {
    fwrite($fp, $c . "\r\n");
    return $read();
};

echo $read();
if ($crypto === 'tls') {
    echo $cmd('EHLO localhost');
    echo $cmd('STARTTLS');
    stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
}
echo $cmd('EHLO localhost');
echo $cmd('AUTH LOGIN');
echo $cmd(base64_encode($user));
$r = $cmd(base64_encode($pass));
echo $r;
if (! str_starts_with($r, '235')) {
    fwrite(STDERR, "AUTH failed\n");
    exit(1);
}
echo $cmd('MAIL FROM:<' . $user . '>');
echo $cmd('RCPT TO:<' . $user . '>');
echo $cmd('DATA');
fwrite($fp, "Subject: AJES SMTP Test\r\n\r\nTest OK.\r\n.\r\n");
echo $read();
echo $cmd('QUIT');
fclose($fp);
echo "SUCCESS: Gmail accepted the message for delivery.\n";
