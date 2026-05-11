<?php
$role = $role ?? 'ADMIN';
$name = $name ?? 'System Administrator';
$tab = $tab ?? 'settings';
$logCount = (int) ($log_count ?? 0);
$settingsData = $settings_data ?? [];
$chatbotData = $chatbot_data ?? [];
$backupData = $backup_data ?? [];
$securityData = $security_data ?? [];

$titles = [
    'settings' => 'System Settings',
    'chatbot' => 'Chatbot Management',
    'backup' => 'Backup & Restore',
    'security-logs' => 'Security Logs',
];
$title = $titles[$tab] ?? 'System Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?> - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header"><?= esc($title) ?></h1>

    <div class="card">
        <div class="card-title">Admin System Control</div>
        <p style="margin-bottom: 12px; color: #558b2f;">Welcome, <?= esc($name) ?>. This panel centralizes technical and system administration tasks.</p>

        <?php if ($tab === 'settings'): ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px;">
                <div><strong>Base URL</strong><br><span><?= esc((string) ($settingsData['base_url'] ?? '')) ?></span></div>
                <div><strong>Index Page</strong><br><span><?= esc((string) ($settingsData['index_page'] ?? '')) ?></span></div>
                <div><strong>Default Locale</strong><br><span><?= esc((string) ($settingsData['default_locale'] ?? '')) ?></span></div>
                <div><strong>Timezone</strong><br><span><?= esc((string) ($settingsData['timezone'] ?? '')) ?></span></div>
                <div><strong>Force Secure Requests</strong><br><span><?= ! empty($settingsData['secure_requests']) ? 'Enabled' : 'Disabled' ?></span></div>
                <div><strong>Presence Timeout</strong><br><span><?= esc((string) ($settingsData['presence_timeout'] ?? 0)) ?> seconds</span></div>
                <div><strong>Presence Heartbeat</strong><br><span><?= esc((string) ($settingsData['presence_heartbeat'] ?? 0)) ?> seconds</span></div>
            </div>
            <p style="margin-top:12px;color:#558b2f;">Source: `app/Config/App.php`</p>
        <?php elseif ($tab === 'chatbot'): ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px;">
                <div><strong>Enabled</strong><br><span><?= ! empty($chatbotData['enabled']) ? 'Yes' : 'No' ?></span></div>
                <div><strong>Auto Reply</strong><br><span><?= ! empty($chatbotData['auto_reply']) ? 'Yes' : 'No' ?></span></div>
                <div><strong>API Configured</strong><br><span><?= ! empty($chatbotData['api_configured']) ? 'Yes' : 'No' ?></span></div>
                <div><strong>Model</strong><br><span><?= esc((string) ($chatbotData['provider_model'] ?? '')) ?></span></div>
                <div><strong>Assistant Name</strong><br><span><?= esc((string) ($chatbotData['ai_name'] ?? '')) ?></span></div>
                <div><strong>Max Response</strong><br><span><?= esc((string) ($chatbotData['max_response_length'] ?? 0)) ?> chars</span></div>
                <div><strong>Temperature</strong><br><span><?= esc((string) ($chatbotData['temperature'] ?? 0)) ?></span></div>
            </div>
            <p style="margin-top:12px;"><strong>Trigger Roles:</strong> <?= esc(implode(', ', (array) ($chatbotData['trigger_roles'] ?? []))) ?></p>
            <p><strong>Receiver Roles:</strong> <?= esc(implode(', ', (array) ($chatbotData['receiver_roles'] ?? []))) ?></p>
            <p><strong>Trigger Mentions:</strong> <?= esc(implode(', ', (array) ($chatbotData['mentions'] ?? []))) ?></p>
            <p style="margin-top:12px;color:#558b2f;">Source: `app/Config/AIChat.php` and environment vars.</p>
        <?php elseif ($tab === 'backup'): ?>
            <p><strong>Backup Directory:</strong> <?= esc((string) ($backupData['backup_dir'] ?? '')) ?></p>
            <p><strong>Detected Backup Files:</strong> <?= esc((string) ($backupData['file_count'] ?? 0)) ?></p>
            <?php $cmds = (array) ($backupData['commands'] ?? []); ?>
            <?php if ($cmds !== []): ?>
                <p style="margin-top:10px;"><strong>Repository scripts (Lab 4):</strong></p>
                <ul style="margin:8px 0 0 18px; color:#333;">
                    <?php foreach ($cmds as $label => $line): ?>
                        <li style="margin-bottom:6px;"><strong><?= esc((string) $label) ?>:</strong><br><code style="word-break:break-all;"><?= esc((string) $line) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php $files = (array) ($backupData['files'] ?? []); ?>
            <?php if ($files !== []): ?>
                <table class="recent-table" style="margin-top:12px;">
                    <thead><tr><th>File</th><th>Size</th><th>Modified</th></tr></thead>
                    <tbody>
                        <?php foreach ($files as $f): ?>
                            <tr>
                                <td><?= esc((string) ($f['name'] ?? '')) ?></td>
                                <td><?= esc((string) ($f['size'] ?? '')) ?></td>
                                <td><?= esc((string) ($f['modified_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php elseif ($tab === 'security-logs'): ?>
            <p><strong>Log Directory:</strong> <?= esc((string) ($securityData['log_dir'] ?? '')) ?></p>
            <p><strong>Detected Log Files:</strong> <?= esc((string) $logCount) ?></p>
            <p><strong>Error Entries:</strong> <?= esc((string) ($securityData['error_count'] ?? 0)) ?> &nbsp; | &nbsp; <strong>Warning Entries:</strong> <?= esc((string) ($securityData['warning_count'] ?? 0)) ?></p>

            <?php $auditLogs = (array) ($securityData['audit_logs'] ?? []); ?>
            <?php if ($auditLogs !== []): ?>
                <h3 style="margin-top:18px; color:#1b5e20;">Audit Trail</h3>
                <div style="overflow-x:auto;">
                <table class="recent-table" style="margin-top:8px; width:100%;">
                    <thead><tr><th>Time</th><th>Action</th><th>User</th><th>Details</th></tr></thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <?php
                                $actionColors = [
                                    'LOGIN_SUCCESS' => '#2e7d32',
                                    'LOGIN_FAILED'  => '#c62828',
                                    'LOGOUT'        => '#6a6a6a',
                                    'USER_CREATED'  => '#1565c0',
                                    'USER_UPDATED'  => '#e65100',
                                    'USER_DELETED'  => '#b71c1c',
                                    'USER_RESTORED' => '#2e7d32',
                                    'PASSWORD_RESET' => '#6a1b9a',
                                    'ROLE_CHANGED'  => '#ef6c00',
                                ];
                                $color = $actionColors[$log['action_type'] ?? ''] ?? '#333';
                                $who = esc((string) ($log['user_name'] ?? $log['username'] ?? '—'));
                            ?>
                            <tr>
                                <td style="white-space:nowrap;"><?= esc((string) ($log['created_at'] ?? '')) ?></td>
                                <td><span style="color:<?= $color ?>;font-weight:600;"><?= esc((string) ($log['action_type'] ?? '')) ?></span></td>
                                <td><?= $who ?></td>
                                <td style="max-width:340px;word-break:break-word;"><?= esc((string) ($log['details'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <p style="margin-top:14px; color:#888;">No audit log entries yet. Entries appear after logins, user changes, and other security events.</p>
            <?php endif; ?>

            <?php $logFiles = (array) ($securityData['files'] ?? []); ?>
            <?php if ($logFiles !== []): ?>
                <h3 style="margin-top:24px; color:#1b5e20;">Application Log Files</h3>
                <table class="recent-table" style="margin-top:8px;">
                    <thead><tr><th>Log File</th><th>Size</th><th>Modified</th></tr></thead>
                    <tbody>
                        <?php foreach ($logFiles as $f): ?>
                            <tr>
                                <td><?= esc((string) ($f['name'] ?? '')) ?></td>
                                <td><?= esc((string) ($f['size'] ?? '')) ?></td>
                                <td><?= esc((string) ($f['modified_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    </main>
</div>
</body>
</html>
