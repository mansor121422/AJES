<?php
$students    = $students ?? [];
$keyword     = $keyword ?? '';
$grade       = $grade ?? '';
$totalCount  = (int) ($total_count ?? count($students));
$activeCount = (int) ($active_count ?? 0);

$dash = static function (?string $value): string {
    $v = trim((string) $value);

    return $v !== '' ? $v : '—';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Log - AJES Admin</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .students-log-intro {
            color: #558b2f;
            font-size: 0.95rem;
            margin: 0;
            line-height: 1.5;
        }
        .students-log-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .students-log-stat {
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
            border: 1px solid #c8e6c9;
            border-radius: 12px;
            padding: 16px 18px;
        }
        .students-log-stat strong {
            display: block;
            font-size: 1.75rem;
            color: #1b5e20;
            line-height: 1.1;
        }
        .students-log-stat span {
            font-size: 0.85rem;
            color: #558b2f;
        }
        .students-log-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }
        .students-log-toolbar .field {
            flex: 1;
            min-width: 200px;
        }
        .students-log-toolbar .field-narrow {
            min-width: 140px;
            flex: 0 1 160px;
        }
        .students-log-toolbar label {
            display: block;
            font-size: 0.82rem;
            color: #2e7d32;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .students-log-toolbar input,
        .students-log-toolbar select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .students-log-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .students-log-table-wrap {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        .students-log-table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .students-log-table th {
            background: #2e7d32;
            color: #fff;
            font-weight: 600;
            text-align: left;
            padding: 12px 14px;
            white-space: nowrap;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .students-log-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e8f5e9;
            vertical-align: top;
            color: #333;
        }
        .students-log-table tbody tr:hover {
            background: #f9fdf7;
        }
        .students-log-table tbody tr:last-child td {
            border-bottom: none;
        }
        .students-log-name {
            font-weight: 600;
            color: #1b5e20;
        }
        .students-log-meta {
            font-size: 0.78rem;
            color: #888;
            margin-top: 2px;
        }
        .students-log-lrn {
            font-family: ui-monospace, Consolas, monospace;
            font-size: 0.88rem;
            color: #33691e;
        }
        .students-log-contact {
            font-family: ui-monospace, Consolas, monospace;
            font-size: 0.88rem;
            white-space: nowrap;
        }
        .students-log-address {
            max-width: 180px;
            font-size: 0.85rem;
            color: #555;
            line-height: 1.35;
        }
        .badge-active {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: #c8e6c9;
            color: #1b5e20;
        }
        .badge-inactive {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: #ffebee;
            color: #c62828;
        }
        .btn-edit-student {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 8px;
            background: #e8f5e9;
            color: #2e7d32 !important;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            border: 1px solid #a5d6a7;
        }
        .btn-edit-student:hover {
            background: #c8e6c9;
        }
        .students-log-empty {
            text-align: center;
            padding: 40px 20px;
            color: #558b2f;
        }
        .students-log-empty p {
            margin: 8px 0 0;
        }
    </style>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Students Log</h1>

    <div class="card" style="margin-bottom: 18px;">
        <p class="students-log-intro">
            Registered students with LRN, age, gender, guardian details, and section assignment
            <?php if (! empty($active_year['label'])): ?>
                for <strong><?= esc((string) $active_year['label']) ?></strong>
            <?php endif; ?>.
            Guardian phone numbers are stored securely and shown here after decryption.
            <a href="<?= base_url('admin/academic-years') ?>" class="link-details">Academic years &amp; history</a>
        </p>
    </div>

    <div class="students-log-stats">
        <div class="students-log-stat">
            <strong><?= $totalCount ?></strong>
            <span>Total students</span>
        </div>
        <div class="students-log-stat">
            <strong><?= $activeCount ?></strong>
            <span>Active accounts</span>
        </div>
        <div class="students-log-stat">
            <strong><?= max(0, $totalCount - $activeCount) ?></strong>
            <span>Inactive</span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 18px;">
        <div class="card-title">Find students</div>
        <form method="get" action="<?= base_url('admin/students-log') ?>" class="students-log-toolbar">
            <div class="field">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" value="<?= esc($keyword) ?>" placeholder="Name, LRN, guardian, email...">
            </div>
            <div class="field field-narrow">
                <label for="grade">Grade</label>
                <select id="grade" name="grade">
                    <option value="">All grades</option>
                    <?php for ($g = 1; $g <= 6; $g++): ?>
                        <option value="<?= $g ?>" <?= (string) $grade === (string) $g ? 'selected' : '' ?>>Grade <?= $g ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="students-log-actions">
                <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 22px;">Search</button>
                <a href="<?= base_url('admin/users/create') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 22px; text-decoration: none;">+ Create student</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-title" style="margin-bottom: 14px;">Student records</div>
        <?php if ($students === []): ?>
            <div class="students-log-empty">
                <strong>No students found</strong>
                <p>Try a different search or <a href="<?= base_url('admin/users/create') ?>" class="link-details">create a new student</a>.</p>
            </div>
        <?php else: ?>
            <div class="students-log-table-wrap">
                <table class="students-log-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>LRN</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Profile</th>
                            <th>Guardian</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td>
                                    <div class="students-log-name"><?= esc($s['display_name'] ?? '') ?></div>
                                    <div class="students-log-meta">
                                        ID #<?= esc($s['id']) ?>
                                        · <a href="<?= base_url('admin/academic-years/student/' . (int) ($s['id'] ?? 0)) ?>" class="link-details">AY history</a>
                                    </div>
                                </td>
                                <td class="students-log-lrn"><?= $dash($s['student_id'] ?? '') ?></td>
                                <td style="white-space:nowrap; font-weight:600; color:#1b5e20;"><?= esc($s['grade_label'] ?? '—') ?></td>
                                <td style="white-space:nowrap;"><?= esc($s['section_label'] ?? '—') ?></td>
                                <td>
                                    <div><?= $dash($s['gender'] ?? '') ?> &middot; Age <?= $dash((string) ($s['age'] ?? '')) ?></div>
                                    <div class="students-log-meta"><?= esc($s['birthdate_display'] ?? '—') ?></div>
                                    <div class="students-log-meta"><?= esc($s['student_type_label'] ?? '—') ?></div>
                                    <?php if (! empty($s['previous_school_display']) && $s['previous_school_display'] !== '—'): ?>
                                        <div class="students-log-meta">Prev. school: <?= esc($s['previous_school_display']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($s['guardian_name_display'] ?? '—') ?></td>
                                <td class="students-log-contact"><?= esc($s['guardian_contact_display'] ?? '—') ?></td>
                                <td class="students-log-address" title="<?= esc($s['address_display'] ?? '') ?>"><?= esc($s['address_display'] ?? '—') ?></td>
                                <td>
                                    <?php if (! empty($s['is_active'])): ?>
                                        <span class="badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/users/edit/' . (int) $s['id']) ?>" class="btn-edit-student">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    </main>
</div>
</body>
</html>
