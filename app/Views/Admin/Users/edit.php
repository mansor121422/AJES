<?php
use App\Libraries\RoleRegistry;

$user = $user ?? [];
$sections = $sections ?? [];
$role = $role ?? 'ADMIN';
$name = $name ?? 'User';
$is_editing_self = $is_editing_self ?? false;
$roleOptions = $role_options ?? [];
$roleDashboardTypes = $role_dashboard_types ?? [];

$userRoleSlug = strtoupper((string) ($user['role'] ?? 'STAFF'));
$selectedRoleSlug = strtoupper((string) old('role', $userRoleSlug));
$studentSectionLocked = ($userRoleSlug === 'STUDENT' && (int) ($user['section_id'] ?? 0) > 0);
$lockedSectionLabel = '';
if ($studentSectionLocked) {
    foreach ($sections as $s) {
        if ((string) ($s['id'] ?? '') === (string) ($user['section_id'] ?? '')) {
            $lockedSectionLabel = esc($s['grade_level'] . ' - ' . $s['name']);
            break;
        }
    }
}
$fullName = trim((string) ($user['name'] ?? ''));
$nameParts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
$firstNameValue = '';
$middleNameValue = '';
$surnameValue = '';
$suffixValue = '';
if (! empty($nameParts)) {
    $lastPart = strtoupper(rtrim((string) end($nameParts), '.'));
    if (in_array($lastPart, ['JR', 'SR', 'II', 'III', 'IV', 'V'], true)) {
        $suffixValue = (string) array_pop($nameParts);
    }
}
if (! empty($nameParts)) {
    $firstNameValue = array_shift($nameParts);
    if (! empty($nameParts)) {
        $surnameValue = array_pop($nameParts);
        $middleNameValue = implode(' ', $nameParts);
    }
}
$roleDisplayName = RoleRegistry::displayName($userRoleSlug);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit user - AJES Admin</title>
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

    <h1 class="dashboard-header">Edit user</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">User #<?= esc($user['id']) ?> &middot; <?= esc($roleDisplayName) ?></div>
        <p style="margin-bottom: 16px; color: #558b2f;">
            Privileges come from the selected role. Manage roles under
            <a href="<?= base_url('admin/users?section=roles') ?>" style="color: #2e7d32;">Roles &amp; privileges</a>.
        </p>
        <?php if ($is_editing_self): ?>
        <p style="margin-bottom: 12px; color: #795548; background: #fff3e0; padding: 10px; border-radius: 8px;">You are editing your own profile. Role cannot be changed.</p>
        <?php endif; ?>
        <form action="<?= base_url('admin/users/update/' . $user['id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="first_name" style="color: #1b5e20;">First name</label>
                <input type="text" id="first_name" name="first_name" required value="<?= esc(old('first_name', $firstNameValue)) ?>" pattern="[A-Za-zÑñ ]+" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="middle_name" style="color: #1b5e20;">Middle name</label>
                <input type="text" id="middle_name" name="middle_name" value="<?= esc(old('middle_name', $middleNameValue)) ?>" placeholder="Middle name (optional)" pattern="[A-Za-zÑñ ]*" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="surname" style="color: #1b5e20;">Surname</label>
                <input type="text" id="surname" name="surname" required value="<?= esc(old('surname', $surnameValue)) ?>" pattern="[A-Za-zÑñ ]+" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="suffix" style="color: #1b5e20;">Suffix (optional)</label>
                <input type="text" id="suffix" name="suffix" value="<?= esc(old('suffix', $suffixValue)) ?>" placeholder="e.g. Jr., Sr., III" pattern="[A-Za-zÑñ. ]*" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="email" style="color: #1b5e20;">Email</label>
                <input type="email" id="email" name="email" required value="<?= esc(old('email', $user['email'])) ?>" pattern="[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="username" style="color: #1b5e20;">Username</label>
                <input type="text" id="username" name="username" required value="<?= esc(old('username', $user['username'])) ?>" pattern="[a-zA-Z0-9_]+" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="password" style="color: #1b5e20;">Password (leave blank to keep)</label>
                <input type="password" id="password" name="password" placeholder="Leave blank to keep current" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>

            <div class="form-group">
                <label for="role" style="color: #1b5e20;">Role</label>
                <?php if ($is_editing_self): ?>
                    <input type="text" id="role" value="<?= esc($roleDisplayName) ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px; background: #f1f8e9;">
                    <input type="hidden" name="role" value="<?= esc($userRoleSlug) ?>">
                <?php else: ?>
                    <select id="role" name="role" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <option value="">— Select role —</option>
                        <?php foreach ($roleOptions as $slug => $label): ?>
                            <option value="<?= esc($slug) ?>" <?= $selectedRoleSlug === $slug ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div id="section-group" style="display:none;">
                <div class="form-group">
                    <?php if ($studentSectionLocked): ?>
                        <span style="display: block; color: #1b5e20; font-weight: 600; margin-bottom: 4px;">Section</span>
                        <p style="margin: 0; padding: 10px 12px; background: #f1f8e9; border: 1px solid #c8e6c9; border-radius: 8px; color: #1b5e20;">
                            <?= $lockedSectionLabel !== '' ? $lockedSectionLabel : 'Assigned section' ?>
                            <span style="display: block; font-size: 0.82rem; color: #558b2f; margin-top: 6px;">This student must stay in this section.</span>
                        </p>
                        <input type="hidden" name="section_id" value="<?= (int) $user['section_id'] ?>">
                    <?php else: ?>
                        <label for="section_id" style="color: #1b5e20;">Section</label>
                        <select id="section_id" name="section_id" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                            <option value="">— None —</option>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= (int) $s['id'] ?>" <?= (string) ($user['section_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= esc($s['grade_level'] . ' - ' . $s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>

            <div id="student-fields" style="display:none;">
                <div class="form-group">
                    <label for="student_id" style="color: #1b5e20;">Student ID / LRN</label>
                    <input type="text" id="student_id" name="student_id" value="<?= esc(old('student_id', $user['student_id'] ?? '')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label for="gender" style="color: #1b5e20;">Gender</label>
                    <select id="gender" name="gender" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <option value="">Select gender</option>
                        <option value="Male" <?= old('gender', $user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= old('gender', $user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="grade_level" style="color: #1b5e20;">Grade Level</label>
                    <input type="text" id="grade_level" name="grade_level" value="<?= esc(old('grade_level', $user['grade_level'] ?? '')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label for="birthdate" style="color: #1b5e20;">Birthdate</label>
                    <input type="date" id="birthdate" name="birthdate" value="<?= esc(old('birthdate', $user['birthdate'] ?? '')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label for="address" style="color: #1b5e20;">Address</label>
                    <textarea id="address" name="address" rows="2" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;"><?= esc(old('address', $user['address'] ?? '')) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="guardian_name" style="color: #1b5e20;">Guardian Name</label>
                    <input type="text" id="guardian_name" name="guardian_name" value="<?= esc(old('guardian_name', $user['guardian_name'] ?? '')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label for="guardian_contact" style="color: #1b5e20;">Guardian Contact</label>
                    <input type="text" id="guardian_contact" name="guardian_contact" value="<?= esc(old('guardian_contact', $user['guardian_contact'] ?? '')) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                </div>
            </div>

            <div class="form-group">
                <label style="color: #1b5e20;">
                    <input type="checkbox" name="is_active" value="1" <?= ! empty($user['is_active']) ? 'checked' : '' ?>> Active
                </label>
            </div>
            <div class="form-group">
                <label style="color: #1b5e20;">
                    <input type="checkbox" name="mfa_enabled" value="1" <?= ! empty($user['mfa_enabled']) ? 'checked' : '' ?>> Enable Two-Factor Authentication (MFA)
                </label>
            </div>
            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 24px;">Update</button>
            <a href="<?= base_url('admin/users') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    <script>
    (function () {
        var roleEl = document.getElementById('role');
        var sectionGroup = document.getElementById('section-group');
        var studentFields = document.getElementById('student-fields');
        var roleDashboardTypes = <?= json_encode($roleDashboardTypes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        function dashboardTypeForRole(slug) {
            if (!slug) return '';
            return roleDashboardTypes[slug] || '';
        }

        function syncRoleFields() {
            var slug = roleEl ? (roleEl.value || roleEl.getAttribute('value') || '') : '';
            if (roleEl && roleEl.tagName === 'INPUT' && roleEl.readOnly) {
                var hidden = document.querySelector('input[type="hidden"][name="role"]');
                if (hidden) slug = hidden.value;
            }
            var dashType = dashboardTypeForRole(slug);
            var isTeacher = dashType === 'teacher' || slug === 'TEACHER';
            var isStudent = dashType === 'student' || slug === 'STUDENT';
            if (sectionGroup) sectionGroup.style.display = (isTeacher || isStudent) ? 'block' : 'none';
            if (studentFields) studentFields.style.display = isStudent ? 'block' : 'none';
        }

        if (roleEl && roleEl.tagName === 'SELECT') {
            roleEl.addEventListener('change', syncRoleFields);
        }
        syncRoleFields();
    })();
    </script>

    </main>
</div>
</body>
</html>
