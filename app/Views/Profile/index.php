<?php
$role = strtoupper((string) ($role ?? (session()->get('role') ?? 'STUDENT')));
$name = (string) ($name ?? (session()->get('name') ?? 'User'));
$user = $user ?? [];
$photo = trim((string) ($user['profile_photo'] ?? ''));
$photoUrl = $photo !== '' ? base_url($photo) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile Settings - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .profile-card { max-width: 760px; background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 14px rgba(27, 94, 32, 0.08); border: 1px solid rgba(46,125,50,0.1); }
        .profile-grid { display: grid; grid-template-columns: 140px 1fr; gap: 20px; align-items: start; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #c8e6c9; background: #f1f8e9; }
        .profile-avatar-fallback { width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #2e7d32; background: #e8f5e9; border: 3px solid #c8e6c9; }
        .profile-field { margin-bottom: 14px; }
        .profile-field label { display: block; margin-bottom: 6px; color: #1b5e20; font-weight: 600; font-size: 0.92rem; }
        .profile-field input[type="text"],
        .profile-field input[type="email"],
        .profile-field input[type="password"],
        .profile-field textarea {
            background: #fff;
            border: 1px solid #c8e6c9;
            color: #1b5e20;
        }
        .profile-field input::placeholder,
        .profile-field textarea::placeholder { color: #8aa08f; }
        .profile-actions { margin-top: 14px; display: flex; gap: 10px; }
        .btn-save { background: #2e7d32; color: #fff; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-edit { background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; padding: 8px 12px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 8px; }
        .btn-danger { background: #fff1f0; color: #b71c1c; border: 1px solid #ef9a9a; padding: 8px 12px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 8px; }
        .btn-link { background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; padding: 10px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; }
        .form-help { font-size: 0.8rem; color: #558b2f; margin-top: 6px; }
        .profile-section-title { font-size: 1rem; color: #1b5e20; margin-bottom: 10px; font-weight: 700; }
        .photo-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: none; align-items: center; justify-content: center; z-index: 1200; }
        .photo-modal.show { display: flex; }
        .photo-modal-card { width: min(92vw, 420px); background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 18px 45px rgba(0,0,0,0.22); border: 1px solid #c8e6c9; }
        .photo-modal-title { margin: 0 0 10px; color: #1b5e20; font-size: 1.02rem; font-weight: 700; }
        .photo-modal-actions { margin-top: 14px; display: flex; justify-content: flex-end; gap: 8px; }
        .btn-cancel { background: #f5f5f5; color: #333; border: 1px solid #ddd; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>
    <h1 class="dashboard-header">Profile Settings</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="card" style="border-left: 4px solid #c62828; color: #c62828;"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="card" style="border-left: 4px solid #2e7d32; color: #1b5e20;"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <form action="<?= base_url('profile') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="profile-grid">
                <div>
                    <?php if ($photoUrl): ?>
                        <img src="<?= esc($photoUrl) ?>" alt="Profile photo" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar-fallback">👤</div>
                    <?php endif; ?>
                    <button type="button" class="btn-edit" onclick="openPhotoEditor()">Edit</button>
                </div>
                <div>
                    <div class="profile-section-title">Personal Information</div>
                    <div class="profile-field">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required placeholder="Enter your full name" value="<?= esc(old('name', (string) ($user['name'] ?? ''))) ?>">
                    </div>
                    <div class="profile-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your email address" value="<?= esc(old('email', (string) ($user['email'] ?? ''))) ?>">
                    </div>
                    <div class="profile-field">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" placeholder="e.g. 09XXXXXXXXX" value="<?= esc(old('contact_number', (string) ($user['contact_number'] ?? ''))) ?>">
                    </div>
                    <div class="profile-field">
                        <label for="bio">Bio / About</label>
                        <textarea id="bio" name="bio" rows="3" placeholder="Write something about yourself"><?= esc(old('bio', (string) ($user['bio'] ?? ''))) ?></textarea>
                    </div>
                    <div class="profile-section-title">Security</div>
                    <div class="profile-field">
                        <label for="old_password">Old Password (required to change password)</label>
                        <input type="password" id="old_password" name="old_password" minlength="8" placeholder="Enter your current password">
                    </div>
                    <div class="profile-field">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" minlength="8" placeholder="Minimum 8 characters">
                    </div>
                    <div class="profile-field">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="8" placeholder="Re-enter your new password">
                    </div>
                    <div class="profile-actions">
                        <button type="submit" class="btn-save">Save Profile</button>
                        <a href="<?= base_url('dashboard/' . strtolower($role)) ?>" class="btn-link">Back to Dashboard</a>
                    </div>
                </div>
            </div>

            <div id="photo-modal" class="photo-modal" onclick="dismissPhotoModal(event)">
                <div class="photo-modal-card" role="dialog" aria-modal="true" aria-label="Edit profile picture">
                    <p class="photo-modal-title">Edit Profile Picture</p>
                    <div class="profile-field">
                        <label for="profile_photo">Profile Picture</label>
                        <input type="file" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <div class="form-help">JPG, PNG, or WEBP only (max 2MB).</div>
                    </div>
                    <?php if ($photoUrl): ?>
                        <button type="submit" name="remove_photo" value="1" class="btn-danger" onclick="return confirm('Delete current profile picture?')">Delete Current Photo</button>
                    <?php endif; ?>
                    <div class="photo-modal-actions">
                        <button type="button" class="btn-cancel" onclick="closePhotoEditor()">Close</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    </main>
</div>
<script>
function openPhotoEditor() {
    var modal = document.getElementById('photo-modal');
    if (!modal) return;
    modal.classList.add('show');
}
function closePhotoEditor() {
    var modal = document.getElementById('photo-modal');
    if (!modal) return;
    modal.classList.remove('show');
}
function dismissPhotoModal(event) {
    var modal = document.getElementById('photo-modal');
    if (!modal) return;
    if (event.target === modal) {
        closePhotoEditor();
    }
}
</script>
</body>
</html>

