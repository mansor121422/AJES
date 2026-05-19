<?php
$section = $section ?? [];
$schedule = $schedule ?? [];
$depedSubjectsByGrade = $deped_subjects_by_grade ?? [];
$slots = $schedule['slots'] ?? [];
$dismissal = $schedule['dismissal_time'] ?? '15:30';
$sectionAdviserId = (int) ($section_adviser_id ?? 0);
$sectionAdviserStatus = strtolower((string) ($section_adviser_status ?? ''));
$availableTeachers = $available_teachers ?? [];
$selectedTeacherId = (string) old('teacher_id', (string) ($sectionAdviserId > 0 ? $sectionAdviserId : ''));
$fmtRange = static function (string $s, string $e): string {
    $a = date('g:i A', strtotime($s));
    $b = date('g:i A', strtotime($e));

    return $a . ' ? ' . $b;
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

            <div class="form-group">
                <label for="teacher_id" style="color: #1b5e20;">Change teacher (class adviser)</label>
                <select id="teacher_id" name="teacher_id" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">-- No teacher --</option>
                    <?php foreach ($availableTeachers as $teacher): ?>
                        <?php $tid = (int) ($teacher['id'] ?? 0); ?>
                        <option value="<?= $tid ?>" <?= $selectedTeacherId === (string) $tid ? 'selected' : '' ?>>
                            <?= esc($teacher['name'] ?? $teacher['username'] ?? ('Teacher #' . $tid)) ?>
                            <?php if ($tid === $sectionAdviserId && $sectionAdviserStatus === 'pending'): ?>
                                (current - pending)
                            <?php elseif ($tid === $sectionAdviserId): ?>
                                (current)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint-text">Only teachers who are <strong>not already an adviser in another section</strong> are listed.</div>
                <?php if ($sectionAdviserId > 0 && $sectionAdviserStatus === 'pending'): ?>
                    <p class="hint-text" style="margin-top: 6px;">Current adviser has not accepted yet. Check <strong>Assign immediately</strong> below to activate without waiting.</p>
                <?php endif; ?>
                <label class="checkbox-row" style="margin-top: 10px;">
                    <input type="checkbox" name="assign_now" value="1" <?= old('assign_now') === '1' || ($sectionAdviserStatus === 'accepted' && old('assign_now', null) === null) ? 'checked' : '' ?>>
                    Assign immediately (skip invite - teacher sees the section right away)
                </label>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label style="color: #1b5e20; display: block; margin-bottom: 6px;">Daily class schedule - eight subjects (50 minutes each)</label>
                <div class="hint-text" style="margin-bottom: 8px;">Times are fixed. Subjects are pre-listed based on grade level.</div>
                <p id="edit-adviser-slots-need-teacher" class="hint-text" style="display: none; color: #c62828;">Select a class adviser above first, then mark adviser slots.</p>
                <p id="edit-adviser-grade-rule-hint" class="hint-text">Mark adviser slots based on grade rule.</p>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th style="width: 36%;">Class time</th>
                            <th>Subject</th>
                            <th style="width: 22%; text-align: center;">Adviser teaches<br><span id="edit-adviser-max-label" style="font-weight:400;font-size:0.8rem;"></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $si = 0; ?>
                        <?php foreach ($slots as $slot): ?>
                            <?php if ($si === 2): ?>
                                <tr class="schedule-break">
                                    <td colspan="3"><strong>Recess</strong> ? <?= $fmtRange('09:10', '09:30') ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($si === 5): ?>
                                <tr class="schedule-break">
                                    <td colspan="3"><strong>Lunch break</strong> ? <?= $fmtRange('12:00', '13:00') ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php
                            $sn = (int) ($slot['slot'] ?? ($si + 1));
                            $defSubj = (string) ($slot['subject'] ?? '');
                            $fieldVal = old('schedule_subj_' . $sn, $defSubj);
                            ?>
                            <tr>
                                <td><?= esc($fmtRange((string) ($slot['start'] ?? ''), (string) ($slot['end'] ?? ''))) ?> <span style="color:#888;">(50 min)</span></td>
                                <td>
                                    <select class="schedule-subject-select" data-slot="<?= $sn ?>" name="schedule_subj_<?= $sn ?>" style="width: 100%; max-width: 320px; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                                        <option value="">Select subject</option>
                                        <?php $fieldValTrim = trim((string) $fieldVal); ?>
                                        <?php if ($fieldValTrim !== ''): ?>
                                            <option value="<?= esc($fieldValTrim) ?>" selected><?= esc($fieldValTrim) ?></option>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td style="text-align: center;">
                                    <label class="checkbox-row" style="justify-content: center; margin: 0;">
                                        <input type="checkbox" class="adviser-teach-cb" name="adviser_teaches[]" value="<?= $sn ?>" <?= in_array($sn, $adviserCheckedSlots, true) ? 'checked' : '' ?> <?= $selectedTeacherId === '' ? 'disabled' : '' ?>>
                                        <span style="font-size: 0.85rem;">This slot</span>
                                    </label>
                                </td>
                            </tr>
                            <?php $si++; ?>
                        <?php endforeach; ?>
                        <tr class="schedule-dismiss">
                            <td colspan="3"><strong>Dismissal</strong> ? <?= esc($fmtTime($dismissal)) ?></td>
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
        var depedSubjectsByGrade = <?= json_encode($depedSubjectsByGrade) ?>;
        var gradeEl = document.getElementById('grade_level');
        var selects = document.querySelectorAll('.schedule-subject-select');
        if (!gradeEl || !selects.length) return;

        function gradeDigit(raw) {
            var t = String(raw || '').trim();
            var m = t.match(/([1-6])/);
            return m ? m[1] : '';
        }

        function rebuildSubjectOptions() {
            var g = gradeDigit(gradeEl.value);
            var subjectList = depedSubjectsByGrade[g] || [];
            selects.forEach(function(sel, idx) {
                var existing = (sel.value || '').trim();
                sel.innerHTML = '';
                var ph = document.createElement('option');
                ph.value = '';
                ph.textContent = 'Select subject';
                sel.appendChild(ph);
                subjectList.forEach(function(subj) {
                    var opt = document.createElement('option');
                    opt.value = subj;
                    opt.textContent = subj;
                    if (existing !== '' ? existing === subj : idx < subjectList.length && subjectList[idx] === subj) {
                        opt.selected = true;
                    }
                    sel.appendChild(opt);
                });
                if (existing !== '' && subjectList.indexOf(existing) === -1) {
                    var keep = document.createElement('option');
                    keep.value = existing;
                    keep.textContent = existing;
                    keep.selected = true;
                    sel.appendChild(keep);
                }
            });
        }
        gradeEl.addEventListener('change', rebuildSubjectOptions);
        rebuildSubjectOptions();
    })();

    (function() {
        var teacherEl = document.getElementById('teacher_id');
        var boxes = document.querySelectorAll('.adviser-teach-cb');
        var gradeEl = document.getElementById('grade_level');
        var gradeHint = document.getElementById('edit-adviser-grade-rule-hint');
        var maxLabelEl = document.getElementById('edit-adviser-max-label');
        var needTeacherHint = document.getElementById('edit-adviser-slots-need-teacher');
        if (!boxes.length) return;

        function hasTeacher() {
            return teacherEl && (teacherEl.value || '').trim() !== '';
        }

        function syncTeacherGate() {
            var ok = hasTeacher();
            if (needTeacherHint) {
                needTeacherHint.style.display = ok ? 'none' : 'block';
            }
            boxes.forEach(function(cb) {
                if (!ok) {
                    cb.disabled = true;
                    cb.checked = false;
                }
            });
        }

        function isGradeAdviserOnly() {
            var raw = (gradeEl && gradeEl.value ? String(gradeEl.value) : '').trim();
            var m = raw.match(/([1-6])/);
            if (!m) return false;
            var n = parseInt(m[1], 10);
            return !isNaN(n) && n >= 1 && n <= 3;
        }

        function syncGradeRule() {
            syncTeacherGate();
            if (!hasTeacher()) {
                return;
            }
            var adviserOnly = isGradeAdviserOnly();
            boxes.forEach(function(cb) {
                cb.disabled = adviserOnly;
                if (adviserOnly) {
                    cb.checked = true;
                }
            });
            if (gradeHint) {
                if (adviserOnly) {
                    gradeHint.innerHTML = 'Grade 1 to 3 rule: class adviser handles <strong>all subjects</strong> (subject teacher not allowed).';
                } else {
                    gradeHint.innerHTML = 'Grade 4 to 6: mark up to <strong>two</strong> slots the current adviser teaches.';
                }
            }
            if (maxLabelEl) {
                maxLabelEl.textContent = adviserOnly ? '' : '(max 2)';
            }
        }

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
        if (gradeEl) {
            gradeEl.addEventListener('change', syncGradeRule);
        }
        if (teacherEl) {
            teacherEl.addEventListener('change', syncGradeRule);
        }
        syncGradeRule();
    })();
    </script>

    </main>
</div>
</body>
</html>
