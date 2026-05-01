<?php
$section = $section ?? [];
$schedule = $schedule ?? [];
$slots = $schedule['slots'] ?? [];
$dismissal = $schedule['dismissal_time'] ?? '15:00';
$sectionAdviserId = (int) ($section_adviser_id ?? 0);
$fmtRange = static function (string $s, string $e): string {
    $a = date('g:i A', strtotime($s));
    $b = date('g:i A', strtotime($e));

    return $a . ' – ' . $b;
};
$fmtTime = static function (string $t): string {
    return date('g:i A', strtotime($t));
};
$oldAdv = old('adviser_teaches');
if (is_array($oldAdv)) {
    $adviserCheckedSlots = array_values(array_unique(array_map('intval', $oldAdv)));
} else {
    $adviserCheckedSlots = [];
    foreach ($slots as $s) {
        if (! empty($s['adviser_teaches'])) {
            $adviserCheckedSlots[] = (int) ($s['slot'] ?? 0);
        }
    }
}
if ($sectionAdviserId <= 0 && ! is_array($oldAdv)) {
    $adviserCheckedSlots = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Section - AJES Admin</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .hint-text { margin-top: 4px; font-size: 0.8rem; color: #558b2f; }
        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.9rem; }
        .schedule-table th, .schedule-table td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #e8f5e9; vertical-align: middle; }
        .schedule-table th { color: #1b5e20; font-weight: 600; }
        .schedule-break td { background: #f1f8e9; color: #33691e; font-size: 0.85rem; }
        .schedule-dismiss td { background: #fff8e1; color: #f57f17; font-size: 0.85rem; }
        .checkbox-row { display: flex; align-items: center; gap: 8px; margin-top: 6px; color: #2e7d32; }
    </style>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Edit section</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Section #<?= esc($section['id']) ?></div>
        <form action="<?= base_url('admin/sections/update/' . $section['id']) ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name" style="color: #1b5e20;">Section name</label>
                <input type="text" id="name" name="name" required value="<?= esc($section['name']) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="grade_level" style="color: #1b5e20;">Grade level</label>
                <input type="text" id="grade_level" name="grade_level" required value="<?= esc($section['grade_level']) ?>" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label style="color: #1b5e20; display: block; margin-bottom: 6px;">Daily class schedule — five subjects (1 hour each)</label>
                <div class="hint-text" style="margin-bottom: 8px;">Two classes before recess, one before lunch, two after lunch. Enter each subject name for the time shown.</div>
                <?php if ($sectionAdviserId <= 0): ?>
                    <p class="hint-text" style="color: #6d4c41;">No adviser on this section yet. Use <strong>Invite teachers</strong> to assign an adviser, then you can mark up to two slots here.</p>
                <?php else: ?>
                    <p class="hint-text">Mark up to <strong>two</strong> slots the current class adviser teaches (subject name required for each checked slot).</p>
                <?php endif; ?>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th style="width: 36%;">Class time</th>
                            <th>Subject</th>
                            <th style="width: 22%; text-align: center;">Adviser teaches<br><span style="font-weight:400;font-size:0.8rem;">(max 2)</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $si = 0; ?>
                        <?php foreach ($slots as $slot): ?>
                            <?php if ($si === 2): ?>
                                <tr class="schedule-break">
                                    <td colspan="3"><strong>Recess</strong> — <?= $fmtRange('09:45', '10:00') ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($si === 3): ?>
                                <tr class="schedule-break">
                                    <td colspan="3"><strong>Lunch break</strong> — <?= $fmtRange('11:00', '13:00') ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php
                            $sn = (int) ($slot['slot'] ?? ($si + 1));
                            $defSubj = (string) ($slot['subject'] ?? '');
                            $fieldVal = old('schedule_subj_' . $sn, $defSubj);
                            ?>
                            <tr>
                                <td><?= esc($fmtRange((string) ($slot['start'] ?? ''), (string) ($slot['end'] ?? ''))) ?> <span style="color:#888;">(1 hr)</span></td>
                                <td>
                                    <input type="text" name="schedule_subj_<?= $sn ?>" value="<?= esc((string) $fieldVal) ?>" placeholder="e.g. Mathematics" style="width: 100%; max-width: 320px; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                                </td>
                                <td style="text-align: center;">
                                    <label class="checkbox-row" style="justify-content: center; margin: 0;">
                                        <input type="checkbox" class="adviser-teach-cb" name="adviser_teaches[]" value="<?= $sn ?>" <?= in_array($sn, $adviserCheckedSlots, true) ? 'checked' : '' ?> <?= $sectionAdviserId <= 0 ? 'disabled' : '' ?>>
                                        <span style="font-size: 0.85rem;">This slot</span>
                                    </label>
                                </td>
                            </tr>
                            <?php $si++; ?>
                        <?php endforeach; ?>
                        <tr class="schedule-dismiss">
                            <td colspan="3"><strong>Dismissal</strong> — <?= esc($fmtTime($dismissal)) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 24px;">Update</button>
            <a href="<?= base_url('admin/sections') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    <script>
    (function() {
        var boxes = document.querySelectorAll('.adviser-teach-cb');
        if (!boxes.length) return;
        function onBoxChange(changed) {
            if (changed.disabled) return;
            var checked = Array.prototype.filter.call(boxes, function(c) {
                return c.checked && !c.disabled;
            });
            if (checked.length > 2) {
                changed.checked = false;
                alert('Maximum 2 subjects for the adviser.');
            }
        }
        boxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                onBoxChange(cb);
            });
        });
    })();
    </script>

    </main>
</div>
</body>
</html>
