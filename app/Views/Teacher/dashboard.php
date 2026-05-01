<?php
$role = $role ?? 'TEACHER';
$name = $name ?? (session()->get('name') ?? 'Teacher');
$assignedSectionsCount = (int) ($assigned_sections_count ?? 0);
$unreadMessages = (int) ($unread_messages ?? 0);
$activeAnnouncements = (int) ($active_announcements ?? 0);
$recordsUpdatedToday = (int) ($records_updated_today ?? 0);
$recentAnnouncements = $recent_announcements ?? [];
$recentMessages = $recent_messages ?? [];
$sectionActivity = $section_activity ?? [];
$kpiSectionsPct = $kpi_sections_pct ?? 66;
$kpiUnreadPct = $kpi_unread_pct ?? 30;
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
                <div class="kpi-value"><?= esc((string) $assignedSectionsCount) ?></div>
                <div class="kpi-meta">Assigned classes</div>
            </div>
            <div class="kpi-progress" style="--pct: <?= esc((string) $kpiSectionsPct) ?>%;"></div>
        </div>
        <div class="kpi-card kpi-sage">
            <div class="kpi-body">
                <h3>Unread Messages</h3>
                <div class="kpi-value"><?= esc((string) $unreadMessages) ?></div>
                <div class="kpi-meta">From Chat</div>
            </div>
            <div class="kpi-progress" style="--pct: <?= esc((string) $kpiUnreadPct) ?>%;"></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-body">
                <h3>Active Announcements</h3>
                <div class="kpi-value"><?= esc((string) $activeAnnouncements) ?></div>
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
                        <?php if (empty($recentAnnouncements)): ?>
                            <tr>
                                <td colspan="5">No announcements yet for your scope (school-wide or your assigned sections).</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAnnouncements as $a): ?>
                                <tr>
                                    <td><?= esc($a['title'] ?? '') ?></td>
                                    <td><?= esc($a['section_label'] ?? '—') ?></td>
                                    <td><?= esc($a['date_label'] ?? '') ?></td>
                                    <td><span class="status-badge <?= esc($a['status_class'] ?? 'status-badge-active') ?>"><?= esc($a['status_label'] ?? 'Active') ?></span></td>
                                    <td><a href="<?= base_url('announcements') ?>" class="link-details">Details</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <div class="card-title">Quick Stats</div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Active Announcements</div>
                        <div class="stat-value"><?= esc((string) $activeAnnouncements) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Unread Messages</div>
                        <div class="stat-value"><?= esc((string) $unreadMessages) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Assigned Sections</div>
                        <div class="stat-value"><?= esc((string) $assignedSectionsCount) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Records Updated Today</div>
                        <div class="stat-value"><?= esc((string) $recordsUpdatedToday) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="updates-card">
                <div class="card-title">Recent Messages</div>
                <?php if (empty($recentMessages)): ?>
                    <div class="updates-item">
                        <div class="updates-avatar">💬</div>
                        <div>
                            <div class="updates-text">No chat messages yet. Open Chat to start a conversation.</div>
                            <div class="updates-time">—</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentMessages as $rm): ?>
                        <div class="updates-item">
                            <div class="updates-avatar"><?= ! empty($rm['from_me']) ? '📤' : '💬' ?></div>
                            <div>
                                <div class="updates-text"><?= esc($rm['text'] ?? '') ?></div>
                                <div class="updates-time"><?= esc($rm['time_ago'] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <p style="margin: 12px 0 0; font-size: 0.85rem;"><a href="<?= base_url('chat') ?>" class="link-details">Open Chat</a></p>
            </div>
            <div class="graph-card">
                <div class="card-title">Section Activity</div>
                <?php if (empty($sectionActivity)): ?>
                    <div class="graph-placeholder">No sections assigned yet. Accept invitations under My Sections when your admin adds you.</div>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.95rem;">
                        <?php foreach ($sectionActivity as $s): ?>
                            <li style="padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,0.06);">
                                <strong><?= esc($s['section_name'] ?? '') ?></strong>
                                <?php if (($s['grade_level'] ?? '') !== ''): ?>
                                    <span style="color:#558b2f;"> · Grade <?= esc((string) $s['grade_level']) ?></span>
                                <?php endif; ?>
                                <div style="font-size: 0.9rem; color: #666; margin-top: 4px;">
                                    <?= (int) ($s['student_count'] ?? 0) ?> student<?= ((int) ($s['student_count'] ?? 0)) === 1 ? '' : 's' ?> in this section
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    </main>
</div>
</body>
</html>
