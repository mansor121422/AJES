<?php
    $role = 'STUDENT';
    $name = session()->get('name') ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Student Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <div class="topbar" aria-label="AJES student dashboard top navigation">
        <div class="topbar-left">
            AJES CRIER
        </div>
        <div class="topbar-right">
            <div class="icon-button" aria-label="Notifications">
                🔔
                <span class="icon-badge">2</span>
            </div>
            <div>
                <?= esc($name) ?> <span class="badge"><?= esc($role) ?></span>
            </div>
            <a href="<?= base_url('auth/logout') ?>" style="color: #ffffff; text-decoration: none;">Logout</a>
        </div>
    </div>

    <div class="layout">
        <div class="sidebar" aria-label="Sidebar navigation">
            <div class="menu">
                <a href="<?= base_url('dashboard/student') ?>">Dashboard Home</a>
                <a href="<?= base_url('announcements') ?>">Announcements</a>
                <a href="<?= base_url('chat') ?>">Chat</a>
            </div>
        </div>

        <div class="content">
            <div class="card">
                <div class="card-title">
                    Welcome back, <?= esc($name) ?>!
                </div>
                <div>
                    Your role: <strong><?= esc($role) ?></strong>
                </div>
            </div>

            <div class="card">
                <div class="card-title">Announcements</div>
                <p style="font-size: 13px; color: #555;">Announcements for your grade and section will appear here.</p>
            </div>

            <div class="card">
                <div class="card-title">Messages</div>
                <p style="font-size: 13px; color: #555;">Messages from your teachers and guidance office will appear here.</p>
            </div>
        </div>
    </div>
</body>
</html>
