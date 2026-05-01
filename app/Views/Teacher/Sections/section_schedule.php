<?php
$section = $section ?? [];
$scheduleRows = $scheduleRows ?? [];
$fmtRange = static function (string $s, string $e): string {
    $a = date('g:i A', strtotime($s));
    $b = date('g:i A', strtotime($e));

    return $a . ' – ' . $b;
};
$fmtTime = static function (string $t): string {
    return date('g:i A', strtotime($t));
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class schedule — <?= esc($section['name'] ?? '') ?></title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.95rem; }
        .schedule-table th, .schedule-table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #e8f5e9; vertical-align: middle; }
        .schedule-table th { color: #1b5e20; font-weight: 600; }
        .schedule-break td { background: #f1f8e9; color: #33691e; font-size: 0.9rem; }
        .schedule-dismiss td { background: #fff8e1; color: #f57f17; font-size: 0.9rem; }
        .teacher-tba { color: #888; font-style: italic; }
    </style>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header"><?= esc($section['name'] ?? '') ?> — Class schedule</h1>
    <p style="color: #558b2f; margin-bottom: 16px;">Your times and subjects; teachers show as assigned, or <span class="teacher-tba">tba</span> until admin assigns someone.</p>

    <div class="card">
        <div class="card-title">Daily timetable</div>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th style="width: 32%;">Time</th>
                    <th style="width: 28%;">Subject</th>
                    <th>Teacher</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scheduleRows as $row): ?>
                    <?php if (($row['kind'] ?? '') === 'break'): ?>
                        <tr class="schedule-break">
                            <td colspan="3">
                                <strong><?= esc($row['label'] ?? '') ?></strong> — <?= esc($fmtRange((string) ($row['start'] ?? ''), (string) ($row['end'] ?? ''))) ?>
                            </td>
                        </tr>
                    <?php elseif (($row['kind'] ?? '') === 'dismissal'): ?>
                        <tr class="schedule-dismiss">
                            <td colspan="3">
                                <strong>Dismissal</strong> — <?= esc($fmtTime((string) ($row['time'] ?? '15:00'))) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td><?= esc($fmtRange((string) ($row['start'] ?? ''), (string) ($row['end'] ?? ''))) ?> <span style="color:#888;">(1 hr)</span></td>
                            <td><?= esc((string) ($row['subject'] ?? '—')) ?></td>
                            <td>
                                <?php
                                $t = (string) ($row['teacher'] ?? 'tba');
                                $isTba = strcasecmp($t, 'tba') === 0;
                                ?>
                                <?php if ($isTba): ?>
                                    <span class="teacher-tba">tba</span>
                                <?php else: ?>
                                    <?= esc($t) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p><a href="<?= base_url('teacher/sections') ?>" class="link-details">← Back to my sections</a></p>

    </main>
</div>
</body>
</html>
