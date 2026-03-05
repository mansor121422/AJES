<?php
$role = 'PRINCIPAL';
$name = session()->get('name') ?? 'Elementary Principal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Principal Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Dashboard</h1>

    <div class="welcome-card">
        <div class="card-title">Welcome back, <?= esc($name) ?>!</div>
        <p>Your role: <strong><?= esc($role) ?></strong>. Overview of school announcements and staff activity.</p>
    </div>

    <div class="kpi-row">
        <div class="kpi-card kpi-mint">
            <div class="kpi-body">
                <h3>Announcements This Week</h3>
                <div class="kpi-value">12</div>
                <div class="kpi-meta">School-wide & by section</div>
            </div>
            <div class="kpi-progress" style="--pct: 70%;"></div>
        </div>
        <div class="kpi-card kpi-sage">
            <div class="kpi-body">
                <h3>Staff Active Today</h3>
                <div class="kpi-value">18</div>
                <div class="kpi-meta">Teachers & staff logged in</div>
            </div>
            <div class="kpi-progress" style="--pct: 85%;"></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-body">
                <h3>Pending Review</h3>
                <div class="kpi-value">2</div>
                <div class="kpi-meta">Announcements awaiting approval</div>
            </div>
            <div class="kpi-icon">📋</div>
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
                            <th>From</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>School Assembly Schedule</td>
                            <td>Announcer</td>
                            <td>Today</td>
                            <td><span class="status-badge status-badge-published">Published</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                        <tr>
                            <td>Emergency Drill Notice</td>
                            <td>Principal</td>
                            <td>Mar 4</td>
                            <td><span class="status-badge status-badge-delivered">Delivered</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                        <tr>
                            <td>Parent-Teacher Meeting</td>
                            <td>Announcer</td>
                            <td>Mar 3</td>
                            <td><span class="status-badge status-badge-pending">Pending</span></td>
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
                    <div class="updates-avatar">📢</div>
                    <div>
                        <div class="updates-text">New announcement from Announcer Staff.</div>
                        <div class="updates-time">1 hour ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">👥</div>
                    <div>
                        <div class="updates-text">Guidance Counselor viewed records.</div>
                        <div class="updates-time">2 hours ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">💬</div>
                    <div>
                        <div class="updates-text">Chat activity in Grade 3 section.</div>
                        <div class="updates-time">3 hours ago</div>
                    </div>
                </div>
            </div>
            <div class="graph-card">
                <div class="card-title">Announcement Activity</div>
                <div class="graph-placeholder">Chart: Announcements per day (connect your data)</div>
            </div>
        </div>
    </div>

    </main>
</div>
</body>
</html>
