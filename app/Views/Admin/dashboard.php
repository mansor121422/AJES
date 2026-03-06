<?php
$role                 = $role ?? 'ADMIN';
$name                 = $name ?? session()->get('name') ?? 'Administrator';
$total_users          = $total_users ?? 0;
$announcements_today  = $announcements_today ?? 0;
$active_modules       = $active_modules ?? 4;
$recent_announcements = $recent_announcements ?? [];
$authors              = $authors ?? [];
$activity_chart       = $activity_chart ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Admin Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .activity-bars { display: flex; align-items: flex-end; gap: 8px; height: 140px; padding: 12px 0; }
        .activity-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; min-width: 0; height: 100%; }
        .activity-bar { width: 100%; max-width: 36px; min-height: 4px; margin-top: auto; background: #c8e6c9; border-radius: 4px 4px 0 0; }
        .activity-bar-wrap .label { font-size: 0.7rem; color: #666; margin-top: 4px; text-align: center; }
    </style>
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
                <div class="kpi-value"><?= (int) $total_users ?></div>
                <div class="kpi-meta">Registered in system</div>
            </div>
            <div class="kpi-progress" style="--pct: <?= min(100, ($total_users ? (($total_users / 50) * 100) : 0)) ?>%;"></div>
        </div>
        <div class="kpi-card kpi-sage">
            <div class="kpi-body">
                <h3>Announcements Today</h3>
                <div class="kpi-value"><?= (int) $announcements_today ?></div>
                <div class="kpi-meta">Last 24 hours</div>
            </div>
            <div class="kpi-progress" style="--pct: <?= min(100, ($announcements_today ? (($announcements_today / 20) * 100) : 0)) ?>%;"></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-body">
                <h3>Active Modules</h3>
                <div class="kpi-value"><?= (int) $active_modules ?></div>
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
                        <?php if (empty($recent_announcements)): ?>
                            <tr><td colspan="5">No announcements yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_announcements as $a): ?>
                                <?php
                                $authorId = (int) ($a['created_by'] ?? 0);
                                $authorName = $authors[$authorId] ?? '—';
                                $created = $a['created_at'] ?? '';
                                $status = $a['status'] ?? 'ACTIVE';
                                $statusClass = 'status-badge-active';
                                if ($status === 'PUBLISHED' || $status === 'Published') $statusClass = 'status-badge-published';
                                if ($status === 'DELIVERED' || $status === 'Delivered') $statusClass = 'status-badge-delivered';
                                ?>
                                <tr>
                                    <td><?= esc($a['title'] ?? '') ?></td>
                                    <td><?= esc($authorName) ?></td>
                                    <td><?= $created ? date('M j, Y g:i A', strtotime($created)) : '—' ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= esc($status) ?></span></td>
                                    <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div>
            <div class="updates-card">
                <div class="card-title">Updates</div>
                <p style="padding: 12px 16px; color: #666; font-size: 0.9rem;">Recent activity is reflected in Recent Announcements and Activity Overview below.</p>
            </div>
            <div class="graph-card">
                <div class="card-title">Activity Overview</div>
                <p style="margin-bottom: 8px; font-size: 0.85rem; color: #666;">Announcements created per day (last 7 days)</p>
                <?php
                $maxCount = 1;
                foreach ($activity_chart as $d) {
                    if ((int) $d['count'] > $maxCount) $maxCount = (int) $d['count'];
                }
                ?>
                <div class="activity-bars">
                    <?php foreach ($activity_chart as $d): ?>
                        <?php $barHeight = $maxCount > 0 ? round(((int) $d['count']) / $maxCount * 100) : 0; if ($barHeight > 0 && $barHeight < 8) $barHeight = 8; ?>
                        <div class="activity-bar-wrap">
                            <div class="activity-bar" style="height: <?= $barHeight ?>px;"></div>
                            <span class="label"><?= esc($d['label']) ?></span>
                            <span class="label"><?= (int) $d['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    </main>
</div>
</body>
</html>
