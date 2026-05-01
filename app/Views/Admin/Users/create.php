<?php
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
            <div id="student-fields" style="display:none;">
                <div class="form-group">
                    <label for="student_id" style="color: #1b5e20;">Student ID / LRN</label>
                    <input type="text" id="student_id" name="student_id" value="<?= esc(old('student_id')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label for="gender" style="color: #1b5e20;">Gender</label>
                    <select id="gender" name="gender" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <option value="">Select gender</option>
                        <option value="Male" <?= old('gender') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= old('gender') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="grade_level" style="color: #1b5e20;">Grade Level</label>
                    <select id="grade_level" name="grade_level" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <option value="">Select grade</option>
                        <?php for ($g = 1; $g <= 6; $g++): ?>
                            <option value="<?= $g ?>" <?= (string) old('grade_level') === (string) $g ? 'selected' : '' ?>>Grade <?= $g ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="birthdate" style="color: #1b5e20;">Birthdate</label>
                    <input type="date" id="birthdate" name="birthdate" value="<?= esc(old('birthdate')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <small style="color: #666;">Student must be at least 6 years old.</small>
                </div>
                <div class="form-group">
                    <label for="address" style="color: #1b5e20;">Address</label>
                    <textarea id="address" name="address" rows="2" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;"><?= esc(old('address')) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="guardian_name" style="color: #1b5e20;">Guardian Name</label>
                    <input type="text" id="guardian_name" name="guardian_name" value="<?= esc(old('guardian_name')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label for="guardian_contact" style="color: #1b5e20;">Guardian Contact</label>
                    <input type="text" id="guardian_contact" name="guardian_contact" value="<?= esc(old('guardian_contact')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                </div>
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
    (function() {
        var roleEl = document.getElementById('role');
        var sf = document.getElementById('student-fields');
        var gradeEl = document.getElementById('grade_level');

        function syncStudentBlock() {
            var isStudent = roleEl && roleEl.value === 'STUDENT';
            if (sf) sf.style.display = isStudent ? 'block' : 'none';
            // Hidden required fields block submit for TEACHER etc.; require grade only for STUDENT.
            if (gradeEl) gradeEl.required = !!isStudent;
        }

        if (roleEl) roleEl.addEventListener('change', syncStudentBlock);

        var birthdateEl = document.getElementById('birthdate');
        if (birthdateEl) {
            var today = new Date();
            var max = new Date(today.getFullYear() - 6, today.getMonth(), today.getDate());
            var min = new Date(today.getFullYear() - 25, today.getMonth(), today.getDate());
            birthdateEl.max = max.toISOString().split('T')[0];
            birthdateEl.min = min.toISOString().split('T')[0];
        }

        syncStudentBlock();
    })();
    </script>

    </main>
</div>
</body>
</html>
