<?php
$invites  = $invites ?? [];
$accepted = $accepted ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sections - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">My sections</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if (! empty($invites)): ?>
    <div class="card">
        <div class="card-title">Section invites</div>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Grade</th>
                    <th>Role</th>
                    <th>Subject</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invites as $inv): ?>
                    <tr>
                        <td><?= esc($inv['section_name'] ?? '') ?></td>
                        <td><?= esc($inv['grade_level'] ?? '') ?></td>
                        <td><?= esc(($inv['assignment_role'] ?? 'ADVISER') === 'ADVISER' ? 'Adviser' : 'Subject Teacher') ?></td>
                        <td><?= esc($inv['subject_display'] ?? $inv['subject_name'] ?? '—') ?></td>
                        <td style="white-space: nowrap;">
                            <a href="<?= base_url('teacher/sections/accept/' . $inv['id']) ?>" class="login-button" style="display: inline-flex; padding: 6px 14px; text-decoration: none;">Accept</a>
                            <a href="<?= base_url('teacher/sections/leave/' . $inv['id']) ?>" class="link-details" style="margin-left: 10px; color: #c62828;" onclick="return confirm('Decline this invite?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Your sections</div>
        <p style="margin-bottom: 12px; color: #558b2f;">Adviser can manage students. Subject teachers are linked per subject. <strong>Click the section name</strong> to see the full timetable and who teaches each class.</p>
        <?php if (empty($accepted)): ?>
            <p>No sections yet. Accept an invite above or ask Admin to assign you.</p>
        <?php else: ?>
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Grade</th>
                        <th>Role</th>
                        <th>Subject</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accepted as $a): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('teacher/sections/' . (int) ($a['section_id'] ?? 0) . '/schedule') ?>" class="link-details"><?= esc($a['section_name'] ?? '') ?></a>
                            </td>
                            <td><?= esc($a['grade_level'] ?? '') ?></td>
                            <td><?= esc(($a['assignment_role'] ?? 'ADVISER') === 'ADVISER' ? 'Adviser' : 'Subject Teacher') ?></td>
                            <td><?= esc($a['subject_display'] ?? $a['subject_name'] ?? '—') ?></td>
                            <td style="white-space: nowrap;">
                                <?php if (($a['assignment_role'] ?? 'ADVISER') === 'ADVISER' || empty($a['assignment_role'])): ?>
                                    <a href="<?= base_url('teacher/sections/' . $a['section_id'] . '/students') ?>" class="link-details">Manage students</a>
                                <?php else: ?>
                                    <span style="color:#777;">Subject class</span>
                                <?php endif; ?>
                                <a href="<?= base_url('teacher/sections/leave/' . (int) ($a['id'] ?? 0)) ?>" class="link-details" style="margin-left: 10px; color: #c62828;" onclick="return confirm('Remove yourself from this section? You can be re-invited by an admin.')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    </main>
</div>
</body>
</html>
