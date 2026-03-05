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
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Dashboard</h1>

    <div class="welcome-card">
        <div class="card-title">Welcome back, <?= esc($name) ?>!</div>
        <p>Your role: <strong><?= esc($role) ?></strong>. Announcements for your grade and section, and messages from teachers and guidance, appear here.</p>
    </div>

    <div class="kpi-row">
        <div class="kpi-card kpi-mint">
            <div class="kpi-body">
                <h3>Announcements for You</h3>
                <div class="kpi-value">9</div>
                <div class="kpi-meta">This week (your section)</div>
            </div>
            <div class="kpi-progress" style="--pct: 80%;"></div>
        </div>
        <div class="kpi-card kpi-sage">
            <div class="kpi-body">
                <h3>Unread Messages</h3>
                <div class="kpi-value">2</div>
                <div class="kpi-meta">From teachers / guidance</div>
            </div>
            <div class="kpi-progress" style="--pct: 25%;"></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-body">
                <h3>Today</h3>
                <div class="kpi-value">3</div>
                <div class="kpi-meta">New announcements today</div>
            </div>
            <div class="kpi-icon">📢</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div>
            <div class="card">
                <div class="card-title">Recent Announcements (your grade & section)</div>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>From</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>School Assembly – Tomorrow 8 AM</td>
                            <td>Announcer</td>
                            <td>Today</td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Read</a></td>
                        </tr>
                        <tr>
                            <td>Grade 3 – Reading Activity Reminder</td>
                            <td>Teacher</td>
                            <td>Today</td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Read</a></td>
                        </tr>
                        <tr>
                            <td>Lunch Menu Update</td>
                            <td>Announcer</td>
                            <td>Yesterday</td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Read</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <div class="card-title">Messages</div>
                <p style="font-size: 0.95rem; color: #558b2f; margin-bottom: 12px;">Messages from your teachers and the guidance office will appear here. Use Chat to reply.</p>
                <a href="<?= base_url('chat') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; margin-top: 0;">Open Chat</a>
            </div>
        </div>
        <div>
            <div class="updates-card">
                <div class="card-title">Updates</div>
                <div class="updates-item">
                    <div class="updates-avatar">📢</div>
                    <div>
                        <div class="updates-text">New announcement: School Assembly tomorrow.</div>
                        <div class="updates-time">30 minutes ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">👩‍🏫</div>
                    <div>
                        <div class="updates-text">Your teacher posted: Reading activity reminder.</div>
                        <div class="updates-time">1 hour ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">💬</div>
                    <div>
                        <div class="updates-text">Guidance office: Counseling schedule for Grade 3.</div>
                        <div class="updates-time">2 hours ago</div>
                    </div>
                </div>
            </div>
            <div class="graph-card">
                <div class="card-title">Your Activity</div>
                <div class="graph-placeholder">Announcements read this week (connect your data)</div>
            </div>
        </div>
    </div>

    </main>
</div>
</body>
</html>
