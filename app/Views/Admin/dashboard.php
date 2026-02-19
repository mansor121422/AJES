<?php
    $role = 'ADMIN';
    $name = session()->get('name') ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }
        .layout {
            display: flex;
            min-height: 100vh;
        }
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background-color: #2d5f3a;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
        }
        .topbar-left {
            font-weight: bold;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .badge {
            background-color: #f9d71c;
            color: #2d5f3a;
            padding: 2px 6px;
            font-size: 12px;
            border-radius: 3px;
        }
        .icon-button {
            position: relative;
            cursor: pointer;
        }
        .icon-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background-color: #d32f2f;
            color: #ffffff;
            border-radius: 50%;
            font-size: 10px;
            padding: 2px 5px;
        }
        .sidebar {
            width: 220px;
            background-color: #f0f0f0;
            padding: 72px 12px 12px;
            box-sizing: border-box;
        }
        .menu a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            margin-bottom: 4px;
            font-size: 14px;
        }
        .menu a:hover {
            background-color: #ddd;
        }
        .content {
            flex: 1;
            padding: 72px 16px 16px;
            box-sizing: border-box;
        }
        .card {
            background-color: #ffffff;
            border: 1px solid $ddd;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .card-title {
            font-size: 16px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="topbar" aria-label="AJES admin dashboard top navigation">
        <div class="topbar-left">
            AJES CRIER
        </div>
        <div class="topbar-right">
            <div class="icon-button" aria-label="Notifications">
                🔔
                <span class="icon-badge">3</span>
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
                <a href="<?= base_url('dashboard/admin') ?>">Dashboard Home</a>
                <a href="<?= base_url('announcements') ?>">Announcements</a>
                <a href="<?= base_url('chat') ?>">Chat</a>
                <a href="<?= base_url('records') ?>">Records</a>
                <a href="<?= base_url('admin/users') ?>">User Management</a>
            </div>
        </div>

        <div class="content">
            <div class="card">
                <div class="card-title">
                    Welcome back, <?= esc($name) ?>!
                </div>
                <div>
                    You are logged in as <strong><?= esc($role) ?></strong>.
                </div>
            </div>

            <div class="card">
                <div class="card-title">System Overview</div>
                <p style="font-size: 13px; color: #555;">Here you can manage users, review logs, and access announcements, chat, and records modules.</p>
            </div>
        </div>
    </div>
</body>
</html>
