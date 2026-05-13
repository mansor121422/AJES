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
    'settings'         => 'System Settings',
    'chatbot'          => 'Chatbot Management',
    'backup'           => 'Backup & Restore',
    'security-logs'    => 'Security Logs',
    'active-sessions'  => 'Active Sessions',
    'audit-report'     => 'Audit Report',
    'activity-logs'    => 'Activity Logs',
    'transaction-logs' => 'Transaction Logs',
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

    <!-- Lab 6 sub-nav for security/audit tabs -->
    <?php
    $labTabs = [
        'security-logs'   => 'Security Logs',
        'active-sessions' => 'Active Sessions',
        'audit-report'    => 'Audit Report',
        'activity-logs'   => 'Activity Logs',
        'transaction-logs'=> 'Transaction Logs',
    ];
    $isLabTab = in_array($tab, array_keys($labTabs), true);
    ?>
    <?php if ($isLabTab): ?>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">
        <?php foreach ($labTabs as $tabKey => $tabLabel): ?>
            <a href="<?= base_url('sysadmin/' . $tabKey) ?>"
               style="padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;
                      <?= $tab === $tabKey ? 'background:#1b5e20;color:#fff;' : 'background:#e8f5e9;color:#2e7d32;' ?>">
                <?= esc($tabLabel) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

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
                    <thead><tr><th>Time</th><th>Action</th><th>User</th><th>IP</th><th>Details</th></tr></thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <?php
                                $actionColors = [
                                    'LOGIN_SUCCESS'  => '#2e7d32',
                                    'LOGIN_FAILED'   => '#c62828',
                                    'LOGOUT'         => '#6a6a6a',
                                    'USER_CREATED'   => '#1565c0',
                                    'USER_UPDATED'   => '#e65100',
                                    'USER_DELETED'   => '#b71c1c',
                                    'USER_RESTORED'  => '#2e7d32',
                                    'PASSWORD_RESET' => '#6a1b9a',
                                    'ROLE_CHANGED'   => '#ef6c00',
                                    'SECURITY_ALERT' => '#d50000',
                                    'MFA_SUCCESS'    => '#00695c',
                                    'MFA_FAILED'     => '#c62828',
                                ];
                                $color = $actionColors[$log['action_type'] ?? ''] ?? '#333';
                                $who = esc((string) ($log['user_name'] ?? $log['username'] ?? '—'));
                            ?>
                            <tr>
                                <td style="white-space:nowrap;"><?= esc((string) ($log['created_at'] ?? '')) ?></td>
                                <td><span style="color:<?= $color ?>;font-weight:600;"><?= esc((string) ($log['action_type'] ?? '')) ?></span></td>
                                <td><?= $who ?></td>
                                <td style="font-size:12px;"><?= esc((string) ($log['ip_address'] ?? '')) ?></td>
                                <td style="max-width:300px;word-break:break-word;"><?= esc((string) ($log['details'] ?? '')) ?></td>
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

        <?php elseif ($tab === 'active-sessions'): ?>
            <?php $sessions = $sessions ?? []; ?>
            <p style="margin-bottom:12px;"><strong>Currently Online:</strong> <?= count($sessions) ?> active session(s)</p>
            <?php if ($sessions !== []): ?>
                <div style="overflow-x:auto;">
                <table class="recent-table" style="width:100%;">
                    <thead><tr><th>User</th><th>Role</th><th>IP Address</th><th>Session Start</th><th>Last Activity</th><th>User Agent</th></tr></thead>
                    <tbody>
                        <?php foreach ($sessions as $s): ?>
                            <tr>
                                <td style="font-weight:600;"><?= esc((string) ($s['user_name'] ?? $s['username'] ?? '')) ?></td>
                                <td><span style="background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;"><?= esc((string) ($s['role'] ?? '')) ?></span></td>
                                <td style="font-family:monospace;font-size:12px;"><?= esc((string) ($s['ip_address'] ?? '')) ?></td>
                                <td style="white-space:nowrap;"><?= esc((string) ($s['started_at'] ?? '')) ?></td>
                                <td style="white-space:nowrap;"><?= esc((string) ($s['last_activity'] ?? '')) ?></td>
                                <td style="max-width:200px;font-size:11px;word-break:break-word;"><?= esc(mb_substr((string) ($s['user_agent'] ?? ''), 0, 80)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <p style="color:#888;">No active sessions detected.</p>
            <?php endif; ?>

        <?php elseif ($tab === 'audit-report'): ?>
            <?php $report = $audit_report ?? []; ?>
            <?php if ($report !== []): ?>
                <p style="margin-bottom:16px;">Showing data for the last <strong><?= esc((string) ($report['days'] ?? 7)) ?> days</strong>.
                    <a href="<?= base_url('sysadmin/audit-report?days=1') ?>" style="margin-left:8px;color:#2e7d32;">1d</a>
                    <a href="<?= base_url('sysadmin/audit-report?days=7') ?>" style="margin-left:4px;color:#2e7d32;">7d</a>
                    <a href="<?= base_url('sysadmin/audit-report?days=30') ?>" style="margin-left:4px;color:#2e7d32;">30d</a>
                </p>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:12px;margin-bottom:20px;">
                    <div style="background:#e8f5e9;padding:16px;border-radius:10px;text-align:center;">
                        <div style="font-size:2rem;font-weight:800;color:#1b5e20;"><?= esc((string) ($report['total_logins'] ?? 0)) ?></div>
                        <div style="font-size:12px;color:#558b2f;">Successful Logins</div>
                    </div>
                    <div style="background:#ffebee;padding:16px;border-radius:10px;text-align:center;">
                        <div style="font-size:2rem;font-weight:800;color:#c62828;"><?= esc((string) ($report['failed_logins'] ?? 0)) ?></div>
                        <div style="font-size:12px;color:#b71c1c;">Failed Login Attempts</div>
                    </div>
                    <div style="background:#fff3e0;padding:16px;border-radius:10px;text-align:center;">
                        <div style="font-size:2rem;font-weight:800;color:#e65100;"><?= esc((string) ($report['security_alerts'] ?? 0)) ?></div>
                        <div style="font-size:12px;color:#bf360c;">Security Alerts</div>
                    </div>
                    <div style="background:#e0f2f1;padding:16px;border-radius:10px;text-align:center;">
                        <div style="font-size:2rem;font-weight:800;color:#00695c;"><?= esc((string) ($report['mfa_events'] ?? 0)) ?></div>
                        <div style="font-size:12px;color:#004d40;">MFA Events</div>
                    </div>
                    <div style="background:#e8eaf6;padding:16px;border-radius:10px;text-align:center;">
                        <div style="font-size:2rem;font-weight:800;color:#283593;"><?= esc((string) ($report['user_changes'] ?? 0)) ?></div>
                        <div style="font-size:12px;color:#1a237e;">User Changes</div>
                    </div>
                </div>

                <?php $mostActive = (array) ($report['most_active'] ?? []); ?>
                <?php if ($mostActive !== []): ?>
                    <h3 style="margin-top:8px;color:#1b5e20;">Most Active Users</h3>
                    <table class="recent-table" style="margin-top:8px;max-width:400px;">
                        <thead><tr><th>User</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($mostActive as $mu): ?>
                                <tr>
                                    <td><?= esc((string) ($mu['name'] ?? $mu['username'] ?? '')) ?></td>
                                    <td style="font-weight:700;color:#1b5e20;"><?= esc((string) ($mu['action_count'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php $daily = (array) ($report['daily_breakdown'] ?? []); ?>
                <?php if ($daily !== []): ?>
                    <h3 style="margin-top:20px;color:#1b5e20;">Daily Breakdown</h3>
                    <div style="overflow-x:auto;">
                    <table class="recent-table" style="margin-top:8px;width:100%;">
                        <thead><tr><th>Date</th><th>Action Type</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($daily as $d): ?>
                                <tr>
                                    <td style="white-space:nowrap;"><?= esc((string) ($d['log_date'] ?? '')) ?></td>
                                    <td><?= esc((string) ($d['action_type'] ?? '')) ?></td>
                                    <td style="font-weight:700;"><?= esc((string) ($d['cnt'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="color:#888;">No audit data available.</p>
            <?php endif; ?>

        <?php elseif ($tab === 'activity-logs'): ?>
            <?php $actLogs = $activity_logs ?? []; ?>
            <p style="margin-bottom:12px;">Showing last <strong><?= count($actLogs) ?></strong> activity entries. Sensitive fields (IP, details) are encrypted at rest.</p>
            <?php if ($actLogs !== []): ?>
                <div style="overflow-x:auto;">
                <table class="recent-table" style="width:100%;">
                    <thead><tr><th>Time</th><th>Action</th><th>User</th><th>URL</th><th>Method</th><th>IP</th><th>Details</th></tr></thead>
                    <tbody>
                        <?php foreach ($actLogs as $al): ?>
                            <tr>
                                <td style="white-space:nowrap;font-size:12px;"><?= esc((string) ($al['created_at'] ?? '')) ?></td>
                                <td><span style="font-weight:600;color:#1b5e20;"><?= esc((string) ($al['action'] ?? '')) ?></span></td>
                                <td><?= esc((string) ($al['user_name'] ?? $al['username'] ?? '—')) ?></td>
                                <td style="max-width:200px;word-break:break-word;font-size:11px;"><?= esc((string) ($al['url'] ?? '')) ?></td>
                                <td style="font-size:11px;"><?= esc((string) ($al['method'] ?? '')) ?></td>
                                <td style="font-family:monospace;font-size:11px;"><?= esc((string) ($al['ip_address'] ?? '')) ?></td>
                                <td style="max-width:200px;word-break:break-word;font-size:11px;"><?= esc((string) ($al['details'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <p style="color:#888;">No activity logs recorded yet.</p>
            <?php endif; ?>

        <?php elseif ($tab === 'transaction-logs'): ?>
            <?php $txLogs = $transaction_logs ?? []; ?>
            <p style="margin-bottom:12px;">Business transaction log — every CRUD operation is wrapped in a DB transaction with ACID guarantees.</p>
            <?php if ($txLogs !== []): ?>
                <div style="overflow-x:auto;">
                <table class="recent-table" style="width:100%;">
                    <thead><tr><th>Time</th><th>Operation</th><th>User</th><th>Table</th><th>ID</th><th>Status</th><th>Duration</th><th>Error</th></tr></thead>
                    <tbody>
                        <?php foreach ($txLogs as $tx): ?>
                            <?php $isOk = ($tx['status'] ?? '') === 'COMMITTED'; ?>
                            <tr>
                                <td style="white-space:nowrap;font-size:12px;"><?= esc((string) ($tx['created_at'] ?? '')) ?></td>
                                <td style="font-weight:600;"><?= esc((string) ($tx['operation'] ?? '')) ?></td>
                                <td><?= esc((string) ($tx['user_name'] ?? $tx['username'] ?? '—')) ?></td>
                                <td style="font-size:12px;"><?= esc((string) ($tx['target_table'] ?? '')) ?></td>
                                <td style="font-size:12px;"><?= esc((string) ($tx['target_id'] ?? '')) ?></td>
                                <td><span style="color:<?= $isOk ? '#2e7d32' : '#c62828' ?>;font-weight:700;"><?= esc((string) ($tx['status'] ?? '')) ?></span></td>
                                <td style="font-size:12px;"><?= esc((string) ($tx['duration_ms'] ?? '')) ?>ms</td>
                                <td style="max-width:200px;word-break:break-word;font-size:11px;color:#c62828;"><?= esc((string) ($tx['error'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <p style="color:#888;">No transaction logs recorded yet.</p>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    </main>
</div>
</body>
</html>
