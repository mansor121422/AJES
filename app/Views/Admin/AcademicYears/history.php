<?php
$year = $year ?? [];
$enrollments = $enrollments ?? [];
$currentPage = (int) ($current_page ?? 1);
$totalPages = (int) ($total_pages ?? 1);
$totalRows = (int) ($total_rows ?? 0);
$perPage = (int) ($per_page ?? 10);
$outcomeColors = [
    'enrolled'  => '#2e7d32',
    'promoted'  => '#1565c0',
    'retained'  => '#ef6c00',
    'graduated' => '#6a1b9a',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment History - <?= esc((string) ($year['label'] ?? '')) ?></title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
<?php include(APPPATH . 'Views/template/index.php'); ?>
<?php include APPPATH . 'Views/Admin/AcademicYears/_layout_open.php'; ?>

<div class="card">
    <p style="color:#558b2f; margin:0 0 12px;">
        Showing <?= esc((string) count($enrollments)) ?> of <?= esc((string) $totalRows) ?> records
        (Page <?= esc((string) $currentPage) ?> of <?= esc((string) $totalPages) ?>, <?= esc((string) $perPage) ?> per page).
    </p>
    <?php if (($year['status'] ?? '') === 'active'): ?>
        <p style="color:#888; font-size:13px; margin:0 0 12px;">
            <strong>Section —</strong> means the student is not assigned to a class yet. After end-of-year promotion, assign them in
            <a href="<?= base_url('admin/sections') ?>" class="link-details">Sections</a>.
        </p>
    <?php endif; ?>
    <table class="recent-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Grade</th>
                <th>Section</th>
                <th>Outcome</th>
                <th>Current</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($enrollments === []): ?>
                <tr><td colspan="5">No enrollment records for this year.</td></tr>
            <?php else: ?>
                <?php foreach ($enrollments as $e): ?>
                    <?php
                        $name = trim((string) (($e['surname'] ?? '') !== '' ? ($e['surname'] . ', ' . ($e['first_name'] ?? '')) : ($e['name'] ?? $e['username'] ?? '')));
                        $out = (string) ($e['outcome'] ?? 'enrolled');
                        $oc = $outcomeColors[$out] ?? '#333';
                    ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('admin/academic-years/student/' . (int) ($e['user_id'] ?? 0)) ?>" class="link-details"><?= esc($name) ?></a>
                        </td>
                        <td><?= esc((string) ($e['grade_label'] ?? '')) ?></td>
                        <td><?= esc((string) ($e['section_display'] ?? $e['section_name_snapshot'] ?? '—')) ?></td>
                        <td><span style="color:<?= $oc ?>; font-weight:600;"><?= esc((string) ($e['outcome_label'] ?? $out)) ?></span></td>
                        <td><?= ! empty($e['is_current']) ? 'Yes' : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin-top:12px;">
            <?php if ($currentPage > 1): ?>
                <a href="<?= base_url('admin/academic-years/history/' . (int) ($year['id'] ?? 0) . '?page=' . ($currentPage - 1)) ?>" class="link-details">Previous</a>
            <?php endif; ?>
            <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                <?php if ($p === $currentPage): ?>
                    <span style="padding:4px 8px; border-radius:6px; background:#2e7d32; color:#fff; font-weight:600;"><?= esc((string) $p) ?></span>
                <?php else: ?>
                    <a href="<?= base_url('admin/academic-years/history/' . (int) ($year['id'] ?? 0) . '?page=' . $p) ?>" class="link-details"><?= esc((string) $p) ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= base_url('admin/academic-years/history/' . (int) ($year['id'] ?? 0) . '?page=' . ($currentPage + 1)) ?>" class="link-details">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include APPPATH . 'Views/Admin/AcademicYears/_layout_close.php'; ?>
