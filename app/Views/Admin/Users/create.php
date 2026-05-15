<?php
$role = $role ?? 'ADMIN';
$name = $name ?? 'User';
$roleOptions = $role_options ?? [];
$roleDashboardTypes = $role_dashboard_types ?? [];
$selectedRole = strtoupper((string) old('role', session()->getFlashdata('new_role_slug') ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create user - AJES Admin</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .invalid-field {
            border-color: #d32f2f !important;
            background-color: #ffebee !important;
        }
    </style>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Create user</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">New user</div>
        <p style="margin-bottom: 16px; color: #558b2f;">
            Assign a role created under
            <a href="<?= base_url('admin/users?section=roles') ?>" style="color: #2e7d32;">Roles &amp; privileges</a>.
            Need a new role? <a href="<?= base_url('admin/users/roles/create') ?>" style="color: #2e7d32; font-weight: 600;">Create role</a> first.
        </p>
        <form action="<?= base_url('admin/users/store') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="first_name" style="color: #1b5e20;">First name</label>
                <input type="text" id="first_name" name="first_name" required value="<?= esc(old('first_name')) ?>" placeholder="First name" pattern="[A-Za-zÑñ ]+" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="middle_name" style="color: #1b5e20;">Middle name</label>
                <input type="text" id="middle_name" name="middle_name" value="<?= esc(old('middle_name')) ?>" placeholder="Middle name (optional)" pattern="[A-Za-zÑñ ]*" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="surname" style="color: #1b5e20;">Surname</label>
                <input type="text" id="surname" name="surname" required value="<?= esc(old('surname')) ?>" placeholder="Surname" pattern="[A-Za-zÑñ ]+" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="suffix" style="color: #1b5e20;">Suffix (optional)</label>
                <input type="text" id="suffix" name="suffix" value="<?= esc(old('suffix')) ?>" placeholder="e.g. Jr., Sr., III" pattern="[A-Za-zÑñ. ]*" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="email" style="color: #1b5e20;">Email</label>
                <input type="email" id="email" name="email" required value="<?= esc(old('email')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="username" style="color: #1b5e20;">Username</label>
                <input type="text" id="username" name="username" required value="<?= esc(old('username')) ?>" pattern="[a-zA-Z0-9_]+" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="password_preview" style="color: #1b5e20;">Default Password</label>
                <input type="text" id="password_preview" value="ajes2026" readonly style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px; background: #f1f8e9; color: #1b5e20;">
                <small style="color: #666;">Set automatically for every new user.</small>
            </div>

            <div class="form-group">
                <label for="role" style="color: #1b5e20;">Role</label>
                <select id="role" name="role" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">— Select role —</option>
                    <?php foreach ($roleOptions as $slug => $label): ?>
                        <option value="<?= esc($slug) ?>" <?= $selectedRole === $slug ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #666;">Privileges come from the selected role. To change privileges, edit the role under Roles &amp; privileges.</small>
            </div>

            <div id="section-group" style="display:none;">
                <div class="form-group">
                    <label for="section_id" style="color: #1b5e20;">Section</label>
                    <select id="section_id" name="section_id" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <option value="">— None —</option>
                        <?php foreach (($sections ?? []) as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= (string) old('section_id') === (string) $s['id'] ? 'selected' : '' ?>><?= esc($s['grade_level'] . ' - ' . $s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
            <div class="form-group">
                <label style="color: #1b5e20;">
                    <input type="checkbox" name="mfa_enabled" value="1" <?= old('mfa_enabled') ? 'checked' : '' ?>> Enable MFA
                </label>
            </div>
            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 24px;">Create user</button>
            <a href="<?= base_url('admin/users') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    <script>
    (function() {
        var roleEl = document.getElementById('role');
        var sectionGroup = document.getElementById('section-group');
        var studentFields = document.getElementById('student-fields');
        var gradeEl = document.getElementById('grade_level');
        var roleDashboardTypes = <?= json_encode($roleDashboardTypes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        function dashboardTypeForRole(slug) {
            if (!slug) return '';
            return roleDashboardTypes[slug] || '';
        }

        function syncRoleFields() {
            var slug = roleEl ? roleEl.value : '';
            var dashType = dashboardTypeForRole(slug);
            var isTeacher = dashType === 'teacher' || slug === 'TEACHER';
            var isStudent = dashType === 'student' || slug === 'STUDENT';
            if (sectionGroup) sectionGroup.style.display = (isTeacher || isStudent) ? 'block' : 'none';
            if (studentFields) studentFields.style.display = isStudent ? 'block' : 'none';
            if (gradeEl) gradeEl.required = isStudent;
        }

        if (roleEl) {
            roleEl.addEventListener('change', syncRoleFields);
            syncRoleFields();
        }
    })();
    </script>

    </main>
</div>
</body>
</html>
