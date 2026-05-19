<?php
$settingsData = $settingsData ?? $settings_data ?? [];
$db = (array) ($settingsData['database'] ?? []);
$urlOk = ! empty($settingsData['url_match']);
?>
<p style="color:#558b2f; margin:0 0 16px;">Live status from your running AJES install (.env, database, and this browser session).</p>

<h3 style="color:#1b5e20; font-size:1rem; margin:0 0 10px;">Environment</h3>
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:18px;">
    <div><strong>Mode</strong><br><?= esc((string) ($settingsData['environment'] ?? '')) ?></div>
    <div><strong>PHP</strong><br><?= esc((string) ($settingsData['php_version'] ?? '')) ?></div>
    <div><strong>CodeIgniter</strong><br><?= esc((string) ($settingsData['ci_version'] ?? '')) ?></div>
    <div><strong>Config file</strong><br><?= esc((string) ($settingsData['env_file'] ?? '')) ?></div>
</div>

<h3 style="color:#1b5e20; font-size:1rem; margin:0 0 10px;">URLs (web + Android)</h3>
<div style="background:#f1f8e9; border:1px solid #c8e6c9; border-radius:8px; padding:14px; margin-bottom:18px;">
    <p style="margin:0 0 8px;"><strong>You are browsing:</strong><br><code><?= esc((string) ($settingsData['current_access_url'] ?? '')) ?></code></p>
    <p style="margin:0 0 8px;"><strong>Configured base URL</strong> (links, emails, app):<br><code><?= esc((string) ($settingsData['configured_base_url'] ?? '')) ?></code></p>
    <p style="margin:0 0 8px;"><strong>From .env</strong> <code>app.baseURL</code>:<br><code><?= esc((string) ($settingsData['env_base_url'] ?? '')) ?></code></p>
    <p style="margin:0 0 8px;"><strong>Android API base</strong> (AJESCHAT <code>ajes.baseUrl</code>):<br><code><?= esc((string) ($settingsData['android_api_url'] ?? '')) ?></code></p>
    <p style="margin:0;">
        <strong>URL check:</strong>
        <?php if ($urlOk): ?>
            <span style="color:#2e7d32;">OK — current host/path matches configured base URL.</span>
        <?php else: ?>
            <span style="color:#e65100;">Different — normal if you use localhost on PC but app.baseURL is your LAN IP for phones.</span>
        <?php endif; ?>
    </p>
</div>

<h3 style="color:#1b5e20; font-size:1rem; margin:0 0 10px;">Database</h3>
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:18px;">
    <div><strong>Status</strong><br>
        <?php if (! empty($db['ok'])): ?>
            <span style="color:#2e7d32;"><?= esc((string) ($db['message'] ?? 'Connected')) ?></span>
        <?php else: ?>
            <span style="color:#c62828;">Failed — <?= esc((string) ($db['message'] ?? '')) ?></span>
        <?php endif; ?>
    </div>
    <div><strong>Host</strong><br><?= esc((string) ($db['hostname'] ?? '')) ?></div>
    <div><strong>Database</strong><br><?= esc((string) ($db['database'] ?? '')) ?></div>
    <div><strong>Tables</strong><br><?= esc($db['tables'] !== null ? (string) $db['tables'] : '—') ?></div>
</div>

<h3 style="color:#1b5e20; font-size:1rem; margin:0 0 10px;">Application</h3>
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:18px;">
    <div><strong>Index page</strong><br><?= esc((string) ($settingsData['index_page'] ?? '')) ?></div>
    <div><strong>Locale</strong><br><?= esc((string) ($settingsData['default_locale'] ?? '')) ?></div>
    <div><strong>App timezone</strong><br><?= esc((string) ($settingsData['app_timezone'] ?? '')) ?></div>
    <div><strong>PHP timezone</strong><br><?= esc((string) ($settingsData['php_timezone'] ?? '')) ?></div>
    <div><strong>HTTPS forced</strong><br><?= ! empty($settingsData['secure_requests']) ? 'Yes' : 'No' ?></div>
    <div><strong>Presence timeout</strong><br><?= esc((string) ($settingsData['presence_timeout'] ?? 0)) ?> sec</div>
    <div><strong>Presence heartbeat</strong><br><?= esc((string) ($settingsData['presence_heartbeat'] ?? 0)) ?> sec</div>
    <div><strong>Allowed hosts</strong><br><span style="font-size:0.85rem;"><?= esc((string) ($settingsData['allowed_hostnames'] ?? '')) ?></span></div>
</div>

<h3 style="color:#1b5e20; font-size:1rem; margin:0 0 10px;">Services & storage</h3>
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-bottom:12px;">
    <div><strong>Encryption key</strong><br><?= ! empty($settingsData['encryption_ok']) ? '<span style="color:#2e7d32;">Configured</span>' : '<span style="color:#c62828;">Missing</span>' ?></div>
    <div><strong>Email (SMTP)</strong><br><?= ! empty($settingsData['smtp_configured']) ? '<span style="color:#2e7d32;">Configured</span>' : '<span style="color:#888;">Not set</span>' ?></div>
    <div><strong>Groq AI</strong><br><?= ! empty($settingsData['groq_configured']) ? '<span style="color:#2e7d32;">Configured</span>' : '<span style="color:#888;">Not set</span>' ?></div>
    <div><strong>Chatbot</strong><br><?= ! empty($settingsData['ai_enabled']) ? 'Enabled' : 'Disabled' ?></div>
    <div><strong>SQL backups</strong><br><?= esc((string) ($settingsData['backup_count'] ?? 0)) ?> file(s)</div>
</div>
<?php $writable = (array) ($settingsData['writable'] ?? []); ?>
<?php if ($writable !== []): ?>
    <p style="margin:0; font-size:0.9rem; color:#555;">
        <strong>Writable folders:</strong>
        <?php foreach ($writable as $name => $info): ?>
            <span style="margin-right:10px;"><?= esc($name) ?>: <?= ! empty($info['ok']) ? 'OK' : 'not writable' ?></span>
        <?php endforeach; ?>
    </p>
<?php endif; ?>
<p style="margin-top:14px;color:#558b2f; font-size:0.85rem;">Edit <code>.env</code> for app.baseURL, database, SMTP, and keys. Restart Apache after changing .env.</p>
