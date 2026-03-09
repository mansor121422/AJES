<?php
$role          = $role ?? 'ADMIN';
$name          = $name ?? 'User';
$notifications = $notifications ?? [];
$isAdmin       = (strtoupper($role) === 'ADMIN');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Notifications</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php
    $hasUnread = false;
    foreach ($notifications as $n) {
        if (empty($n['is_read'])) { $hasUnread = true; break; }
    }
    ?>
    <div class="card">
        <div class="card-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <span>Your notifications</span>
            <?php if ($hasUnread): ?>
                <a href="<?= base_url('notifications/mark-all-read') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 8px 16px; text-decoration: none; font-size: 0.9rem;">Mark all as read</a>
            <?php endif; ?>
        </div>
        <?php if (empty($notifications)): ?>
            <p style="color: #558b2f;">No notifications yet.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($notifications as $n): ?>
                    <?php
                    $isRead = ! empty($n['is_read']);
                    $url    = (($n['type'] ?? '') === 'censored_chat' && $isAdmin) ? base_url('admin/chat-logs') : base_url('notifications');
                    ?>
                    <li style="padding: 12px 0; border-bottom: 1px solid #e8f5e9; <?= $isRead ? '' : 'background: #f1f8e9; margin: 0 -20px; padding-left: 20px; padding-right: 20px;' ?>">
                        <a href="<?= $url ?>" style="color: #1b5e20; text-decoration: none; font-weight: <?= $isRead ? '400' : '600' ?>;">
                            <?= esc($n['message'] ?? '') ?>
                        </a>
                        <div style="font-size: 0.8rem; color: #558b2f; margin-top: 4px;">
                            <?= esc($n['created_at'] ?? '') ?>
                            <?php if (($n['type'] ?? '') === 'censored_chat' && $isAdmin): ?>
                                &nbsp;·&nbsp;<a href="<?= base_url('admin/chat-logs') ?>" class="link-details">View Chat Logs</a>
                            <?php endif; ?>
                            <?php if (! $isRead): ?>
                                &nbsp;·&nbsp;<a href="<?= base_url('notifications/mark-read/' . (int) $n['id']) ?>" class="link-details" style="font-weight: 600;">Mark as read</a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
