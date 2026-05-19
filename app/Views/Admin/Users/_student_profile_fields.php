<?php
/** @var array<string, mixed> $profileUser */
$profileUser = $profileUser ?? [];
$val = static function (string $key) use ($profileUser): string {
    return esc(old($key, $profileUser[$key] ?? ''));
};
$ageVal = old('age', $profileUser['age'] ?? '');
?>
<div class="card" style="margin-top: 8px; margin-bottom: 8px; background: #f9fdf7; border: 1px solid #c8e6c9;">
    <div class="card-title" style="font-size: 1rem;">Student profile details</div>
    <p class="hint-text" style="margin-bottom: 12px;">LRN, age, gender, guardian, and address are saved for the Students Log.</p>

    <div class="form-group">
        <label for="student_id" style="color: #1b5e20;">LRN (Learner Reference Number)</label>
        <input type="text" id="student_id" name="student_id" class="student-profile-field" value="<?= $val('student_id') ?>" placeholder="12-digit LRN" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
    </div>
    <div class="form-group">
        <label for="gender" style="color: #1b5e20;">Gender</label>
        <select id="gender" name="gender" class="student-profile-field" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            <option value="">Select gender</option>
            <option value="Male" <?= old('gender', $profileUser['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= old('gender', $profileUser['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
        </select>
    </div>
    <div class="form-group">
        <label for="birthdate" style="color: #1b5e20;">Birthdate</label>
        <input type="date" id="birthdate" name="birthdate" class="student-profile-field" value="<?= $val('birthdate') ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
    </div>
    <div class="form-group">
        <label for="age_display" style="color: #1b5e20;">Age</label>
        <input type="text" id="age_display" readonly value="<?= $ageVal !== '' ? esc($ageVal) : '' ?>" placeholder="Computed from birthdate" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px; background: #f1f8e9;">
        <input type="hidden" id="age" name="age" value="<?= esc((string) $ageVal) ?>">
        <small style="color: #666;">Calculated automatically when you pick a birthdate.</small>
    </div>
    <div class="form-group">
        <label for="grade_level" style="color: #1b5e20;">Grade level</label>
        <select id="grade_level" name="grade_level" class="student-profile-field student-grade-select" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            <option value="">Select grade</option>
            <?php for ($g = 1; $g <= 6; $g++): ?>
                <option value="<?= $g ?>" <?= (string) old('grade_level', $profileUser['grade_level'] ?? '') === (string) $g ? 'selected' : '' ?>>Grade <?= $g ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="address" style="color: #1b5e20;">Address</label>
        <textarea id="address" name="address" class="student-profile-field" rows="2" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;"><?= $val('address') ?></textarea>
    </div>
    <div class="form-group">
        <label for="guardian_name" style="color: #1b5e20;">Guardian name</label>
        <input type="text" id="guardian_name" name="guardian_name" class="student-profile-field" value="<?= $val('guardian_name') ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
    </div>
    <div class="form-group">
        <label for="guardian_contact" style="color: #1b5e20;">Guardian contact</label>
        <input type="text" id="guardian_contact" name="guardian_contact" class="student-profile-field" value="<?= $val('guardian_contact') ?>" placeholder="Phone number" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
    </div>
</div>
