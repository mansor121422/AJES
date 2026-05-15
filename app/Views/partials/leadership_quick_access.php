<?php
use App\Libraries\AdminPrivilege;

$role = strtoupper((string) ($role ?? session()->get('role') ?? ''));
$name = $name ?? session()->get('name') ?? 'User';
$roleLabel = $role_label ?? $role;
$subtitle = $subtitle ?? 'You can review announcements, monitor communication, and oversee reports.';
$granted = AdminPrivilege::effectiveForRole($role, session()->get('feature_privileges') ?? session()->get('admin_privileges'));
$hasFull = $granted === [];
$can = static fn (string $key): bool => $hasFull || in_array($key, $granted, true);
?>
<div class="welcome-card">
    <div class="card-title">Welcome back, <?= esc($name) ?>!</div>
    <p>Your role: <strong><?= esc($roleLabel) ?></strong>. <?= esc($subtitle) ?></p>
</div>

<div class="card">
    <div class="card-title">Quick Access</div>
    <p style="margin-bottom: 10px; color: #558b2f;">Use your assigned privileges to open announcements, reports, chat monitoring, and messaging.</p>
    <?php if ($can('announcements')): ?>
        <a href="<?= base_url('announcements') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-right: 8px;">Announcements</a>
    <?php endif; ?>
    <?php if ($can('records')): ?>
        <a href="<?= base_url('records') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-right: 8px;">Reports</a>
    <?php endif; ?>
    <?php if ($can('chat_logs')): ?>
        <a href="<?= base_url('chatlogs') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-right: 8px;">Chat Monitoring</a>
    <?php endif; ?>
    <a href="<?= base_url('chat') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none;">Open Chat</a>
</div>
