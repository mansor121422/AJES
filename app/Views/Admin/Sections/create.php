<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Section - AJES Admin</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .hint-text { margin-top: 4px; font-size: 0.8rem; color: #558b2f; }
        .checkbox-row { display: flex; align-items: center; gap: 8px; margin-top: 6px; color: #2e7d32; }
        .custom-section-wrap { margin-top: 8px; display: none; }
        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.9rem; }
        .schedule-table th, .schedule-table td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #e8f5e9; vertical-align: middle; }
        .schedule-table th { color: #1b5e20; font-weight: 600; }
        .schedule-break td { background: #f1f8e9; color: #33691e; font-size: 0.85rem; }
        .schedule-dismiss td { background: #fff8e1; color: #f57f17; font-size: 0.85rem; }
    </style>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Create section</h1>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php
    $schedule = $schedule ?? [];
    $depedSubjectsByGrade = $deped_subjects_by_grade ?? [];
    $adviserTeacherIds = $adviser_teacher_ids ?? [];
    $slots = $schedule['slots'] ?? [];
    $dismissal = $schedule['dismissal_time'] ?? '15:30';
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
    ?>
    <div class="card">
        <div class="card-title">New section</div>
        <form action="<?= base_url('admin/sections/store') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="section_label" style="color: #1b5e20;">Section name</label>
                <select id="section_label" name="section_label" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">Select section</option>
                </select>
                <div class="custom-section-wrap" id="custom-section-wrap">
                    <input type="text" id="custom_section_label" name="custom_section_label" value="<?= esc(old('custom_section_label')) ?>" placeholder="Type new section name (e.g. Kamagong)" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                </div>
                <div class="hint-text">Section list changes based on selected grade level. Names already created for that grade are hidden (use Custom if you need another name).</div>
            </div>
            <div class="form-group">
                <label for="grade_level" style="color: #1b5e20;">Grade level</label>
                <select id="grade_level" name="grade_level" required style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">Select grade</option>
                    <?php $oldGrade = old('grade_level'); ?>
                    <?php for ($g = 1; $g <= 6; $g++): ?>
                        <option value="<?= $g ?>" <?= (string) $oldGrade === (string) $g ? 'selected' : '' ?>>Grade <?= $g ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <input type="hidden" id="name" name="name" value="<?= esc(old('name')) ?>">
            <div class="form-group">
                <label for="teacher_id" style="color: #1b5e20;">Assign teacher (optional)</label>
                <select id="teacher_id" name="teacher_id" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                    <option value="">-- No teacher yet --</option>
                    <?php foreach (($teachers ?? []) as $teacher): ?>
                        <option value="<?= (int) $teacher['id'] ?>" <?= (old('teacher_id') == (string) $teacher['id']) ? 'selected' : '' ?>>
                            <?= esc($teacher['name'] ?? $teacher['username'] ?? ('Teacher #' . $teacher['id'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint-text">When assigned, this teacher is linked as <strong>class adviser</strong> for the section.</div>
                <p id="assign-teacher-warning" class="hint-text" style="display:none; color:#c62828; margin-top:6px;"></p>
                <label class="checkbox-row">
                    <input type="checkbox" name="assign_now" value="1" <?= old('assign_now') === '1' ? 'checked' : '' ?>>
                    Assign immediately (skip invite — teacher sees the section right away)
                </label>
                <div class="hint-text" style="margin-top: 6px;"><strong>Default (box unchecked):</strong> assignment stays <strong>pending</strong> until the teacher accepts — same as sending an invite. Check the box only if you want them active on the section without accepting.</div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label style="color: #1b5e20; display: block; margin-bottom: 6px;">Daily class schedule — eight subjects (50 minutes each)</label>
                <div class="hint-text" style="margin-bottom: 8px;">Times are fixed. Subjects are pre-listed based on selected grade level.</div>
                <p id="adviser-slots-need-teacher" class="hint-text" style="display: none; color: #c62828;">Choose a teacher above first, then mark adviser slots.</p>
                <p id="adviser-grade-rule-hint" class="hint-text" style="display: none; color: #c62828;"></p>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th style="width: 36%;">Class time</th>
                            <th>Subject</th>
                            <th style="width: 22%; text-align: center;">Adviser teaches<br><span id="adviser-max-label" style="font-weight:400;font-size:0.8rem;"></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $si = 0; ?>
                        <?php foreach ($slots as $slot): ?>
                            <?php if ($si === 2): ?>
                                <tr class="schedule-break">
                                    <td colspan="3"><strong>Recess</strong> — <?= $fmtRange('09:10', '09:30') ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($si === 5): ?>
                                <tr class="schedule-break">
                                    <td colspan="3"><strong>Lunch break</strong> — <?= $fmtRange('12:00', '13:00') ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php
                            $sn = (int) ($slot['slot'] ?? ($si + 1));
                            $oldVal = old('schedule_subj_' . $sn);
                            ?>
                            <tr>
                                <td><?= esc($fmtRange((string) ($slot['start'] ?? ''), (string) ($slot['end'] ?? ''))) ?> <span style="color:#888;">(50 min)</span></td>
                                <td>
                                    <select class="schedule-subject-select" data-slot="<?= $sn ?>" name="schedule_subj_<?= $sn ?>" style="width: 100%; max-width: 320px; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
                                        <option value="">Select subject</option>
                                        <?php
                                        $currentVal = trim((string) ($oldVal !== null ? $oldVal : ($slot['subject'] ?? '')));
                                        if ($currentVal !== ''):
                                        ?>
                                            <option value="<?= esc($currentVal) ?>" selected><?= esc($currentVal) ?></option>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td style="text-align: center;">
                                    <label class="checkbox-row" style="justify-content: center; margin: 0;">
                                        <input type="checkbox" class="adviser-teach-cb" name="adviser_teaches[]" value="<?= $sn ?>" <?= in_array($sn, $adviserCheckedSlots, true) ? 'checked' : '' ?>>
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

            <button type="submit" class="login-button" style="display: inline-flex; width: auto; padding: 10px 24px;">Save</button>
            <a href="<?= base_url('admin/sections') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    <script>
    (function() {
        var gradeEl = document.getElementById('grade_level');
        var teacherEl = document.getElementById('teacher_id');
        var warningEl = document.getElementById('assign-teacher-warning');
        var adviserTeacherIds = <?= json_encode(array_values(array_map('intval', $adviserTeacherIds))) ?>;
        var adviserTeacherSet = {};
        adviserTeacherIds.forEach(function(id) { adviserTeacherSet[String(id)] = true; });
        var sectionEl = document.getElementById('section_label');
        var customSectionWrap = document.getElementById('custom-section-wrap');
        var customSectionEl = document.getElementById('custom_section_label');
        var hiddenNameEl = document.getElementById('name');
        var previewEl = null;
        var oldSectionLabel = <?= json_encode((string) old('section_label')) ?>;
        var oldCustomSectionLabel = <?= json_encode((string) old('custom_section_label')) ?>;
        var existingNamesByGrade = <?= json_encode($existing_section_names_by_grade ?? []) ?>;
        var depedSubjectsByGrade = <?= json_encode($depedSubjectsByGrade) ?>;
        var gradeSections = {
            '1': ['Chico', 'Narra', 'Molave'],
            '2': ['Acacia', 'Mahogany', 'Yakal'],
            '3': ['Sampaguita', 'Gumamela', 'Rosal'],
            '4': ['Rizal', 'Bonifacio', 'Mabini'],
            '5': ['Aguinaldo', 'Del Pilar', 'Luna'],
            '6': ['Einstein', 'Newton', 'Galileo']
        };
        if (!gradeEl || !sectionEl || !hiddenNameEl || !customSectionWrap || !customSectionEl) return;

        function fillSectionOptions() {
            var grade = (gradeEl.value || '').trim();
            var items = gradeSections[grade] || [];
            var selectedBefore = (sectionEl.value || '').trim();
            sectionEl.innerHTML = '';

            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select section';
            sectionEl.appendChild(placeholder);

            items.forEach(function(treeName) {
                var fullName = 'Grade ' + grade + ' - ' + treeName;
                var taken = (existingNamesByGrade[grade] || []).some(function(dbName) {
                    return dbName && String(dbName).trim().toLowerCase() === fullName.toLowerCase();
                });
                if (taken) {
                    return;
                }
                var opt = document.createElement('option');
                opt.value = treeName;
                opt.textContent = treeName;
                if (treeName === selectedBefore || treeName === oldSectionLabel) {
                    opt.selected = true;
                }
                sectionEl.appendChild(opt);
            });

            var customOpt = document.createElement('option');
            customOpt.value = '__custom__';
            customOpt.textContent = 'Custom section...';
            if (selectedBefore === '__custom__' || oldSectionLabel === '__custom__') {
                customOpt.selected = true;
            }
            sectionEl.appendChild(customOpt);
        }

        function syncCustomVisibility() {
            var isCustom = sectionEl.value === '__custom__';
            customSectionWrap.style.display = isCustom ? 'block' : 'none';
            if (isCustom) {
                customSectionEl.focus();
            }
        }

        function buildSectionName() {
            var grade = (gradeEl.value || '').trim();
            var section = (sectionEl.value || '').trim();
            if (section === '__custom__') {
                section = (customSectionEl.value || '').trim();
            }
            var result = '';
            if (grade !== '' && section !== '') {
                result = 'Grade ' + grade + ' - ' + section;
            }
            hiddenNameEl.value = result;
        }

        gradeEl.addEventListener('change', function() {
            fillSectionOptions();
            syncCustomVisibility();
            buildSectionName();
        });
        sectionEl.addEventListener('change', function() {
            syncCustomVisibility();
            buildSectionName();
        });
        customSectionEl.addEventListener('input', buildSectionName);
        if ((oldCustomSectionLabel || '').trim() !== '') {
            customSectionEl.value = oldCustomSectionLabel;
            sectionEl.value = '__custom__';
        }
        fillSectionOptions();
        syncCustomVisibility();
        buildSectionName();

        function rebuildSubjectOptions() {
            var grade = (gradeEl.value || '').trim();
            var subjectList = depedSubjectsByGrade[grade] || [];
            var selects = document.querySelectorAll('.schedule-subject-select');
            if (!selects.length) return;
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

        function normalizeGrade(raw) {
            var t = String(raw || '').trim();
            var m = t.match(/([1-6])/);
            return m ? m[1] : '';
        }

        function syncAssignTeacherWarning() {
            if (!gradeEl || !teacherEl) return;
            var grade = normalizeGrade(gradeEl.value);
            var selectedTeacher = (teacherEl.value || '').trim();
            var blocked = selectedTeacher !== '' && !!adviserTeacherSet[selectedTeacher];

            Array.prototype.forEach.call(teacherEl.options || [], function(opt) {
                if (!opt || !opt.value) return;
                var disallowed = !!adviserTeacherSet[String(opt.value)];
                opt.disabled = disallowed;
            });

            if (blocked) {
                teacherEl.value = '';
                if (warningEl) {
                    warningEl.style.display = 'block';
                    warningEl.textContent = 'This teacher is already an adviser in another section and cannot be selected again as adviser.';
                }
                return;
            }

            if (warningEl) {
                warningEl.style.display = 'none';
                warningEl.textContent = '';
            }
        }

        gradeEl.addEventListener('change', syncAssignTeacherWarning);
        teacherEl.addEventListener('change', syncAssignTeacherWarning);
        syncAssignTeacherWarning();
    })();

    (function() {
        var teacherEl = document.getElementById('teacher_id');
        var gradeEl = document.getElementById('grade_level');
        var boxes = document.querySelectorAll('.adviser-teach-cb');
        var hintNeedTeacher = document.getElementById('adviser-slots-need-teacher');
        var gradeRuleHint = document.getElementById('adviser-grade-rule-hint');
        var maxLabelEl = document.getElementById('adviser-max-label');
        if (!teacherEl || !boxes.length) return;

        function isGradeAdviserOnly() {
            var g = (gradeEl && gradeEl.value ? String(gradeEl.value) : '').trim();
            var n = parseInt(g, 10);
            return !isNaN(n) && n >= 1 && n <= 3;
        }

        function hasTeacher() {
            return (teacherEl.value || '').trim() !== '';
        }

        function syncTeacherGate() {
            var ok = hasTeacher();
            var adviserOnly = isGradeAdviserOnly();
            boxes.forEach(function(cb) {
                cb.disabled = !ok || adviserOnly;
            });
            if (hintNeedTeacher) {
                hintNeedTeacher.style.display = ok ? 'none' : 'block';
            }
            if (gradeRuleHint) {
                if (ok && adviserOnly) {
                    gradeRuleHint.style.display = 'block';
                    gradeRuleHint.innerHTML = 'Grade 1 to 3 rule: class adviser handles <strong>all subjects</strong> (subject teacher not allowed).';
                } else if (ok) {
                    gradeRuleHint.style.display = 'block';
                    gradeRuleHint.innerHTML = 'Grade 4 to 6: mark up to <strong>two</strong> adviser slots only.';
                } else {
                    gradeRuleHint.style.display = 'none';
                }
            }
            if (maxLabelEl) {
                maxLabelEl.textContent = adviserOnly ? '' : '(max 2)';
            }
            if (!ok) {
                boxes.forEach(function(cb) {
                    cb.checked = false;
                });
                return;
            }
            if (adviserOnly) {
                boxes.forEach(function(cb) {
                    cb.checked = true;
                });
            } else {
                // Grade 4-6: start with no adviser slot selected by default.
                boxes.forEach(function(cb) {
                    cb.checked = false;
                });
            }
            refreshMaxTwoLock();
        }

        function refreshMaxTwoLock() {
            if (!hasTeacher() || isGradeAdviserOnly()) {
                return;
            }
            var checked = Array.prototype.filter.call(boxes, function(c) {
                return c.checked;
            });
            var reached = checked.length >= 2;
            boxes.forEach(function(cb) {
                if (!cb.checked) {
                    cb.disabled = reached ? true : false;
                }
            });
        }

        function onBoxChange(changed) {
            if (!hasTeacher()) return;
            var checked = Array.prototype.filter.call(boxes, function(c) {
                return c.checked;
            });
            if (checked.length > 2) {
                changed.checked = false;
                alert('Maximum 2 subjects for the adviser.');
            }
            refreshMaxTwoLock();
        }

        boxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                onBoxChange(cb);
            });
        });
        teacherEl.addEventListener('change', syncTeacherGate);
        if (gradeEl) {
            gradeEl.addEventListener('change', syncTeacherGate);
        }
        syncTeacherGate();
    })();
    </script>

    </main>
</div>
</body>
</html>
