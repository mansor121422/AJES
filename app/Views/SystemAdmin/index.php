<?php
$role = $role ?? 'SUPER_ADMIN';
$name = $name ?? 'Super Administrator';
$tab = $tab ?? 'settings';
$logCount = (int) ($log_count ?? 0);

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
        <div class="card-title">Super Admin Control</div>
        <p style="margin-bottom: 12px; color: #558b2f;">Welcome, <?= esc($name) ?>. This panel centralizes technical administration tasks.</p>

        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
            <a href="<?= base_url('system/settings') ?>" class="link-details">System Settings</a>
            <a href="<?= base_url('system/chatbot') ?>" class="link-details">Chatbot Management</a>
            <a href="<?= base_url('system/backup') ?>" class="link-details">Backup & Restore</a>
            <a href="<?= base_url('system/security-logs') ?>" class="link-details">Security Logs</a>
        </div>

        <?php if ($tab === 'settings'): ?>
            <p>Configure application-level settings, policies, and environment defaults from this module.</p>
        <?php elseif ($tab === 'chatbot'): ?>
            <p>Manage AI chatbot behavior and responses. Current role-based AI triggers are configured in `app/Config/AIChat.php`.</p>
        <?php elseif ($tab === 'backup'): ?>
            <p>Run and monitor database backup/restore operations. You can wire your backup scripts to this module next.</p>
        <?php elseif ($tab === 'security-logs'): ?>
            <p>Review security and audit-related events. Current available log files detected: <strong><?= esc((string) $logCount) ?></strong>.</p>
        <?php endif; ?>
    </div>

    </main>
</div>
</body>
</html>
