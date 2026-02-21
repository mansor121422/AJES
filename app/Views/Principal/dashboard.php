<?php
    $role = 'PRINCIPAL';
    $name = session()->get('name') ?? 'Principal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Principal Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <div class="topbar" aria-label="AJES principal dashboard top navigation">
        <div class="topbar-left">
            AJES CRIER
        </div>
        <div class="topbar-right">
            <div class="icon-button" aria-label="Notifications">
                🔔
                <span class="icon-badge">4</span>
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
                <a href="<?= base_url('dashboard/principal') ?>">Dashboard Home</a>
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
                <div class="card-title">School Overview</div>
                <p style="font-size: 13px; color: #555;">A summary of announcements and staff activity can be displayed here.</p>
            </div>
        </div>
    </div>
</body>
</html>
