<?php
$role = $role ?? 'STUDENT';
$name = $name ?? (session()->get('name') ?? 'Student');
$studentSection = $student_section ?? null;
$hasSection = is_array($studentSection) && ! empty($studentSection['id']);
$sectionLabel = $hasSection ? (($studentSection['grade_level'] ?? '') . ' - ' . ($studentSection['name'] ?? '')) : 'Not assigned yet';
$announcementCountWeek = (int) ($announcement_count_week ?? 0);
$announcementCountToday = (int) ($announcement_count_today ?? 0);
$unreadMessages = (int) ($unread_messages ?? 0);
$recentAnnouncements = $recent_announcements ?? [];
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
        <?php if ($hasSection): ?>
            <p>Your role: <strong><?= esc($role) ?></strong>. Your current section is <strong><?= esc($sectionLabel) ?></strong>. Announcements and updates for your section appear here.</p>
        <?php else: ?>
            <p>Your role: <strong><?= esc($role) ?></strong>. You are not assigned to a section yet. Ask your teacher/admin to assign you so section announcements appear here.</p>
        <?php endif; ?>
    </div>

    <div class="kpi-row">
        <div class="kpi-card kpi-mint">
            <div class="kpi-body">
                <h3>Announcements for You</h3>
                <div class="kpi-value"><?= esc((string) $announcementCountWeek) ?></div>
                <div class="kpi-meta"><?= $hasSection ? 'This week (your section)' : 'This week (school-wide)' ?></div>
            </div>
            <div class="kpi-progress" style="--pct: 80%;"></div>
        </div>
        <div class="kpi-card kpi-sage">
            <div class="kpi-body">
                <h3>Unread Messages</h3>
                <div class="kpi-value"><?= esc((string) $unreadMessages) ?></div>
                <div class="kpi-meta">From teachers / guidance</div>
            </div>
            <div class="kpi-progress" style="--pct: 25%;"></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-body">
                <h3>Today</h3>
                <div class="kpi-value"><?= esc((string) $announcementCountToday) ?></div>
                <div class="kpi-meta">New announcements today</div>
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
                        <?php if (empty($recentAnnouncements)): ?>
                            <tr>
                                <td colspan="4"><?= $hasSection ? 'No announcements for your section yet.' : 'No announcements yet.' ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAnnouncements as $a): ?>
                                <tr>
                                    <td><?= esc($a['title'] ?? '') ?></td>
                                    <td><?= esc($a['audience_type'] ?? 'School') ?></td>
                                    <td><?= esc($a['created_at'] ?? '') ?></td>
                                    <td><a href="<?= base_url('announcements') ?>" class="link-details">Read</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
