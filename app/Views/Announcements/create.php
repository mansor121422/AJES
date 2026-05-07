<?php
$role = $role ?? 'ADMIN';
$name = $name ?? 'User';
$teacherSections = $teacherSections ?? [];
$audienceOptions = $audienceOptions ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create announcement - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Create announcement</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">New announcement</div>
        <p style="margin-bottom: 16px; color: #558b2f;">
            <?= ($role ?? '') === 'TEACHER'
                ? 'Only your students will be notified when you publish.'
                : 'Choose who should receive this announcement.' ?>
        </p>
        <form action="<?= base_url('announcements/store') ?>" method="post">
            <?= csrf_field() ?>
            <?php if (! empty($audienceOptions)): ?>
                <div class="form-group">
                    <label for="audience_type" style="color: #1b5e20;">Audience</label>
                    <select id="audience_type" name="audience_type" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <?php foreach ($audienceOptions as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= old('audience_type', 'school-wide') === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if (($role ?? '') === 'TEACHER'): ?>
                <div class="form-group">
                    <label for="section_id" style="color: #1b5e20;">Section to notify</label>
                    <select id="section_id" name="section_id" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <option value="">Select your section</option>
                        <?php foreach ($teacherSections as $s): ?>
                            <option value="<?= (int) ($s['id'] ?? 0) ?>" <?= (string) old('section_id') === (string) ($s['id'] ?? '') ? 'selected' : '' ?>>
                                <?= esc($s['display_label'] ?? ($s['name'] ?? 'Section')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="title" style="color: #1b5e20;">Title</label>
                <input type="text" id="title" name="title" required value="<?= esc(old('title')) ?>" placeholder="Announcement title" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="body" style="color: #1b5e20;">Body</label>
                <textarea id="body" name="body" rows="6" required placeholder="Full announcement text..." style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;"><?= esc(old('body')) ?></textarea>
            </div>
            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 24px;">
                <?= ($role ?? '') === 'TEACHER' ? 'Publish (notify my students)' : 'Publish announcement' ?>
            </button>
            <a href="<?= base_url('announcements') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    </main>
</div>
</body>
</html>
