<?php
$role = 'GUIDANCE';
$name = session()->get('name') ?? 'Guidance Counselor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Guidance Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Dashboard</h1>

    <div class="welcome-card">
        <div class="card-title">Welcome back, <?= esc($name) ?>!</div>
        <p>Your role: <strong><?= esc($role) ?></strong>. View counseling records, announcements, and student-related updates here.</p>
    </div>

    <div class="kpi-row">
        <div class="kpi-card kpi-mint">
            <div class="kpi-body">
                <h3>Records Updated</h3>
                <div class="kpi-value">8</div>
                <div class="kpi-meta">This week</div>
            </div>
            <div class="kpi-progress" style="--pct: 55%;"></div>
        </div>
        <div class="kpi-card kpi-sage">
            <div class="kpi-body">
                <h3>Announcements</h3>
                <div class="kpi-value">6</div>
                <div class="kpi-meta">Relevant to guidance / students</div>
            </div>
            <div class="kpi-progress" style="--pct: 72%;"></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-body">
                <h3>Unread Messages</h3>
                <div class="kpi-value">4</div>
                <div class="kpi-meta">From Chat</div>
            </div>
            <div class="kpi-icon">💬</div>
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
                <div class="card-title">Recent Records Activity</div>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Student / Record</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Grade 3-A – Counseling note</td>
                            <td>Session</td>
                            <td>Today</td>
                            <td><a href="<?= base_url('records') ?>" class="link-details">View</a></td>
                        </tr>
                        <tr>
                            <td>Grade 4-B – Follow-up</td>
                            <td>Record</td>
                            <td>Yesterday</td>
                            <td><a href="<?= base_url('records') ?>" class="link-details">View</a></td>
                        </tr>
                        <tr>
                            <td>Grade 5 – Group session</td>
                            <td>Session</td>
                            <td>Mar 3</td>
                            <td><a href="<?= base_url('records') ?>" class="link-details">View</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <div class="card-title">Guidance Overview</div>
                <p style="font-size: 0.95rem; color: #558b2f;">Manage counseling-related records and view student announcements. Use Records for session notes and Chat to coordinate with teachers and parents.</p>
                <a href="<?= base_url('records') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; margin-top: 12px;">Open Records</a>
            </div>
        </div>
        <div>
            <div class="updates-card">
                <div class="card-title">Updates</div>
                <div class="updates-item">
                    <div class="updates-avatar">📁</div>
                    <div>
                        <div class="updates-text">New counseling session logged (Grade 3-A).</div>
                        <div class="updates-time">1 hour ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">📢</div>
                    <div>
                        <div class="updates-text">Announcement: Counseling schedule for Grade 4.</div>
                        <div class="updates-time">3 hours ago</div>
                    </div>
                </div>
                <div class="updates-item">
                    <div class="updates-avatar">💬</div>
                    <div>
                        <div class="updates-text">Message from Teacher (Grade 3-B – referral).</div>
                        <div class="updates-time">5 hours ago</div>
                    </div>
                </div>
            </div>
            <div class="graph-card">
                <div class="card-title">Records Overview</div>
                <div class="graph-placeholder">Chart: Sessions & records over time (connect your data)</div>
            </div>
        </div>
    </div>

    </main>
</div>
</body>
</html>
