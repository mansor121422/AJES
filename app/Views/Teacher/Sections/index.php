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
        <div class="card-title">Section invites (accept to add students)</div>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Grade</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invites as $inv): ?>
                    <tr>
                        <td><?= esc($inv['section_name'] ?? '') ?></td>
                        <td><?= esc($inv['grade_level'] ?? '') ?></td>
                        <td>
                            <a href="<?= base_url('teacher/sections/accept/' . $inv['id']) ?>" class="login-button" style="display: inline-flex; padding: 6px 14px; text-decoration: none;">Accept</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Your sections</div>
        <p style="margin-bottom: 12px; color: #558b2f;">Add students to a section (only students who have a record from Guidance can be added).</p>
        <?php if (empty($accepted)): ?>
            <p>No sections yet. Accept an invite above or ask Admin to assign you.</p>
        <?php else: ?>
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Grade</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accepted as $a): ?>
                        <tr>
                            <td><?= esc($a['section_name'] ?? '') ?></td>
                            <td><?= esc($a['grade_level'] ?? '') ?></td>
                            <td>
                                <a href="<?= base_url('teacher/sections/' . $a['section_id'] . '/students') ?>" class="link-details">Manage students</a>
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
