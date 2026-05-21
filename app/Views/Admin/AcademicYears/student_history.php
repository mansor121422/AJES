<?php
$student = $student ?? [];
$history = $history ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student enrollment history</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
<?php include(APPPATH . 'Views/template/index.php'); ?>
<?php include APPPATH . 'Views/Admin/AcademicYears/_layout_open.php'; ?>

<p style="margin:0 0 16px; color:#558b2f;">
    <strong><?= esc(\App\Models\UserModel::fullName($student)) ?></strong>
    — full enrollment history across all school years.
</p>

<div class="card">
    <table class="recent-table">
        <thead>
            <tr>
                <th>Academic year</th>
                <th>Grade</th>
                <th>Section</th>
                <th>Subjects (archived)</th>
                <th>Outcome</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($history === []): ?>
                <tr><td colspan="5">No enrollment history yet.</td></tr>
            <?php else: ?>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td>
                            <?= esc((string) ($h['academic_year_label'] ?? '')) ?>
                            <span style="font-size:12px; color:#888;">(<?= esc((string) ($h['academic_year_status'] ?? '')) ?>)</span>
                        </td>
                        <td><?= esc((string) ($h['grade_label'] ?? '')) ?></td>
                        <td><?= esc((string) ($h['section_name_snapshot'] ?? '—')) ?></td>
                        <td style="font-size:13px;">
                            <?php $subs = (array) ($h['subjects_list'] ?? []); ?>
                            <?= $subs !== [] ? esc(implode(', ', $subs)) : '—' ?>
                        </td>
                        <td><?= esc(ucfirst((string) ($h['outcome'] ?? 'enrolled'))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include APPPATH . 'Views/Admin/AcademicYears/_layout_close.php'; ?>
