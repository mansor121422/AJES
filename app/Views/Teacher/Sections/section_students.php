<?php
$section           = $section ?? [];
$studentsInSection = $studentsInSection ?? [];
$addableStudents   = $addableStudents ?? [];

function formatStudentName(array $u): string {
    $s = trim($u['surname'] ?? '');
    $f = trim($u['first_name'] ?? '');
    $m = trim($u['middle_initial'] ?? '');
    $x = trim($u['name_suffix'] ?? '');
    if ($s !== '' || $f !== '') {
        $part = $s . ($f !== '' ? ', ' . $f : '') . ($m !== '' ? ' ' . $m : '') . ($x !== '' ? ' ' . $x : '');
        return $part;
    }
    return $u['name'] ?? $u['username'] ?? '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - <?= esc($section['name']) ?></title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header"><?= esc($section['name']) ?> — Students</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Add student to section</div>
        <p style="margin-bottom: 12px; color: #558b2f;">Only students who already have a record (from Guidance) can be added.</p>
        <?php if (empty($addableStudents)): ?>
            <p>No students available to add (all have records and are either in this section or another).</p>
        <?php else: ?>
            <form action="<?= base_url('teacher/sections/add-student') ?>" method="post" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <?= csrf_field() ?>
                <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
                <div style="min-width: 280px;">
                    <label for="student_id" style="display: block; font-size: 0.85rem; color: #2e7d32; margin-bottom: 4px;">Student</label>
                    <select id="student_id" name="student_id" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <option value="">Select student</option>
                        <?php foreach ($addableStudents as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= esc($s['id']) ?> — <?= esc(formatStudentName($s)) ?> (<?= esc($s['gender'] ?? '-') ?> / Grade <?= esc($s['grade_level'] ?? '-') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px;">Add to section</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">Students in this section</div>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full name (Surname, First name MI Suffix)</th>
                    <th>Gender</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($studentsInSection)): ?>
                    <tr><td colspan="4">No students in this section yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($studentsInSection as $s): ?>
                        <tr>
                            <td><?= esc($s['id']) ?></td>
                            <td><?= esc(formatStudentName($s)) ?></td>
                            <td><?= esc($s['gender'] ?? '-') ?></td>
                            <td><?= esc($s['grade_level'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p><a href="<?= base_url('teacher/sections') ?>" class="link-details">← Back to My Sections</a></p>

    </main>
</div>
</body>
</html>
