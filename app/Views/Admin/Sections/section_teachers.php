<?php
$section               = $section ?? [];
$assignments           = $assignments ?? [];
$teachers              = $teachers ?? [];
$teachers_for_invite   = $teachers_for_invite ?? $teachers;
$assignable_subjects   = $assignable_subjects ?? [];
$remaining_subjects       = $remaining_subjects ?? [];
$adviser_subjects_label   = $adviser_subjects_label ?? '—';
$section_schedule_rows    = $section_schedule_rows ?? [];
$invite_subject_time_meta = $invite_subject_time_meta ?? [];
$teacher_busy_by_id       = $teacher_busy_by_id ?? [];
$is_adviser_only_grade    = ! empty($is_adviser_only_grade);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite teachers - <?= esc($section['name']) ?></title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header"><?= esc($section['name']) ?> — Invite teachers</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Daily schedule (this section)</div>
        <p style="margin-bottom: 8px; color: #558b2f; font-size: 0.9rem;">Each subject has a fixed time. Subject-teacher invites use these slots; the system blocks teachers who already have another class at the same time.</p>
        <table class="recent-table" style="margin-bottom: 16px;">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Time</th>
                    <th>Who teaches</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($section_schedule_rows)): ?>
                    <tr><td colspan="3">No schedule data.</td></tr>
                <?php else: ?>
                    <?php foreach ($section_schedule_rows as $sr): ?>
                        <tr>
                            <td><?= esc($sr['subject'] ?? '') ?></td>
                            <td><?= esc($sr['time_label'] ?? '') ?></td>
                            <td><?= esc($sr['taught_note'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-title">Invite subject teacher</div>
        <?php if ($is_adviser_only_grade): ?>
            <p style="margin-bottom: 12px; color: #c62828;">This section is Grade 1 to 3, so it is <strong>adviser-only</strong>. Subject teacher invites are disabled.</p>
        <?php else: ?>
            <p style="margin-bottom: 12px; color: #558b2f;">Invite teachers for subjects in the section schedule that the <strong>class adviser does not</strong> teach. The class adviser is not listed here. Teachers must accept the invite before they appear as assigned.</p>
        <?php endif; ?>
        <form id="invite-teacher-form" action="<?= base_url('admin/sections/invite') ?>" method="post" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <?= csrf_field() ?>
            <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
            <div style="min-width: 200px;">
                <label for="teacher_id" style="display: block; font-size: 0.85rem; color: #2e7d32; margin-bottom: 4px;">Teacher</label>
                <select id="teacher_id" name="teacher_id" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">Select teacher</option>
                    <?php foreach ($teachers_for_invite as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= esc($t['name']) ?> (<?= esc($t['username']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="min-width: 220px;">
                <label for="subject_name" style="display: block; font-size: 0.85rem; color: #2e7d32; margin-bottom: 4px;">Subject</label>
                <select id="subject_name" name="subject_name" <?= empty($remaining_subjects) ? '' : 'required' ?> style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <?php if (empty($remaining_subjects)): ?>
                        <option value="">No open subjects — all non‑adviser slots filled or edit section schedule</option>
                    <?php else: ?>
                        <option value="">Select subject</option>
                        <?php foreach ($remaining_subjects as $sub): ?>
                            <?php
                            $meta = $invite_subject_time_meta[$sub] ?? [];
                            $tl   = trim((string) ($meta['label'] ?? ''));
                            $optLabel = $tl !== '' ? $sub . ' — ' . $tl : $sub;
                            ?>
                            <option value="<?= esc($sub) ?>" data-time-label="<?= esc($tl) ?>"><?= esc($optLabel) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div id="invite-availability-wrap" style="flex-basis: 100%; display: none;">
                <p id="invite-availability-msg" style="margin: 0; padding: 10px 12px; border-radius: 8px; font-size: 0.9rem;"></p>
            </div>
            <button type="submit" id="invite-submit-btn" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px;" <?= (empty($remaining_subjects) || $is_adviser_only_grade) ? 'disabled' : '' ?>>Send invite</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Teachers in this section</div>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Role</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignments)): ?>
                    <tr><td colspan="5">No teachers invited yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($assignments as $a): ?>
                        <?php
                        $aid = (int) ($a['id'] ?? 0);
                        $curTeacherId = (int) ($a['teacher_id'] ?? 0);
                        $curRole = strtoupper((string) ($a['assignment_role'] ?? 'ADVISER'));
                        if (! in_array($curRole, ['ADVISER', 'SUBJECT_TEACHER'], true)) {
                            $curRole = 'ADVISER';
                        }
                        $curSubj = trim((string) ($a['subject_name'] ?? ''));
                        $teacherDisplay = 'User #' . $curTeacherId;
                        foreach ($teachers as $t) {
                            if ((int) ($t['id'] ?? 0) === $curTeacherId) {
                                $teacherDisplay = esc($t['name']) . ' (' . esc($t['username'] ?? '') . ')';
                                break;
                            }
                        }
                        $roleDisplay = $curRole === 'ADVISER' ? 'Adviser' : 'Subject Teacher';
                        $rowOpts = $assignable_subjects;
                        if ($curSubj !== '') {
                            $found = false;
                            foreach ($rowOpts as $ro) {
                                if (strcasecmp(trim((string) $ro), $curSubj) === 0) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (! $found) {
                                $rowOpts = array_merge([$curSubj], $rowOpts);
                            }
                        }
                        ?>
                        <tr>
                            <form action="<?= base_url('admin/sections/assignment/update/' . $aid) ?>" method="post" style="display: contents;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
                                <td>
                                    <input type="hidden" name="teacher_id" value="<?= $curTeacherId ?>">
                                    <span style="display: inline-block; padding: 8px 10px; background: #f1f8e9; border: 1px solid #c8e6c9; border-radius: 8px; max-width: 260px;"><?= $teacherDisplay ?></span>
                                </td>
                                <td>
                                    <input type="hidden" name="assignment_role" value="<?= esc($curRole) ?>">
                                    <span style="display: inline-block; padding: 8px 10px; background: #f1f8e9; border: 1px solid #c8e6c9; border-radius: 8px;"><?= esc($roleDisplay) ?></span>
                                </td>
                                <td>
                                    <div class="row-adviser-subject-block" data-row="<?= $aid ?>" style="<?= $curRole === 'ADVISER' ? '' : 'display:none;' ?>">
                                        <input type="hidden" name="subject_name" value="" class="row-adj-sub-empty" <?= $curRole === 'SUBJECT_TEACHER' ? 'disabled' : '' ?>>
                                        <span style="color:#1b5e20;"><?= esc($adviser_subjects_label) ?></span>
                                        <span style="display:block;font-size:0.75rem;color:#888;margin-top:4px;">From section schedule (adviser’s classes)</span>
                                    </div>
                                    <div class="row-subject-select-block" data-row="<?= $aid ?>" style="<?= $curRole === 'SUBJECT_TEACHER' ? '' : 'display:none;' ?>">
                                        <select name="subject_name" class="row-subject-select" data-row="<?= $aid ?>" <?= $curRole === 'ADVISER' ? 'disabled' : '' ?> <?= $curRole === 'SUBJECT_TEACHER' ? 'required' : '' ?> style="width: 100%; max-width: 220px; padding: 8px; border: 1px solid #c8e6c9; border-radius: 8px;">
                                            <?php if (empty($rowOpts)): ?>
                                                <option value="">Add non‑adviser subjects in section schedule</option>
                                            <?php endif; ?>
                                            <?php foreach ($rowOpts as $opt): ?>
                                                <option value="<?= esc($opt) ?>" <?= $curSubj !== '' && strcasecmp($opt, $curSubj) === 0 ? 'selected' : '' ?>><?= esc($opt) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <?php if (($a['status'] ?? '') === 'accepted'): ?>
                                        <span class="status-badge status-badge-approved">Accepted</span>
                                    <?php else: ?>
                                        <span class="status-badge status-badge-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: nowrap;">
                                    <button type="submit" class="login-button" style="display: inline-flex; padding: 6px 12px; font-size: 0.85rem;">Save</button>
                                    <a href="<?= base_url('admin/sections/assignment/delete/' . $aid) ?>" class="link-details" style="margin-left: 10px;" onclick="return confirm('Remove this teacher assignment?')">Delete</a>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p><a href="<?= base_url('admin/sections') ?>" class="link-details">← Back to sections</a></p>

    <script>
    (function() {
        var busyMap = <?= json_encode($teacher_busy_by_id) ?>;
        var metaMap = <?= json_encode($invite_subject_time_meta) ?>;
        var form = document.getElementById('invite-teacher-form');
        var teacherEl = document.getElementById('teacher_id');
        var subjectEl = document.getElementById('subject_name');
        var wrap = document.getElementById('invite-availability-wrap');
        var msg = document.getElementById('invite-availability-msg');
        var submitBtn = document.getElementById('invite-submit-btn');
        var noSubjects = <?= json_encode(empty($remaining_subjects)) ?>;
        if (!form || !teacherEl || !subjectEl || !wrap || !msg) return;

        function toMin(hm) {
            if (!hm || typeof hm !== 'string') return NaN;
            var p = hm.trim().split(':');
            return (parseInt(p[0], 10) || 0) * 60 + (parseInt(p[1], 10) || 0);
        }
        function overlap(a1, a2, b1, b2) {
            return a1 < b2 && b1 < a2;
        }
        function syncAvailability() {
            var tid = teacherEl.value;
            var sub = subjectEl.value;
            if (!tid || !sub || !submitBtn) {
                wrap.style.display = 'none';
                submitBtn.disabled = !!noSubjects;
                return;
            }
            var m = metaMap[sub];
            if (!m || !m.start || !m.end) {
                wrap.style.display = 'none';
                return;
            }
            var ns = toMin(m.start);
            var ne = toMin(m.end);
            var ranges = busyMap[tid] || [];
            var bad = null;
            for (var i = 0; i < ranges.length; i++) {
                var r = ranges[i];
                if (overlap(toMin(r.start), toMin(r.end), ns, ne)) {
                    bad = r;
                    break;
                }
            }
            wrap.style.display = 'block';
            if (bad) {
                msg.style.background = '#ffebee';
                msg.style.color = '#b71c1c';
                msg.style.border = '1px solid #ffcdd2';
                msg.textContent = 'Not available: this teacher already has a class that overlaps ' + (m.label || '') + ' — ' + bad.label + ' at ' +
                    formatLbl(bad.start, bad.end) + '. Choose another teacher or time slot is blocked.';
                submitBtn.disabled = true;
            } else {
                msg.style.background = '#e8f5e9';
                msg.style.color = '#1b5e20';
                msg.style.border = '1px solid #c8e6c9';
                msg.textContent = 'Available for ' + (m.label || 'this slot') + ' — no overlapping class found for this teacher.';
                submitBtn.disabled = !!noSubjects;
            }
        }
        function formatLbl(s, e) {
            function clk(hm) {
                var p = (hm || '').split(':');
                var h = parseInt(p[0], 10), m = parseInt(p[1], 10) || 0;
                var am = h >= 12 ? 'PM' : 'AM';
                var hr = h % 12; if (hr === 0) hr = 12;
                return hr + ':' + (m < 10 ? '0' : '') + m + ' ' + am;
            }
            return clk(s) + ' – ' + clk(e);
        }
        teacherEl.addEventListener('change', syncAvailability);
        subjectEl.addEventListener('change', syncAvailability);
        syncAvailability();
    })();
    </script>

    </main>
</div>
</body>
</html>
