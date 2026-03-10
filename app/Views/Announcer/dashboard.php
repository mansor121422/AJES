<?php
$role = 'ANNOUNCER';
$name = session()->get('name') ?? 'Announcer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Announcer Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Dashboard</h1>

    <div class="welcome-card">
        <div class="card-title">Welcome back, <?= esc($name) ?>!</div>
        <p>Create and manage school announcements. Your role: <strong><?= esc($role) ?></strong>.</p>
    </div>

    <div class="kpi-row">
        <div class="kpi-card kpi-mint">
            <div class="kpi-body">
                <h3>Sent Today</h3>
                <div class="kpi-value">5</div>
                <div class="kpi-meta">Announcements delivered</div>
            </div>
            <div class="kpi-progress" style="--pct: 65%;"></div>
        </div>
        <div class="kpi-card kpi-sage">
            <div class="kpi-body">
                <h3>Scheduled</h3>
                <div class="kpi-value">3</div>
                <div class="kpi-meta">Waiting to go out</div>
            </div>
            <div class="kpi-progress" style="--pct: 40%;"></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-body">
                <h3>Drafts</h3>
                <div class="kpi-value">2</div>
                <div class="kpi-meta">Finish and publish</div>
            </div>
            <div class="kpi-icon">📝</div>
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
                <div class="card-title">Recent Announcements</div>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Target</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>School Assembly Schedule</td>
                            <td>School-wide</td>
                            <td>Today, 8:00 AM</td>
                            <td><span class="status-badge status-badge-published">Published</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                        <tr>
                            <td>Lunch Menu Update</td>
                            <td>All grades</td>
                            <td>Yesterday</td>
                            <td><span class="status-badge status-badge-delivered">Delivered</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                        </tr>
                        <tr>
                            <td>Parent-Teacher Meeting</td>
                            <td>Grade 3–6</td>
                            <td>Draft</td>
                            <td><span class="status-badge status-badge-draft">Draft</span></td>
                            <td><a href="<?= base_url('announcements') ?>" class="link-details">Edit</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <div class="card-title">Quick action</div>
                <p style="margin-bottom: 12px; color: #558b2f; font-size: 0.9rem;">Create a new announcement to send to students, teachers, or the whole school.</p>
                <a href="<?= base_url('announcements') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; margin-top: 0;">Create Announcement</a>
            </div>
        </div>
        <div>
            <div class="updates-card">
                <div class="card-title">Updates</div>
                <div class="updates-item">
                    <div class="updates-avatar">📢</div>
                    <div>
                        <div class="updates-text">"School Assembly" was sent to all recipients.</div>
                        <div class="updates-time">30 minutes ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">👁</div>
                    <div>
                        <div class="updates-text">Lunch Menu Update – 95% read rate.</div>
                        <div class="updates-time">2 hours ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">📅</div>
                    <div>
                        <div class="updates-text">Scheduled: "Sports Day" for tomorrow 9 AM.</div>
                        <div class="updates-time">Yesterday</div>
                    </div>
                </div>
            </div>
            <div class="graph-card">
                <div class="card-title">Delivery Overview</div>
                <div class="graph-placeholder">Chart: Announcements sent vs read (connect your data)</div>
            </div>
        </div>
    </div>

    </main>
</div>
</body>
</html>
