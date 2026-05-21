<?php
$activeYear = $active_year ?? null;
$preview = $preview ?? [];
$students = (array) ($preview['students'] ?? []);
$retainedIds = $retained_ids ?? [];
$nextLabel = $next_label ?? ($preview['next_label'] ?? '');
$cloneSections = ! empty($clone_sections);
$actionColors = [
    'promote'  => '#2e7d32',
    'retain'   => '#ef6c00',
    'graduate' => '#1565c0',
    'skipped'  => '#9e9e9e',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Close Academic Year - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
<?php include(APPPATH . 'Views/template/index.php'); ?>
<?php include APPPATH . 'Views/Admin/AcademicYears/_layout_open.php'; ?>

<div class="card" style="margin-bottom:16px; border-left:4px solid #ff8f00;">
    <div class="card-title">Closing: <?= esc((string) ($activeYear['label'] ?? '')) ?></div>
    <p style="color:#555; margin:0 0 12px;">Review the promotion plan. Retained students repeat the same grade. This cannot be undone automatically — take a backup first if needed.</p>
    <div style="display:flex; flex-wrap:wrap; gap:16px;">
        <span style="color:#2e7d32; font-weight:600;">Promote: <?= (int) ($preview['promote'] ?? 0) ?></span>
        <span style="color:#ef6c00; font-weight:600;">Retain: <?= (int) ($preview['retain'] ?? 0) ?></span>
        <span style="color:#1565c0; font-weight:600;">Graduate: <?= (int) ($preview['graduate'] ?? 0) ?></span>
        <?php if ((int) ($preview['skipped'] ?? 0) > 0): ?>
            <span style="color:#9e9e9e;">No grade set: <?= (int) $preview['skipped'] ?></span>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="<?= base_url('admin/academic-years/close/preview') ?>" id="retainForm" style="margin-bottom:16px;">
    <?= csrf_field() ?>
    <input type="hidden" name="next_label" value="<?= esc($nextLabel) ?>">
    <input type="hidden" name="clone_sections" value="<?= $cloneSections ? '1' : '0' ?>">
    <div class="card">
        <div class="card-title">Step 1 — Mark retained students (optional)</div>
        <p style="color:#558b2f; font-size:14px;">Check students who did <strong>not</strong> pass and must repeat the same grade.</p>
        <div style="overflow-x:auto; max-height:320px; overflow-y:auto;">
            <table class="recent-table">
                <thead>
                    <tr>
                        <th style="width:40px;">Retain</th>
                        <th>Student</th>
                        <th>Current</th>
                        <th>Section</th>
                        <th>Planned action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <?php
                            $sid = (int) ($s['id'] ?? 0);
                            $act = (string) ($s['action'] ?? '');
                            $checked = in_array($sid, $retainedIds, true);
                            if ($checked) {
                                $act = 'retain';
                            }
                            $color = $actionColors[$act] ?? '#333';
                        ?>
                        <tr>
                            <td>
                                <?php if (($s['grade_level'] ?? '') !== '' && $act !== 'graduate' && $act !== 'skipped'): ?>
                                    <input type="checkbox" name="retained[]" value="<?= $sid ?>" <?= $checked ? 'checked' : '' ?> onchange="document.getElementById('retainForm').submit();">
                                <?php endif; ?>
                            </td>
                            <td><?= esc((string) ($s['name'] ?? '')) ?></td>
                            <td><?= esc((string) ($s['grade_label'] ?? '')) ?></td>
                            <td><?= esc((string) ($s['section_name'] ?? '—')) ?></td>
                            <td><span style="color:<?= $color ?>; font-weight:600;"><?= esc(ucfirst($act)) ?> → <?= esc((string) ($s['next_label'] ?? '')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<form method="post" action="<?= base_url('admin/academic-years/close/execute') ?>" onsubmit="return confirm('This will close the current school year and promote students. Continue?');">
    <?= csrf_field() ?>
    <?php foreach ($retainedIds as $rid): ?>
        <input type="hidden" name="retained[]" value="<?= (int) $rid ?>">
    <?php endforeach; ?>

    <div class="card" style="margin-bottom:16px;">
        <div class="card-title">Step 2 — New academic year</div>
        <label style="font-weight:600; color:#1b5e20;">Next year label</label>
        <input type="text" name="next_label" required value="<?= esc($nextLabel) ?>" style="width:100%; max-width:280px; padding:10px; border:1px solid #c8e6c9; border-radius:8px; margin-top:6px;">
        <label style="display:block; margin-top:14px;">
            <input type="checkbox" name="clone_sections" value="1" <?= $cloneSections ? 'checked' : '' ?>>
            Clone section names into the new year (grade +1, schedules updated) and keep teacher assignments on the new sections
        </label>
    </div>

    <div class="card" style="border:2px solid #c62828; margin-bottom:16px;">
        <div class="card-title" style="color:#c62828;">Step 3 — Confirm</div>
        <p style="color:#555;">Type <strong>YES</strong> to run end-of-year promotion.</p>
        <input type="text" name="confirm" placeholder="YES" autocomplete="off" style="width:120px; padding:10px; border:1px solid #ef9a9a; border-radius:8px;">
        <div style="margin-top:14px;">
            <button type="submit" class="login-button" style="display:inline-flex; width:auto; padding:12px 24px; background:#c62828;">Close year &amp; start <?= esc($nextLabel) ?></button>
        </div>
    </div>
</form>

<?php include APPPATH . 'Views/Admin/AcademicYears/_layout_close.php'; ?>
