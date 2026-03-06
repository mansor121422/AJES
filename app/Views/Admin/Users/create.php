<?php
$sections = $sections ?? [];
$role = $role ?? 'ADMIN';
$name = $name ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create user - AJES Admin</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Create user</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">New user</div>
        <form action="<?= base_url('admin/users/store') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name" style="color: #1b5e20;">Name</label>
                <input type="text" id="name" name="name" required value="<?= esc(old('name')) ?>" placeholder="Full name" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="email" style="color: #1b5e20;">Email</label>
                <input type="email" id="email" name="email" required value="<?= esc(old('email')) ?>" placeholder="user@example.com" pattern="[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" title="Letters, numbers, and @ only (e.g. user@domain.com)" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                <small style="color: #666;">Letters, numbers, at @ lang (hal. user@domain.com)</small>
            </div>
            <div class="form-group">
                <label for="username" style="color: #1b5e20;">Username</label>
                <input type="text" id="username" name="username" required value="<?= esc(old('username')) ?>" placeholder="letters, numbers, underscore only" pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscore only. No special characters." style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                <small style="color: #666;">Letters, numbers, at underscore lang. Walang special characters.</small>
            </div>
            <div class="form-group">
                <label for="password" style="color: #1b5e20;">Password</label>
                <input type="password" id="password" name="password" required placeholder="Min 6 characters" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="role" style="color: #1b5e20;">Role</label>
                <select id="role" name="role" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">Select role</option>
                    <option value="ADMIN" <?= old('role') === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                    <option value="PRINCIPAL" <?= old('role') === 'PRINCIPAL' ? 'selected' : '' ?>>PRINCIPAL</option>
                    <option value="ANNOUNCER" <?= old('role') === 'ANNOUNCER' ? 'selected' : '' ?>>ANNOUNCER</option>
                    <option value="TEACHER" <?= old('role') === 'TEACHER' ? 'selected' : '' ?>>TEACHER</option>
                    <option value="GUIDANCE" <?= old('role') === 'GUIDANCE' ? 'selected' : '' ?>>GUIDANCE</option>
                    <option value="STUDENT" <?= old('role') === 'STUDENT' ? 'selected' : '' ?>>STUDENT</option>
                </select>
            </div>
            <div class="form-group" id="section-group" style="display: none;">
                <label for="section_id" style="color: #1b5e20;">Section</label>
                <select id="section_id" name="section_id" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">— None —</option>
                    <?php foreach ($sections as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (string) old('section_id') === (string) $s['id'] ? 'selected' : '' ?>><?= esc($s['grade_level'] . ' - ' . $s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="color: #1b5e20;">
                    <input type="checkbox" name="is_active" value="1" <?= old('is_active', '1') ? 'checked' : '' ?>> Active
                </label>
            </div>
            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 24px;">Create user</button>
            <a href="<?= base_url('admin/users') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    <script>
    document.getElementById('role').addEventListener('change', function() {
        var g = document.getElementById('section-group');
        g.style.display = ['TEACHER','STUDENT'].indexOf(this.value) >= 0 ? 'block' : 'none';
    });
    document.getElementById('role').dispatchEvent(new Event('change'));
    </script>

    </main>
</div>
</body>
</html>
