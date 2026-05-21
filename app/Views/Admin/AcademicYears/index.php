<?php
$years = $years ?? [];
$activeYear = $active_year ?? null;
$statusColors = [
    'active'   => '#2e7d32',
    'planning' => '#9e9e9e',
    'closed'   => '#6a6a6a',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Years - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .ay-badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; color:#fff; }
        .ay-hero { background:linear-gradient(135deg,#43a047 0%,#66bb6a 100%); color:#fff; border-radius:12px; padding:20px 24px; margin-bottom:20px; border:1px solid #c8e6c9; }
        .ay-stat { display:inline-block; margin-right:24px; font-size:14px; opacity:0.95; }
        .ay-toggle-form { display:inline-flex; align-items:center; margin:0; }
        .ay-toggle {
            position:relative; width:52px; height:28px; border:none; border-radius:999px;
            cursor:pointer; padding:0; transition:background 0.2s ease;
            background:#bdbdbd; box-shadow:inset 0 1px 3px rgba(0,0,0,0.15);
        }
        .ay-toggle.is-on { background:#2e7d32; }
        .ay-toggle-knob {
            position:absolute; top:3px; left:3px; width:22px; height:22px;
            border-radius:50%; background:#fff; transition:left 0.2s ease;
            box-shadow:0 1px 4px rgba(0,0,0,0.2);
        }
        .ay-toggle.is-on .ay-toggle-knob { left:27px; }
        .ay-toggle-label { font-size:12px; font-weight:600; margin-left:8px; color:#558b2f; }
    </style>
</head>
<body>
<?php include(APPPATH . 'Views/template/index.php'); ?>
<?php include APPPATH . 'Views/Admin/AcademicYears/_layout_open.php'; ?>

<?php if ($activeYear): ?>
<div class="ay-hero">
    <div style="font-size:13px; text-transform:uppercase; letter-spacing:1px; opacity:0.85;">Current school year</div>
    <div style="font-size:28px; font-weight:700; margin:6px 0 10px;"><?= esc((string) ($activeYear['label'] ?? '')) ?></div>
    <div class="ay-stat">Status: <strong>Active</strong></div>
    <?php if (! empty($activeYear['start_date'])): ?>
        <div class="ay-stat"><?= esc((string) $activeYear['start_date']) ?> → <?= esc((string) ($activeYear['end_date'] ?? '—')) ?></div>
    <?php endif; ?>
    <p style="margin:14px 0 0; font-size:0.9rem; opacity:0.9;">Use <strong>End school year</strong> in the sidebar when the school year ends (promotes students and clears section assignments for the new year).</p>
</div>
<?php else: ?>
    <div class="message">No active academic year. Create one below and set it as active.</div>
<?php endif; ?>

<div class="card" id="create-year" style="margin-bottom:20px;">
    <div class="card-title">Create academic year</div>
    <p style="color:#558b2f; margin-bottom:12px;">Plan ahead (e.g. <?= esc(\App\Libraries\AcademicYearManager::suggestNextLabel($activeYear['label'] ?? null)) ?>) or register a past year for records.</p>
    <form method="post" action="<?= base_url('admin/academic-years/store') ?>" style="display:grid; gap:12px; max-width:480px;">
        <?= csrf_field() ?>
        <div>
            <label style="font-weight:600; color:#1b5e20;">Label (YYYY–YYYY)</label>
            <input type="text" name="label" required placeholder="2026–2027" value="<?= esc(old('label', \App\Libraries\AcademicYearManager::suggestNextLabel($activeYear['label'] ?? null))) ?>" style="width:100%; padding:10px; border:1px solid #c8e6c9; border-radius:8px;">
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div>
                <label style="font-weight:600; color:#1b5e20;">Start date</label>
                <input type="date" name="start_date" value="<?= esc(old('start_date')) ?>" style="width:100%; padding:10px; border:1px solid #c8e6c9; border-radius:8px;">
            </div>
            <div>
                <label style="font-weight:600; color:#1b5e20;">End date</label>
                <input type="date" name="end_date" value="<?= esc(old('end_date')) ?>" style="width:100%; padding:10px; border:1px solid #c8e6c9; border-radius:8px;">
            </div>
        </div>
        <div>
            <label style="font-weight:600; color:#1b5e20;">After creating</label>
            <select name="status" style="width:100%; padding:10px; border:1px solid #c8e6c9; border-radius:8px;">
                <option value="planning">Save as inactive</option>
                <option value="active">Save and set as active</option>
            </select>
        </div>
        <button type="submit" class="login-button" style="width:auto; display:inline-flex; padding:10px 22px;">Create academic year</button>
    </form>
</div>

<div class="card">
    <div class="card-title">All academic years</div>
    <table class="recent-table">
        <thead>
            <tr>
                <th>Year</th>
                <th>Status</th>
                <th>Active / Inactive</th>
                <th>Dates</th>
                <th>Enrollment records</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($years === []): ?>
                <tr><td colspan="6">No academic years yet.</td></tr>
            <?php else: ?>
                <?php foreach ($years as $y): ?>
                    <?php
                        $st = (string) ($y['status'] ?? 'planning');
                        $color = $statusColors[$st] ?? '#333';
                    ?>
                    <?php
                        $yearId = (int) ($y['id'] ?? 0);
                        $isOn = $st === 'active';
                        $canToggle = $st !== 'closed';
                    ?>
                    <tr>
                        <td><strong><?= esc((string) ($y['label'] ?? '')) ?></strong></td>
                        <td><span class="ay-badge" style="background:<?= $color ?>;"><?= esc((string) ($y['status_label'] ?? $st)) ?></span></td>
                        <td>
                            <?php if ($canToggle): ?>
                                <form method="post" action="<?= base_url('admin/academic-years/toggle/' . $yearId) ?>" class="ay-toggle-form"
                                      onsubmit="return confirm('<?= $isOn ? 'Deactivate' : 'Activate' ?> <?= esc((string) ($y['label'] ?? '')) ?>?<?= $isOn ? '' : ' Other active years will become inactive.' ?>');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="ay-toggle <?= $isOn ? 'is-on' : '' ?>" title="<?= $isOn ? 'Click to deactivate' : 'Click to activate' ?>" aria-label="<?= $isOn ? 'Deactivate' : 'Activate' ?> academic year">
                                        <span class="ay-toggle-knob"></span>
                                    </button>
                                    <span class="ay-toggle-label"><?= $isOn ? 'Active' : 'Inactive' ?></span>
                                </form>
                            <?php else: ?>
                                <span style="color:#888; font-size:13px;">Locked</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:13px;">
                            <?= esc((string) ($y['start_date'] ?? '—')) ?>
                            <?php if (! empty($y['end_date'])): ?> → <?= esc((string) $y['end_date']) ?><?php endif; ?>
                            <?php if (! empty($y['closed_at'])): ?><br><span style="color:#888;">Closed <?= esc((string) $y['closed_at']) ?></span><?php endif; ?>
                        </td>
                        <td><?= esc((string) ($y['enrollment_count'] ?? 0)) ?></td>
                        <td style="white-space:nowrap;">
                            <a href="<?= base_url('admin/academic-years/history/' . $yearId) ?>" class="link-details">View records</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:20px; background:#f1f8e9;">
    <div class="card-title">How it works</div>
    <ul style="color:#33691e; line-height:1.7; margin:0; padding-left:20px;">
        <li>Use the <strong>toggle</strong> to activate or deactivate a year. Only one year can be active at a time.</li>
        <li>Students, sections, and schedules are tied to the <strong>active</strong> academic year.</li>
        <li>Closing a year <strong>archives</strong> enrollments and subjects (not deleted), promotes grade levels, and clears section assignments for reassignment.</li>
        <li><strong>Retained</strong> students stay in the same grade; <strong>Grade 6</strong> completers are marked graduated.</li>
        <li>Optional: clone sections into the new year — teacher assignments (adviser &amp; subject) are carried over automatically.</li>
    </ul>
</div>

<?php include APPPATH . 'Views/Admin/AcademicYears/_layout_close.php'; ?>
