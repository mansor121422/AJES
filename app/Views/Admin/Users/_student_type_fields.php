<?php

use App\Libraries\StudentEnrollmentType;

$selectedType = strtolower(trim((string) old('student_type', $studentType ?? '')));
$previousSchool = (string) old('previous_school', $previousSchool ?? '');
?>
<div class="form-group">
    <label for="student_type" style="color: #1b5e20;">Student type</label>
    <select id="student_type" name="student_type" class="student-enrollment-field" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
        <option value="">— Select student type —</option>
        <?php foreach (StudentEnrollmentType::options() as $value => $label): ?>
            <option value="<?= esc($value) ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
    </select>
    <small style="color: #666;">New student and transferee must provide the previous school name.</small>
</div>
<div class="form-group" id="previous-school-wrap" style="display: none;">
    <label for="previous_school" style="color: #1b5e20;">Previous school</label>
    <input type="text" id="previous_school" name="previous_school" class="student-enrollment-field" value="<?= esc($previousSchool) ?>" placeholder="Name of previous school" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
</div>

