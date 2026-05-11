<?php
$role = $role ?? 'ADMIN';
$name = $name ?? 'User';
$privilegeLabels = $privilege_labels ?? [];
$privilegeRoleMap = $privilege_role_map ?? [];
$selectedPrivileges = old('admin_privileges');
if (! is_array($selectedPrivileges)) {
    $selectedPrivileges = [];
}
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

    <div class="card">
        <div class="card-title">New user</div>
        <form action="<?= base_url('admin/users/store') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="first_name" style="color: #1b5e20;">First name</label>
                <input type="text" id="first_name" name="first_name" required value="<?= esc(old('first_name')) ?>" placeholder="First name" pattern="[A-Za-zÑñ ]+" title="First name must contain letters (including Ñ/ñ) and spaces only. Numbers and special characters are not allowed." style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="middle_name" style="color: #1b5e20;">Middle name</label>
                <input type="text" id="middle_name" name="middle_name" value="<?= esc(old('middle_name')) ?>" placeholder="Middle name (optional)" pattern="[A-Za-zÑñ ]*" title="Middle name must contain letters (including Ñ/ñ) and spaces only. Numbers and special characters are not allowed." style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="surname" style="color: #1b5e20;">Surname</label>
                <input type="text" id="surname" name="surname" required value="<?= esc(old('surname')) ?>" placeholder="Surname" pattern="[A-Za-zÑñ ]+" title="Surname must contain letters (including Ñ/ñ) and spaces only. Numbers and special characters are not allowed." style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                <small style="color: #666;">Name fields: letters (including Ñ/ñ) and spaces only. No numbers or special characters.</small>
            </div>
            <div class="form-group">
                <label for="suffix" style="color: #1b5e20;">Suffix (optional)</label>
                <input type="text" id="suffix" name="suffix" value="<?= esc(old('suffix')) ?>" placeholder="e.g. Jr., Sr., III" pattern="[A-Za-zÑñ. ]*" title="Suffix must contain letters (including Ñ/ñ), spaces, or dot only." style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
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
                <label for="password_preview" style="color: #1b5e20;">Default Password</label>
                <input type="text" id="password_preview" value="ajes2026" readonly style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px; background: #f1f8e9; color: #1b5e20;">
                <small style="color: #666;">This is set automatically for every new user created by admin.</small>
            </div>
            <div class="form-group">
                <label for="role" style="color: #1b5e20;">Role</label>
                <select id="role" name="role" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">Select role</option>
                    <option value="SUPER_ADMIN" <?= old('role') === 'SUPER_ADMIN' ? 'selected' : '' ?>>SUPER_ADMIN</option>
                    <option value="ADMIN" <?= old('role') === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                    <option value="PRINCIPAL" <?= old('role') === 'PRINCIPAL' ? 'selected' : '' ?>>PRINCIPAL</option>
                    <option value="VICE_PRINCIPAL" <?= old('role') === 'VICE_PRINCIPAL' ? 'selected' : '' ?>>VICE_PRINCIPAL</option>
                    <option value="HEAD_TEACHER" <?= old('role') === 'HEAD_TEACHER' ? 'selected' : '' ?>>HEAD_TEACHER</option>
                    <option value="ANNOUNCER" <?= old('role') === 'ANNOUNCER' ? 'selected' : '' ?>>ANNOUNCER</option>
                    <option value="TEACHER" <?= old('role') === 'TEACHER' ? 'selected' : '' ?>>TEACHER</option>
                    <option value="GUIDANCE" <?= old('role') === 'GUIDANCE' ? 'selected' : '' ?>>GUIDANCE</option>
                    <option value="STUDENT" <?= old('role') === 'STUDENT' ? 'selected' : '' ?>>STUDENT</option>
                </select>
            </div>
            <div id="feature-privileges" style="margin-bottom: 16px; padding: 12px; border: 1px solid #c8e6c9; border-radius: 10px; background: #f8fff8;">
                <div style="font-weight: 600; color: #1b5e20; margin-bottom: 8px;">Feature Privileges</div>
                <small style="display:block; margin-bottom: 10px; color: #558b2f;">Choose which features this account can access. Chat is always available to all users.</small>
                <div style="margin-bottom: 10px;">
                    <button type="button" id="privileges-select-all" style="margin-right: 8px; padding: 6px 10px; border: 1px solid #81c784; border-radius: 6px; background: #e8f5e9; color: #1b5e20; cursor: pointer;">Select all</button>
                    <button type="button" id="privileges-clear-all" style="padding: 6px 10px; border: 1px solid #c8e6c9; border-radius: 6px; background: #fff; color: #2e7d32; cursor: pointer;">Clear all</button>
                </div>
                <?php foreach ($privilegeLabels as $key => $label): ?>
                    <?php
                        $rolesForPrivilege = [];
                        foreach ($privilegeRoleMap as $roleKey => $keys) {
                            if (in_array($key, (array) $keys, true)) {
                                $rolesForPrivilege[] = $roleKey;
                            }
                        }
                    ?>
                    <label style="display: block; margin-bottom: 6px; color: #1b5e20;">
                        <input type="checkbox" name="admin_privileges[]" value="<?= esc($key) ?>" data-roles="<?= esc(implode(',', $rolesForPrivilege)) ?>" <?= in_array($key, $selectedPrivileges, true) ? 'checked' : '' ?>>
                        <?= esc($label) ?>
                    </label>
                <?php endforeach; ?>
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
            <div class="form-group">
                <label style="color: #1b5e20;">
                    <input type="checkbox" name="mfa_enabled" value="1" <?= old('mfa_enabled') ? 'checked' : '' ?>> Enable Two-Factor Authentication (MFA)
                </label>
                <small style="color: #666; display: block; margin-top: 4px;">When enabled, the user must enter a 6-digit email code after their password on every login.</small>
            </div>
            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 24px;">Create user</button>
            <a href="<?= base_url('admin/users') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    <script>
    (function() {
        var formEl = document.querySelector('form[action$="admin/users/store"]');
        var roleEl = document.getElementById('role');
        var sf = document.getElementById('student-fields');
        var gradeEl = document.getElementById('grade_level');
        var selectAllBtn = document.getElementById('privileges-select-all');
        var clearAllBtn = document.getElementById('privileges-clear-all');
        var privilegeChecks = document.querySelectorAll('input[name="admin_privileges[]"]');
        var privilegeRoleMap = <?= json_encode($privilegeRoleMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function syncStudentBlock() {
            var isStudent = roleEl && roleEl.value === 'STUDENT';
            if (sf) sf.style.display = isStudent ? 'block' : 'none';
            // Hidden required fields block submit for TEACHER etc.; require grade only for STUDENT.
            if (gradeEl) gradeEl.required = !!isStudent;
        }

        function syncPrivilegesByRole() {
            if (!roleEl) return;
            var selectedRole = roleEl.value || '';
            var allowed = privilegeRoleMap[selectedRole] || [];
            privilegeChecks.forEach(function (cb) {
                var isAllowed = allowed.indexOf(cb.value) >= 0;
                cb.disabled = !isAllowed;
                if (!isAllowed) {
                    cb.checked = false;
                }
                var row = cb.closest('label');
                if (row) {
                    row.style.opacity = isAllowed ? '1' : '0.45';
                }
            });
        }

        function refreshFieldState(field) {
            if (!field || typeof field.checkValidity !== 'function') return;
            if (field.checkValidity()) {
                field.classList.remove('invalid-field');
            } else {
                field.classList.add('invalid-field');
            }
        }

        function bindValidationStyles() {
            if (!formEl) return;
            var fields = formEl.querySelectorAll('input, select, textarea');
            fields.forEach(function(field) {
                field.addEventListener('input', function() { refreshFieldState(field); });
                field.addEventListener('change', function() { refreshFieldState(field); });
                field.addEventListener('blur', function() { refreshFieldState(field); });
            });
            formEl.addEventListener('submit', function() {
                fields.forEach(function(field) { refreshFieldState(field); });
            });
        }

        if (roleEl) roleEl.addEventListener('change', function () {
            syncStudentBlock();
            syncPrivilegesByRole();
        });
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function () {
                privilegeChecks.forEach(function (cb) {
                    if (!cb.disabled) cb.checked = true;
                });
            });
        }
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function () {
                privilegeChecks.forEach(function (cb) { cb.checked = false; });
            });
        }

        var birthdateEl = document.getElementById('birthdate');
        if (birthdateEl) {
            var today = new Date();
            var max = new Date(today.getFullYear() - 6, today.getMonth(), today.getDate());
            var min = new Date(today.getFullYear() - 25, today.getMonth(), today.getDate());
            birthdateEl.max = max.toISOString().split('T')[0];
            birthdateEl.min = min.toISOString().split('T')[0];
        }

        syncStudentBlock();
        syncPrivilegesByRole();
        bindValidationStyles();
    })();
    </script>

    </main>
</div>
</body>
</html>
