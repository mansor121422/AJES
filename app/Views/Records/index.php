<?php
$typeList   = $typeList ?? [];
$typeFilter = $typeFilter ?? '';
$keyword    = $keyword ?? '';
$recordTypes = $recordTypes ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Student records</h1>

    <div class="card">
        <p style="color: #558b2f; font-size: 0.95rem; margin-bottom: 0;">
            <strong>What this is for:</strong> Log counseling and session notes for students. Guidance and Admin use this to record which students have been seen. Students with at least one record can be assigned to a section by teachers.
        </p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Search & filter</div>
        <form method="get" action="<?= base_url('records') ?>" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label for="q" style="display: block; font-size: 0.85rem; color: #2e7d32; margin-bottom: 4px;">Search (type or details)</label>
                <input type="text" id="q" name="q" placeholder="Search..." value="<?= esc($keyword) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div style="min-width: 140px;">
                <label for="type" style="display: block; font-size: 0.85rem; color: #2e7d32; margin-bottom: 4px;">Type</label>
                <select id="type" name="type" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">All types</option>
                    <?php foreach ($typeList as $t): ?>
                        <option value="<?= esc($t) ?>" <?= $typeFilter === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px;">Search</button>
            <a href="<?= base_url('records/create') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none;">Create record</a>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Records list</div>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Details</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="6">No records yet. Create one to log a student session or note.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= esc($record['id']) ?></td>
                            <td><?= esc($record['student_id']) ?></td>
                            <td><?= esc($record['type']) ?></td>
                            <td><?= esc(character_limiter($record['details'], 60)) ?></td>
                            <td><?= esc($record['created_at']) ?></td>
                            <td>
                                <a href="<?= base_url('records/edit/' . $record['id']) ?>" class="link-details">Edit</a>
                                &nbsp;|&nbsp;
                                <a href="<?= base_url('records/delete/' . $record['id']) ?>" class="link-details" onclick="return confirm('Delete this record?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if (isset($pager) && $pager->getPageCount() > 1): ?>
            <div style="margin-top: 16px;">
                <?= $pager->links() ?>
            </div>
        <?php endif; ?>
    </div>

    </main>
</div>
</body>
</html>
