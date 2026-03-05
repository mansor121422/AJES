<?php
$section     = $section ?? [];
$assignments = $assignments ?? [];
$teachers    = $teachers ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite teachers - <?= esc($section['name']) ?></title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header"><?= esc($section['name']) ?> — Invite teachers</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Invite teacher to this section</div>
        <p style="margin-bottom: 12px; color: #558b2f;">Teachers must accept the invite before they can add students to this section.</p>
        <form action="<?= base_url('admin/sections/invite') ?>" method="post" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <?= csrf_field() ?>
            <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
            <div style="min-width: 200px;">
                <label for="teacher_id" style="display: block; font-size: 0.85rem; color: #2e7d32; margin-bottom: 4px;">Teacher</label>
                <select id="teacher_id" name="teacher_id" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">Select teacher</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= esc($t['name']) ?> (<?= esc($t['username']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px;">Send invite</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Teachers in this section</div>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignments)): ?>
                    <tr><td colspan="2">No teachers invited yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><?= esc($a['teacher_name'] ?? $a['username'] ?? '') ?></td>
                            <td>
                                <?php if (($a['status'] ?? '') === 'accepted'): ?>
                                    <span class="status-badge status-badge-approved">Accepted</span>
                                <?php else: ?>
                                    <span class="status-badge status-badge-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p><a href="<?= base_url('admin/sections') ?>" class="link-details">← Back to sections</a></p>

    </main>
</div>
</body>
</html>
