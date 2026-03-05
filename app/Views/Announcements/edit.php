<?php
$announcement = $announcement ?? [];
$role = $role ?? 'ADMIN';
$name = $name ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit announcement - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Edit announcement</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Announcement #<?= esc($announcement['id']) ?></div>
        <form action="<?= base_url('announcements/update/' . $announcement['id']) ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="title" style="color: #1b5e20;">Title</label>
                <input type="text" id="title" name="title" required value="<?= esc($announcement['title']) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="body" style="color: #1b5e20;">Body</label>
                <textarea id="body" name="body" rows="6" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;"><?= esc($announcement['body']) ?></textarea>
            </div>
            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 24px;">Update</button>
            <a href="<?= base_url('announcements') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    </main>
</div>
</body>
</html>
