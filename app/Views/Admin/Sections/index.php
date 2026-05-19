<?php
$sections = $sections ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sections - AJES Admin</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Sections</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">All sections</div>
        <p style="margin-bottom: 12px; color: #558b2f;">Create sections, enroll students (maximum <?= \App\Libraries\SectionEnrollment::MAX_STUDENTS ?> per section), and invite teachers.</p>
        <a href="<?= base_url('admin/sections/create') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-bottom: 16px;">Create section</a>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Grade level</th>
                    <th>Teacher</th>
                    <th>School day (times)</th>
                    <th>Students</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sections)): ?>
                    <tr><td colspan="7">No sections yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($sections as $s): ?>
                        <tr>
                            <td><?= esc($s['id']) ?></td>
                            <td><?= esc($s['name']) ?></td>
                            <td><?= esc($s['grade_level']) ?></td>
                            <td><?= esc($s['adviser_name'] ?? '—') ?></td>
                            <td style="font-size: 0.85rem; color: #555;"><?= esc($s['schedule_time_summary'] ?? '—') ?></td>
                            <td>
                                <?php $sc = (int) ($s['student_count'] ?? 0); ?>
                                <a href="<?= base_url('admin/sections/' . $s['id'] . '/students') ?>" class="link-details"><?= $sc ?> / <?= \App\Libraries\SectionEnrollment::MAX_STUDENTS ?></a>
                            </td>
                            <td>
                                <a href="<?= base_url('admin/sections/' . $s['id'] . '/students') ?>" class="link-details">View students</a>
                                &nbsp;|&nbsp;
                                <a href="<?= base_url('admin/sections/' . $s['id'] . '/teachers') ?>" class="link-details">Invite teachers</a>
                                &nbsp;|&nbsp;
                                <a href="<?= base_url('admin/sections/edit/' . $s['id']) ?>" class="link-details">Edit</a>
                                &nbsp;|&nbsp;
                                <a href="<?= base_url('admin/sections/delete/' . $s['id']) ?>" class="link-details" onclick="return confirm('Delete this section?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    </main>
</div>
</body>
</html>
