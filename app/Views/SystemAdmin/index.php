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

        <?php if (session()->getFlashdata('success')): ?>
            <div class="message success" style="margin-bottom: 12px;"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="message" style="margin-bottom: 12px;"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if ($tab === 'settings'): ?>
            <?php include APPPATH . 'Views/SystemAdmin/_settings_tab.php'; ?>
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
            <p style="margin-top:12px;color:#558b2f;">AjesAI replies when a <strong>student</strong> messages Principal/Guidance (or types <code>@ajesai</code>). Answers use live AJES announcements and sections only.</p>
        <?php elseif ($tab === 'backup'): ?>
            <p><strong>Database:</strong> <?= esc((string) ($backupData['db_name'] ?? '')) ?></p>
            <p><strong>Backup folder:</strong> <code style="word-break:break-all;"><?= esc((string) ($backupData['backup_dir'] ?? '')) ?></code></p>
            <p><strong>SQL backups:</strong> <?= esc((string) ($backupData['file_count'] ?? 0)) ?></p>

            <div style="display:flex; flex-wrap:wrap; gap:10px; margin:16px 0;">
                <form method="post" action="<?= base_url('sysadmin/backup/create') ?>" style="margin:0;">
                    <?= csrf_field() ?>
                    <button type="submit" class="login-button" style="display:inline-flex; width:auto; padding:10px 22px;">Create backup now</button>
                </form>
            </div>
            <p style="color:#558b2f; font-size:0.9rem; margin:0 0 14px;">Creates a full MySQL dump (<code>ajesdb_YYYYMMDD_HHMMSS.sql</code>) with all tables and data.</p>

            <?php $files = (array) ($backupData['files'] ?? []); ?>
            <?php if ($files === []): ?>
                <p style="color:#666;">No SQL backups yet. Click <strong>Create backup now</strong> to generate one.</p>
            <?php else: ?>
                <table class="recent-table" style="margin-top:8px;">
                    <thead><tr><th>File</th><th>Size</th><th>Modified</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($files as $f): ?>
                            <tr>
                                <td><code><?= esc((string) ($f['name'] ?? '')) ?></code></td>
                                <td><?= esc((string) ($f['size'] ?? '')) ?></td>
                                <td><?= esc((string) ($f['modified_at'] ?? '')) ?></td>
                                <td>
                                    <a href="<?= base_url('sysadmin/backup/download/' . rawurlencode((string) ($f['name'] ?? ''))) ?>" style="color:#2e7d32; font-weight:600;">Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top:20px; padding:16px; background:#fff8e1; border:1px solid #ffe082; border-radius:8px;">
                    <h3 style="margin:0 0 10px; color:#e65100; font-size:1rem;">Restore database</h3>
                    <p style="margin:0 0 12px; color:#555; font-size:0.9rem;">This replaces all current data in the database with the selected backup. A safety backup is taken first if checked.</p>
                    <form method="post" action="<?= base_url('sysadmin/backup/restore') ?>">
                        <?= csrf_field() ?>
                        <div style="margin-bottom:10px;">
                            <label for="dump_file" style="display:block; font-weight:600; color:#1b5e20; margin-bottom:6px;">Backup file</label>
                            <select id="dump_file" name="dump_file" required style="width:100%; max-width:420px; padding:10px; border:1px solid #c8e6c9; border-radius:8px;">
                                <?php foreach ($files as $f): ?>
                                    <option value="<?= esc((string) ($f['name'] ?? '')) ?>"><?= esc((string) ($f['name'] ?? '')) ?> (<?= esc((string) ($f['size'] ?? '')) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <label style="display:block; margin-bottom:10px;">
                            <input type="checkbox" name="pre_backup" value="1" checked> Take a safety backup before restore
                        </label>
                        <div style="margin-bottom:10px;">
                            <label for="confirm" style="display:block; font-weight:600; color:#c62828; margin-bottom:6px;">Type <strong>YES</strong> to confirm</label>
                            <input type="text" id="confirm" name="confirm" placeholder="YES" autocomplete="off" style="width:120px; padding:10px; border:1px solid #ef9a9a; border-radius:8px;">
                        </div>
                        <button type="submit" class="login-button" style="display:inline-flex; width:auto; padding:10px 22px; background:#c62828;" onclick="return confirm('Restore will overwrite the live database. Continue?');">Restore from backup</button>
                    </form>
                </div>
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
