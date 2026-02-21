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
            <div class="card">
                <div class="card-title">
                    Welcome back, <?= esc($name) ?>!
                </div>
                <div>
                    Your role: <strong><?= esc($role) ?></strong>
                </div>
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

            <div class="card">
                <div class="card-title">Recent Announcements</div>
                <p style="font-size: 13px; color: #555;">Sample announcement items will appear here.</p>
                <div>
                    <strong>Grade 3 - A Reading Activity Reminder</strong><br>
                    <span class="status-badge">ACTIVE</span>
                </div>
            </div>

            <div class="card">
                <div class="card-title">Recent Messages</div>
                <p style="font-size: 13px; color: #555;">Latest chat messages preview will appear here.</p>
            </div>
    </div>
</div>
</body>
</html>
