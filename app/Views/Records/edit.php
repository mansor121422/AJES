<?php
$role    = $role ?? 'ADMIN';
$name    = $name ?? 'User';
$record  = $record ?? [];
$students = $students ?? [];
$recordTypes = $recordTypes ?? ['Session', 'Note', 'Referral', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit record - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Edit student record</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Record #<?= esc($record['id']) ?></div>
        <form action="<?= base_url('records/update/' . $record['id']) ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="student_id" style="color: #1b5e20;">Student</label>
                <select id="student_id" name="student_id" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">Select student</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) ($record['student_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['id']) ?> — <?= esc($s['name'] ?? $s['username'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="type" style="color: #1b5e20;">Type</label>
                <select id="type" name="type" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <?php foreach ($recordTypes as $rt): ?>
                        <option value="<?= esc($rt) ?>" <?= ($record['type'] ?? '') === $rt ? 'selected' : '' ?>><?= esc($rt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="details" style="color: #1b5e20;">Details</label>
                <textarea id="details" name="details" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;"><?= esc($record['details']) ?></textarea>
            </div>
            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 24px;">Update</button>
            <a href="<?= base_url('records') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    </main>
</div>
</body>
</html>
