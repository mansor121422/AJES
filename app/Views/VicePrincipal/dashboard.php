<?php
$role = 'VICE_PRINCIPAL';
$name = session()->get('name') ?? 'Vice Principal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Vice Principal Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Dashboard</h1>

    <div class="welcome-card">
        <div class="card-title">Welcome back, <?= esc($name) ?>!</div>
        <p>Your role: <strong><?= esc($role) ?></strong>. You can review announcements, monitor communication, and oversee reports.</p>
    </div>

    <div class="card">
        <div class="card-title">Quick Access</div>
        <p style="margin-bottom: 10px; color: #558b2f;">Use your assigned privileges to open announcements, reports, chat monitoring, and messaging.</p>
        <a href="<?= base_url('announcements') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-right: 8px;">Announcements</a>
        <a href="<?= base_url('records') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-right: 8px;">Reports</a>
        <a href="<?= base_url('admin/chat-logs') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-right: 8px;">Chat Monitoring</a>
        <a href="<?= base_url('chat') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none;">Open Chat</a>
    </div>

    </main>
</div>
</body>
</html>
