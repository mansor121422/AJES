<?php
$role = 'TEACHER';
$name = session()->get('name') ?? 'Teacher';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Teacher Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Dashboard</h1>

    <div class="welcome-card">
        <div class="card-title">Welcome back, <?= esc($name) ?>!</div>
        <p>Your role: <strong><?= esc($role) ?></strong>. Manage your sections, announcements, and messages here.</p>
    </div>

    <div class="kpi-row">
        <div class="kpi-card kpi-mint">
            <div class="kpi-body">
                <h3>My Sections</h3>
                <div class="kpi-value">2</div>
                <div class="kpi-meta">Assigned classes</div>
            </div>
            <div class="kpi-progress" style="--pct: 66%;"></div>
        </div>
        <div class="kpi-card kpi-sage">
            <div class="kpi-body">
                <h3>Unread Messages</h3>
                <div class="kpi-value">3</div>
                <div class="kpi-meta">From Chat</div>
            </div>
            <div class="kpi-progress" style="--pct: 30%;"></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-body">
                <h3>Active Announcements</h3>
                <div class="kpi-value">5</div>
                <div class="kpi-meta">Relevant to your sections</div>
            </div>
            <div class="kpi-icon">📢</div>
        </div>
    </div>

    <!-- React: animated cards (Framer Motion) -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-title">Quick highlights</div>
        <div data-react-component="cards"></div>
    </div>

    <div class="dashboard-grid">
        <div>
            <div class="card">
                <div class="card-title">Recent Announcements (for your sections)</div>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Section</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Grade 3 – Reading Activity Reminder</td>
                            <td>Grade 3-A</td>
                            <td>Today</td>
                            <td><span class="status-badge status-badge-active">Active</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                        <tr>
                            <td>School Assembly Schedule</td>
                            <td>School-wide</td>
                            <td>Today</td>
                            <td><span class="status-badge status-badge-published">Published</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                        <tr>
                            <td>Homework Submission</td>
                            <td>Grade 3-B</td>
                            <td>Yesterday</td>
                            <td><span class="status-badge status-badge-delivered">Delivered</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <div class="card-title">Quick Stats</div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Active Announcements</div>
                        <div class="stat-value">5</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Unread Messages</div>
                        <div class="stat-value">3</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Assigned Sections</div>
                        <div class="stat-value">2</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Records Updated Today</div>
                        <div class="stat-value">4</div>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="updates-card">
                <div class="card-title">Recent Messages</div>
                <div class="updates-item">
                    <div class="updates-avatar">💬</div>
                    <div>
                        <div class="updates-text">Guidance: Reminder – counseling schedule for Grade 3-A.</div>
                        <div class="updates-time">25 minutes ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">👤</div>
                    <div>
                        <div class="updates-text">Parent inquiry in Chat (Grade 3-B).</div>
                        <div class="updates-time">1 hour ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">📢</div>
                    <div>
                        <div class="updates-text">New school-wide announcement: Assembly.</div>
                        <div class="updates-time">2 hours ago</div>
                    </div>
                </div>
            </div>
            <div class="graph-card">
                <div class="card-title">Section Activity</div>
                <div class="graph-placeholder">Chart: Announcements & messages by section (connect your data)</div>
            </div>
        </div>
    </div>

    </main>
</div>
</body>
</html>
