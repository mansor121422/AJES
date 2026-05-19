<?php
$section            = $section ?? [];
$studentsInSection  = $studentsInSection ?? [];
$addableStudents    = $addableStudents ?? [];
$studentCount       = (int) ($student_count ?? count($studentsInSection));
$maxStudents        = (int) ($max_students ?? 30);
$sectionHasCapacity = (bool) ($section_has_capacity ?? ($studentCount < $maxStudents));

function formatStudentName(array $u): string {
    $s = trim($u['surname'] ?? '');
    $f = trim($u['first_name'] ?? '');
    $m = trim($u['middle_initial'] ?? '');
    $x = trim($u['name_suffix'] ?? '');
    if ($s !== '' || $f !== '') {
        return $s . ($f !== '' ? ', ' . $f : '') . ($m !== '' ? ' ' . $m : '') . ($x !== '' ? ' ' . $x : '');
    }
    return $u['name'] ?? $u['username'] ?? '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - <?= esc($section['name'] ?? '') ?></title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header"><?= esc($section['grade_level'] ?? '') ?> — <?= esc($section['name'] ?? '') ?> — Students</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 16px;">
        <p style="margin: 0; color: #558b2f;">
            <strong><?= $studentCount ?> / <?= $maxStudents ?></strong> students enrolled.
            <?php if (! $sectionHasCapacity): ?>
                This section is full. Remove a student before adding another.
            <?php else: ?>
                <?= $maxStudents - $studentCount ?> slot<?= ($maxStudents - $studentCount) === 1 ? '' : 's' ?> remaining.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($sectionHasCapacity): ?>
    <div class="card">
        <div class="card-title">Enroll student</div>
        <p style="margin-bottom: 12px; color: #558b2f;">Only active students with the <strong>same grade</strong> as this section and <strong>not yet assigned</strong> to any class are listed.</p>
        <?php if (empty($addableStudents)): ?>
            <p>No students available to enroll right now.</p>
        <?php else: ?>
            <form action="<?= base_url('admin/sections/add-student') ?>" method="post" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <?= csrf_field() ?>
                <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
                <div style="min-width: 280px;">
                    <label for="student_id" style="display: block; font-size: 0.85rem; color: #2e7d32; margin-bottom: 4px;">Student</label>
                    <select id="student_id" name="student_id" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <option value="">Select student</option>
                        <?php foreach ($addableStudents as $s): ?>
                            <option value="<?= (int) $s['id'] ?>">
                                <?= esc($s['id']) ?> — <?= esc(formatStudentName($s)) ?> (<?= esc($s['gender'] ?? '-') ?> / Grade <?= esc($s['grade_level'] ?? '-') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px;">Enroll</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Enrolled students</div>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full name</th>
                    <th>Gender</th>
                    <th>Grade</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($studentsInSection)): ?>
                    <tr><td colspan="5">No students enrolled in this section yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($studentsInSection as $s): ?>
                        <tr>
                            <td><?= esc($s['id']) ?></td>
                            <td><?= esc(formatStudentName($s)) ?></td>
                            <td><?= esc($s['gender'] ?? '-') ?></td>
                            <td><?= esc($s['grade_level'] ?? '-') ?></td>
                            <td>
                                <form action="<?= base_url('admin/sections/remove-student') ?>" method="post" style="display: inline;" onsubmit="return confirm('Remove this student from the section?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
                                    <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
                                    <button type="submit" class="link-details" style="background: none; border: none; cursor: pointer; padding: 0;">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p><a href="<?= base_url('admin/sections') ?>" class="link-details">← Back to Sections</a></p>

    </main>
</div>
</body>
</html>
