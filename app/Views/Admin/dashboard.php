<?php
$role = 'ADMIN';
$name = session()->get('name') ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Admin Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Dashboard</h1>

    <div class="welcome-card">
        <div class="card-title">Welcome back, <?= esc($name) ?>!</div>
        <p>You are logged in as <strong><?= esc($role) ?></strong>. Manage users, announcements, and system settings here.</p>
    </div>

    <div class="kpi-row">
        <div class="kpi-card kpi-mint">
            <div class="kpi-body">
                <h3>Total Users</h3>
                <div class="kpi-value">24</div>
                <div class="kpi-meta">Registered in system</div>
            </div>
            <div class="kpi-progress" style="--pct: 75%;"></div>
        </div>
        <div class="kpi-card kpi-sage">
            <div class="kpi-body">
                <h3>Announcements Today</h3>
                <div class="kpi-value">7</div>
                <div class="kpi-meta">Last 24 hours</div>
            </div>
            <div class="kpi-progress" style="--pct: 89%;"></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-body">
                <h3>Active Modules</h3>
                <div class="kpi-value">4</div>
                <div class="kpi-meta">Announcements, Chat, Records, Users</div>
            </div>
            <div class="kpi-icon">✓</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div>
            <div class="card">
                <div class="card-title">Recent Announcements</div>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>School Assembly Schedule</td>
                            <td>Announcer Staff</td>
                            <td>Today, 8:00 AM</td>
                            <td><span class="status-badge status-badge-published">Published</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                        <tr>
                            <td>Grade 3 Reading Activity</td>
                            <td>Sample Teacher</td>
                            <td>Yesterday</td>
                            <td><span class="status-badge status-badge-active">Active</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                        <tr>
                            <td>Emergency Drill Notice</td>
                            <td>Elementary Principal</td>
                            <td>Mar 4, 2026</td>
                            <td><span class="status-badge status-badge-delivered">Delivered</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div>
            <div class="updates-card">
                <div class="card-title">Updates</div>
                <div class="updates-item">
                    <div class="updates-avatar">👤</div>
                    <div>
                        <div class="updates-text">New user registered (Teacher).</div>
                        <div class="updates-time">25 seconds ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">📢</div>
                    <div>
                        <div class="updates-text">Announcement "School Assembly" was published.</div>
                        <div class="updates-time">30 minutes ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">📁</div>
                    <div>
                        <div class="updates-text">Records module accessed by Guidance.</div>
                        <div class="updates-time">2 hours ago</div>
                    </div>
                </div>
            </div>
            <div class="graph-card">
                <div class="card-title">Activity Overview</div>
                <div class="graph-placeholder">Chart: Announcements & logins over time (connect your data)</div>
            </div>
        </div>
    </div>

    </main>
</div>
</body>
</html>
