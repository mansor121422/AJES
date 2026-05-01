<?php

namespace App\Controllers;

use App\Models\SectionModel;
use App\Models\UserModel;
use App\Models\TeacherSectionModel;
use CodeIgniter\HTTP\RedirectResponse;

class Sections extends BaseController
{
    protected SectionModel $sections;
    protected UserModel $users;
    protected TeacherSectionModel $teacherSections;

    public function __construct()
    {
        $this->sections        = new SectionModel();
        $this->users           = new UserModel();
        $this->teacherSections = new TeacherSectionModel();
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $list = $this->sections->orderBy('grade_level')->orderBy('name')->findAll();
        foreach ($list as &$secRow) {
            $secRow['schedule_time_summary'] = $this->compactScheduleTimesSummary($secRow['class_schedule'] ?? null);
        }
        unset($secRow);

        $data = [
            'sections' => $list,
            'role'     => session()->get('role') ?? 'ADMIN',
            'name'     => session()->get('name') ?? 'User',
        ];
        return view('Admin/Sections/index', $data);
    }

    public function create(): string
    {
        $teachers = $this->users
            ->where('role', 'TEACHER')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        $existingNamesByGrade = [];
        foreach ($this->sections->orderBy('grade_level')->orderBy('name')->findAll() as $row) {
            $g = trim((string) ($row['grade_level'] ?? ''));
            $n = trim((string) ($row['name'] ?? ''));
            if ($g === '' || $n === '') {
                continue;
            }
            if (! isset($existingNamesByGrade[$g])) {
                $existingNamesByGrade[$g] = [];
            }
            $existingNamesByGrade[$g][] = $n;
        }

        $data = [
            'role'                         => session()->get('role') ?? 'ADMIN',
            'name'                         => session()->get('name') ?? 'User',
            'teachers'                     => $teachers,
            'schedule'                     => $this->emptyScheduleStructure(),
            'existing_section_names_by_grade' => $existingNamesByGrade,
        ];
        return view('Admin/Sections/create', $data);
    }

    public function store(): RedirectResponse
    {
        $name       = trim((string) $this->request->getPost('name'));
        $gradeLevel = trim((string) $this->request->getPost('grade_level'));
        $teacherId  = (int) $this->request->getPost('teacher_id');
        $assignNow  = (int) $this->request->getPost('assign_now') === 1;

        if ($name === '' || $gradeLevel === '') {
            return redirect()->back()->withInput()->with('error', 'Section name and grade level are required.');
        }

        [$adviserSlots, $adviserErr] = $this->adviserTeachesSlotsFromRequest($teacherId);
        if ($adviserErr !== null) {
            return redirect()->back()->withInput()->with('error', $adviserErr);
        }

        $classSchedule = $this->buildClassScheduleFromRequest($adviserSlots);
        $schedErr = $this->validateAdviserSubjectsNonEmpty($classSchedule);
        if ($schedErr !== null) {
            return redirect()->back()->withInput()->with('error', $schedErr);
        }

        if ($this->sectionNameGradeExists($name, $gradeLevel)) {
            return redirect()->back()->withInput()->with('error', 'A section with this name and grade level already exists. You cannot create the same section twice.');
        }

        $this->sections->insert([
            'name'           => $name,
            'grade_level'    => $gradeLevel,
            'class_schedule' => $classSchedule,
        ]);
        $sectionId = (int) $this->sections->getInsertID();

        if ($teacherId > 0 && $sectionId > 0) {
            $teacher = $this->users
                ->where('id', $teacherId)
                ->where('role', 'TEACHER')
                ->where('is_active', 1)
                ->first();

            if (! $teacher) {
                return redirect()->back()->withInput()->with('error', 'Selected teacher was not found.');
            }

            $this->teacherSections->insert([
                'section_id' => $sectionId,
                'teacher_id' => $teacherId,
                'assignment_role' => 'ADVISER',
                'subject_name' => null,
                'status'     => $assignNow ? 'accepted' : 'pending',
            ]);
        }

        if ($teacherId > 0 && $assignNow) {
            return redirect()->to(base_url('admin/sections'))->with('success', 'Section created and assigned to teacher.');
        }
        if ($teacherId > 0) {
            return redirect()->to(base_url('admin/sections'))->with('success', 'Section created and invite sent to teacher.');
        }
        return redirect()->to(base_url('admin/sections'))->with('success', 'Section created.');
    }

    public function edit(int $id): string|RedirectResponse
    {
        $section = $this->sections->find($id);
        if (! $section) {
            return redirect()->to(base_url('admin/sections'))->with('error', 'Section not found.');
        }
        $data = [
            'section'            => $section,
            'schedule'           => $this->parseClassSchedule($section['class_schedule'] ?? null),
            'section_adviser_id' => $this->resolveSectionAdviserTeacherId($id),
            'role'               => session()->get('role') ?? 'ADMIN',
            'name'               => session()->get('name') ?? 'User',
        ];
        return view('Admin/Sections/edit', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $section = $this->sections->find($id);
        if (! $section) {
            return redirect()->to(base_url('admin/sections'))->with('error', 'Section not found.');
        }
        $name       = trim((string) $this->request->getPost('name'));
        $gradeLevel = trim((string) $this->request->getPost('grade_level'));
        if ($name === '' || $gradeLevel === '') {
            return redirect()->back()->withInput()->with('error', 'Section name and grade level are required.');
        }

        $adviserId = $this->resolveSectionAdviserTeacherId($id);
        [$adviserSlots, $adviserErr] = $this->adviserTeachesSlotsFromRequest($adviserId);
        if ($adviserErr !== null) {
            return redirect()->back()->withInput()->with('error', $adviserErr);
        }

        $classSchedule = $this->buildClassScheduleFromRequest($adviserSlots);
        $schedErr = $this->validateAdviserSubjectsNonEmpty($classSchedule);
        if ($schedErr !== null) {
            return redirect()->back()->withInput()->with('error', $schedErr);
        }

        if ($this->sectionNameGradeExists($name, $gradeLevel, $id)) {
            return redirect()->back()->withInput()->with('error', 'Another section already uses this name and grade level.');
        }

        $this->sections->update($id, [
            'name'           => $name,
            'grade_level'    => $gradeLevel,
            'class_schedule' => $classSchedule,
        ]);
        return redirect()->to(base_url('admin/sections'))->with('success', 'Section updated.');
    }

    public function delete(int $id): RedirectResponse
    {
        $section = $this->sections->find($id);
        if (! $section) {
            return redirect()->to(base_url('admin/sections'))->with('error', 'Section not found.');
        }
        $this->sections->delete($id);
        return redirect()->to(base_url('admin/sections'))->with('success', 'Section deleted.');
    }

    /** Invite a subject teacher to a section (admin). Adviser is not invited from here. */
    public function invite(): string|RedirectResponse
    {
        $sectionId   = (int) $this->request->getGetPost('section_id');
        $teacherId   = (int) $this->request->getGetPost('teacher_id');
        $subjectName = trim((string) $this->request->getGetPost('subject_name'));
        $assignmentRole = 'SUBJECT_TEACHER';

        if ($sectionId <= 0 || $teacherId <= 0) {
            return redirect()->back()->with('error', 'Section and teacher are required.');
        }

        $section = $this->sections->find($sectionId);
        if (! $section) {
            return redirect()->back()->with('error', 'Section not found.');
        }

        $assignable = $this->subjectsFromScheduleNonAdviserSlots($section['class_schedule'] ?? null);
        if ($subjectName === '' || ! $this->subjectIsAssignableForSection($subjectName, $assignable)) {
            return redirect()->back()->with('error', 'Choose a subject from the section schedule (slots not handled by the adviser).');
        }

        $adviserIds = $this->adviserTeacherIdsForSection($sectionId);
        if (in_array($teacherId, $adviserIds, true)) {
            return redirect()->back()->with('error', 'The class adviser cannot be invited again as a subject teacher from this form.');
        }

        $takenSubjects = $this->assignedSubjectTeacherKeys($sectionId);
        if ($takenSubjects[$this->scheduleSubjectKey($subjectName)] ?? false) {
            return redirect()->back()->with('error', 'That subject already has a teacher assigned or invited.');
        }

        $schedArr = json_decode($section['class_schedule'] ?? '', true);
        $targetSlot = $this->findNonAdviserSlotForSubject(is_array($schedArr) ? $schedArr : [], $subjectName);
        if ($targetSlot === null || ($targetSlot['start'] ?? '') === '' || ($targetSlot['end'] ?? '') === '') {
            return redirect()->back()->with('error', 'Could not resolve class time for this subject in the schedule.');
        }
        $overlapMsg = $this->teacherInviteOverlapsExisting($teacherId, (string) $targetSlot['start'], (string) $targetSlot['end']);
        if ($overlapMsg !== null) {
            return redirect()->back()->with('error', $overlapMsg);
        }

        $existsQuery = $this->teacherSections->where('section_id', $sectionId)
            ->where('teacher_id', $teacherId)
            ->where('assignment_role', $assignmentRole)
            ->where('subject_name', $subjectName);
        $exists = $existsQuery->first();
        if ($exists) {
            return redirect()->back()->with('error', 'This assignment already exists for the selected teacher.');
        }

        $this->teacherSections->insert([
            'section_id'      => $sectionId,
            'teacher_id'      => $teacherId,
            'assignment_role' => $assignmentRole,
            'subject_name'    => $subjectName,
            'status'          => 'pending',
        ]);
        return redirect()->back()->with('success', 'Invitation sent. Teacher must accept to be assigned.');
    }

    /** List section with assigned/pending teachers for admin. */
    public function sectionTeachers(int $id): string|RedirectResponse
    {
        $section = $this->sections->find($id);
        if (! $section) {
            return redirect()->to(base_url('admin/sections'))->with('error', 'Section not found.');
        }

        $db = \Config\Database::connect();
        $assignments = $db->table('teacher_sections')
            ->select('teacher_sections.*, users.name as teacher_name, users.username')
            ->join('users', 'users.id = teacher_sections.teacher_id')
            ->where('teacher_sections.section_id', $id)
            ->get()
            ->getResultArray();

        $teachers = $this->users->where('role', 'TEACHER')->findAll();
        $byId = [];
        foreach ($teachers as $t) {
            $byId[(int) $t['id']] = true;
        }
        foreach ($assignments as $a) {
            $tid = (int) ($a['teacher_id'] ?? 0);
            if ($tid > 0 && ! isset($byId[$tid])) {
                $extra = $this->users->find($tid);
                if ($extra && ($extra['role'] ?? '') === 'TEACHER') {
                    $teachers[] = $extra;
                    $byId[$tid] = true;
                }
            }
        }
        usort($teachers, static fn ($x, $y) => strcmp((string) ($x['name'] ?? ''), (string) ($y['name'] ?? '')));

        $adviserIds = $this->adviserTeacherIdsForSection($id);
        $teachersForInvite = array_values(array_filter(
            $teachers,
            static fn (array $t) => ! in_array((int) ($t['id'] ?? 0), $adviserIds, true)
        ));

        $assignableSubjects = $this->subjectsFromScheduleNonAdviserSlots($section['class_schedule'] ?? null);
        $assignedKeys       = $this->assignedSubjectTeacherKeys($id);
        $remainingSubjects  = [];
        foreach ($assignableSubjects as $display) {
            if (! ($assignedKeys[$this->scheduleSubjectKey($display)] ?? false)) {
                $remainingSubjects[] = $display;
            }
        }

        $adviserSubjectsLabel = $this->adviserSubjectsLabelFromSchedule($section['class_schedule'] ?? null);

        $schedDecoded = json_decode($section['class_schedule'] ?? '', true);
        $schedDecoded = is_array($schedDecoded) ? $schedDecoded : [];
        $sectionScheduleRows   = $this->buildSectionScheduleDisplayRows($schedDecoded);
        $inviteSubjectTimeMeta = [];
        foreach ($remainingSubjects as $sub) {
            $sl           = $this->findNonAdviserSlotForSubject($schedDecoded, $sub);
            $inviteSubjectTimeMeta[$sub] = [
                'start' => $sl['start'] ?? '',
                'end'   => $sl['end'] ?? '',
                'label' => $sl !== null ? $this->formatTimeRangeLabel((string) $sl['start'], (string) $sl['end']) : '',
            ];
        }

        $teacherBusyById = [];
        foreach ($teachersForInvite as $t) {
            $tid = (int) ($t['id'] ?? 0);
            if ($tid > 0) {
                $teacherBusyById[$tid] = $this->collectBusyRangesForTeacher($tid);
            }
        }

        $data = [
            'section'                 => $section,
            'assignments'             => $assignments,
            'teachers'                => $teachers,
            'teachers_for_invite'     => $teachersForInvite,
            'assignable_subjects'     => $assignableSubjects,
            'remaining_subjects'      => $remainingSubjects,
            'adviser_subjects_label'  => $adviserSubjectsLabel,
            'section_schedule_rows'   => $sectionScheduleRows,
            'invite_subject_time_meta' => $inviteSubjectTimeMeta,
            'teacher_busy_by_id'      => $teacherBusyById,
            'role'                    => session()->get('role') ?? 'ADMIN',
            'name'                    => session()->get('name') ?? 'User',
        ];
        return view('Admin/Sections/section_teachers', $data);
    }

    /** Update an existing teacher-section assignment (admin). */
    public function updateTeacherAssignment(int $assignmentId): RedirectResponse
    {
        $sectionId = (int) $this->request->getPost('section_id');
        $teacherId = (int) $this->request->getPost('teacher_id');
        $assignmentRole = strtoupper(trim((string) $this->request->getPost('assignment_role')));
        $subjectName = trim((string) $this->request->getPost('subject_name'));

        if ($sectionId <= 0 || $teacherId <= 0 || $assignmentId <= 0) {
            return redirect()->back()->with('error', 'Invalid request.');
        }

        $row = $this->teacherSections->find($assignmentId);
        if (! $row || (int) ($row['section_id'] ?? 0) !== $sectionId) {
            return redirect()->back()->with('error', 'Assignment not found.');
        }

        $teacher = $this->users
            ->where('id', $teacherId)
            ->where('role', 'TEACHER')
            ->where('is_active', 1)
            ->first();
        if (! $teacher) {
            return redirect()->back()->with('error', 'Selected teacher was not found.');
        }

        $section = $this->sections->find($sectionId);
        if (! $section) {
            return redirect()->back()->with('error', 'Section not found.');
        }
        $assignable = $this->subjectsFromScheduleNonAdviserSlots($section['class_schedule'] ?? null);

        if (! in_array($assignmentRole, ['ADVISER', 'SUBJECT_TEACHER'], true)) {
            $assignmentRole = 'SUBJECT_TEACHER';
        }
        if ($assignmentRole === 'SUBJECT_TEACHER') {
            if ($subjectName === '' || ! $this->subjectIsAssignableForSection($subjectName, $assignable)) {
                return redirect()->back()->with('error', 'Subject must match a non‑adviser slot in this section schedule.');
            }
            $others = $this->teacherSections
                ->where('section_id', $sectionId)
                ->where('assignment_role', 'SUBJECT_TEACHER')
                ->where('id !=', $assignmentId)
                ->groupStart()
                ->where('status', 'accepted')
                ->orWhere('status', 'pending')
                ->groupEnd()
                ->findAll();
            foreach ($others as $o) {
                $sn = trim((string) ($o['subject_name'] ?? ''));
                if ($sn !== '' && $this->scheduleSubjectKey($sn) === $this->scheduleSubjectKey($subjectName)) {
                    return redirect()->back()->with('error', 'Another teacher already covers this subject in this section.');
                }
            }
        }

        if ($assignmentRole === 'ADVISER') {
            $otherAdviser = $this->teacherSections
                ->where('section_id', $sectionId)
                ->where('assignment_role', 'ADVISER')
                ->where('id !=', $assignmentId)
                ->first();
            if ($otherAdviser) {
                return redirect()->back()->with('error', 'This section already has another adviser.');
            }
        }

        $dup = $this->teacherSections
            ->where('section_id', $sectionId)
            ->where('teacher_id', $teacherId)
            ->where('assignment_role', $assignmentRole)
            ->where('id !=', $assignmentId);
        if ($assignmentRole === 'SUBJECT_TEACHER') {
            $dup->where('subject_name', $subjectName);
        }
        if ($dup->first()) {
            return redirect()->back()->with('error', 'That assignment already exists.');
        }

        $this->teacherSections->update($assignmentId, [
            'teacher_id'      => $teacherId,
            'assignment_role' => $assignmentRole,
            'subject_name'    => $assignmentRole === 'ADVISER' ? null : $subjectName,
        ]);

        return redirect()->back()->with('success', 'Teacher assignment updated.');
    }

    /** Remove a teacher-section assignment (admin). */
    public function deleteTeacherAssignment(int $assignmentId): RedirectResponse
    {
        $row = $this->teacherSections->find($assignmentId);
        if (! $row) {
            return redirect()->back()->with('error', 'Assignment not found.');
        }

        $sectionId = (int) ($row['section_id'] ?? 0);
        $this->teacherSections->delete($assignmentId);

        return redirect()->to(base_url('admin/sections/' . $sectionId . '/teachers'))->with('success', 'Teacher assignment removed.');
    }

    /** Default daily template: five 1-hour subjects, recess, lunch, 3:00 PM dismissal. */
    private function emptyScheduleStructure(): array
    {
        return [
            'slots' => [
                ['slot' => 1, 'subject' => '', 'start' => '07:45', 'end' => '08:45', 'adviser_teaches' => false],
                ['slot' => 2, 'subject' => '', 'start' => '08:45', 'end' => '09:45', 'adviser_teaches' => false],
                ['slot' => 3, 'subject' => '', 'start' => '10:00', 'end' => '11:00', 'adviser_teaches' => false],
                ['slot' => 4, 'subject' => '', 'start' => '13:00', 'end' => '14:00', 'adviser_teaches' => false],
                ['slot' => 5, 'subject' => '', 'start' => '14:00', 'end' => '15:00', 'adviser_teaches' => false],
            ],
            'breaks' => [
                ['label' => 'Recess', 'start' => '09:45', 'end' => '10:00'],
                ['label' => 'Lunch break', 'start' => '11:00', 'end' => '13:00'],
            ],
            'dismissal_time' => '15:00',
        ];
    }

    /**
     * @param list<int> $adviserSlotNumbers slot numbers 1–5 the assigned adviser teaches (max 2)
     */
    private function buildClassScheduleFromRequest(array $adviserSlotNumbers): string
    {
        $base = $this->emptyScheduleStructure();
        $adviserSet = array_fill_keys($adviserSlotNumbers, true);
        for ($i = 1; $i <= 5; $i++) {
            $subj = trim((string) $this->request->getPost('schedule_subj_' . $i));
            $base['slots'][$i - 1]['subject'] = $subj;
            $base['slots'][$i - 1]['adviser_teaches'] = isset($adviserSet[$i]);
        }

        return json_encode($base, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{0: list<int>, 1: ?string} [unique slot numbers, error message]
     */
    private function adviserTeachesSlotsFromRequest(int $assignedAdviserTeacherId): array
    {
        if ($assignedAdviserTeacherId <= 0) {
            return [[], null];
        }

        $raw = $this->request->getPost('adviser_teaches');
        if (! is_array($raw)) {
            $raw = [];
        }

        $slots = [];
        foreach ($raw as $v) {
            $n = (int) $v;
            if ($n >= 1 && $n <= 5) {
                $slots[$n] = true;
            }
        }
        $list = array_keys($slots);
        sort($list);

        if (count($list) > 2) {
            return [[], 'You can assign at most two subjects to the adviser.'];
        }

        return [$list, null];
    }

    private function validateAdviserSubjectsNonEmpty(string $classScheduleJson): ?string
    {
        $data = json_decode($classScheduleJson, true);
        if (! is_array($data) || empty($data['slots'])) {
            return null;
        }
        foreach ($data['slots'] as $slot) {
            if (empty($slot['adviser_teaches'])) {
                continue;
            }
            if (trim((string) ($slot['subject'] ?? '')) === '') {
                return 'Enter a subject name for each time slot you mark for the adviser.';
            }
        }

        return null;
    }

    private function resolveSectionAdviserTeacherId(int $sectionId): int
    {
        $row = $this->teacherSections
            ->where('section_id', $sectionId)
            ->where('assignment_role', 'ADVISER')
            ->groupStart()
            ->where('status', 'accepted')
            ->orWhere('status', 'pending')
            ->groupEnd()
            ->first();

        return (int) ($row['teacher_id'] ?? 0);
    }

    private function scheduleSubjectKey(string $subject): string
    {
        return strtolower(trim($subject));
    }

    /** Subjects in section timetable for slots the adviser does not teach (need a subject teacher). */
    private function subjectsFromScheduleNonAdviserSlots(?string $classScheduleRaw): array
    {
        if ($classScheduleRaw === null || $classScheduleRaw === '') {
            return [];
        }
        $sched = json_decode($classScheduleRaw, true);
        if (! is_array($sched) || empty($sched['slots']) || ! is_array($sched['slots'])) {
            return [];
        }
        $byKey = [];
        foreach ($sched['slots'] as $slot) {
            if (! empty($slot['adviser_teaches'])) {
                continue;
            }
            $s = trim((string) ($slot['subject'] ?? ''));
            if ($s === '') {
                continue;
            }
            $k = $this->scheduleSubjectKey($s);
            if (! isset($byKey[$k])) {
                $byKey[$k] = $s;
            }
        }

        return array_values($byKey);
    }

    private function adviserSubjectsLabelFromSchedule(?string $classScheduleRaw): string
    {
        if ($classScheduleRaw === null || $classScheduleRaw === '') {
            return '—';
        }
        $sched = json_decode($classScheduleRaw, true);
        if (! is_array($sched) || empty($sched['slots'])) {
            return '—';
        }
        $parts = [];
        foreach ($sched['slots'] as $slot) {
            if (empty($slot['adviser_teaches'])) {
                continue;
            }
            $s = trim((string) ($slot['subject'] ?? ''));
            if ($s !== '') {
                $parts[] = $s;
            }
        }

        return $parts !== [] ? implode(', ', $parts) : '—';
    }

    /** @return array<string, true> normalized subject keys */
    private function assignedSubjectTeacherKeys(int $sectionId): array
    {
        $rows = $this->teacherSections
            ->where('section_id', $sectionId)
            ->where('assignment_role', 'SUBJECT_TEACHER')
            ->groupStart()
            ->where('status', 'accepted')
            ->orWhere('status', 'pending')
            ->groupEnd()
            ->findAll();

        $keys = [];
        foreach ($rows as $r) {
            $sn = trim((string) ($r['subject_name'] ?? ''));
            if ($sn !== '') {
                $keys[$this->scheduleSubjectKey($sn)] = true;
            }
        }

        return $keys;
    }

    /** @return list<int> */
    private function adviserTeacherIdsForSection(int $sectionId): array
    {
        $rows = $this->teacherSections
            ->where('section_id', $sectionId)
            ->groupStart()
            ->where('status', 'accepted')
            ->orWhere('status', 'pending')
            ->groupEnd()
            ->findAll();

        $ids = [];
        foreach ($rows as $r) {
            $roleRaw = $r['assignment_role'] ?? null;
            $role = $roleRaw === null ? 'ADVISER' : strtoupper((string) $roleRaw);
            if ($role === 'ADVISER') {
                $ids[] = (int) ($r['teacher_id'] ?? 0);
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function subjectIsAssignableForSection(string $subjectName, array $assignableList): bool
    {
        $want = $this->scheduleSubjectKey($subjectName);
        foreach ($assignableList as $a) {
            if ($this->scheduleSubjectKey((string) $a) === $want) {
                return true;
            }
        }

        return false;
    }

    private function parseClassSchedule(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return $this->emptyScheduleStructure();
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || empty($decoded['slots']) || ! is_array($decoded['slots'])) {
            return $this->emptyScheduleStructure();
        }
        $out = $this->emptyScheduleStructure();
        foreach ($out['slots'] as $idx => $slot) {
            $incoming = $decoded['slots'][$idx] ?? [];
            if (is_array($incoming)) {
                $out['slots'][$idx]['subject'] = trim((string) ($incoming['subject'] ?? ''));
                $out['slots'][$idx]['adviser_teaches'] = ! empty($incoming['adviser_teaches']);
            }
        }
        if (! empty($decoded['breaks']) && is_array($decoded['breaks'])) {
            $out['breaks'] = $decoded['breaks'];
        }
        if (! empty($decoded['dismissal_time'])) {
            $out['dismissal_time'] = (string) $decoded['dismissal_time'];
        }

        return $out;
    }

    /** @param array<string, mixed> $schedDecoded */
    private function findNonAdviserSlotForSubject(array $schedDecoded, string $subjectName): ?array
    {
        if ($subjectName === '' || empty($schedDecoded['slots']) || ! is_array($schedDecoded['slots'])) {
            return null;
        }
        $want = $this->scheduleSubjectKey($subjectName);
        foreach ($schedDecoded['slots'] as $slot) {
            if (! is_array($slot) || ! empty($slot['adviser_teaches'])) {
                continue;
            }
            $s = trim((string) ($slot['subject'] ?? ''));
            if ($this->scheduleSubjectKey($s) !== $want) {
                continue;
            }
            $st = trim((string) ($slot['start'] ?? ''));
            $en = trim((string) ($slot['end'] ?? ''));
            if ($st === '' || $en === '') {
                return null;
            }

            return ['start' => $st, 'end' => $en, 'subject' => $s];
        }

        return null;
    }

    private function formatClock(string $hm): string
    {
        $hm = trim($hm);
        if ($hm === '') {
            return '';
        }
        $ts = strtotime('2000-01-01 ' . $hm);
        if ($ts === false) {
            return $hm;
        }

        return date('g:i A', $ts);
    }

    private function formatTimeRangeLabel(string $start, string $end): string
    {
        return $this->formatClock($start) . ' – ' . $this->formatClock($end);
    }

    private function timeToMinutes(string $hm): int
    {
        $parts = explode(':', trim($hm));
        $h     = (int) ($parts[0] ?? 0);
        $m     = (int) ($parts[1] ?? 0);

        return $h * 60 + $m;
    }

    private function timeRangesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        if ($startA === '' || $endA === '' || $startB === '' || $endB === '') {
            return false;
        }
        $a1 = $this->timeToMinutes($startA);
        $a2 = $this->timeToMinutes($endA);
        $b1 = $this->timeToMinutes($startB);
        $b2 = $this->timeToMinutes($endB);

        return $a1 < $b2 && $b1 < $a2;
    }

    /**
     * Occupied class time ranges for a teacher (accepted + pending), all sections.
     *
     * @return list<array{start: string, end: string, label: string}>
     */
    private function collectBusyRangesForTeacher(int $teacherId): array
    {
        if ($teacherId <= 0) {
            return [];
        }
        $rows = $this->teacherSections
            ->where('teacher_id', $teacherId)
            ->groupStart()
                ->where('status', 'accepted')
                ->orWhere('status', 'pending')
            ->groupEnd()
            ->findAll();

        $busy = [];
        foreach ($rows as $row) {
            $sid = (int) ($row['section_id'] ?? 0);
            $section = $this->sections->find($sid);
            if (! $section) {
                continue;
            }
            $sched = json_decode($section['class_schedule'] ?? '', true);
            if (! is_array($sched) || empty($sched['slots'])) {
                continue;
            }
            $secLabel = trim((string) ($section['name'] ?? ('Section #' . $sid)));
            $roleRaw  = $row['assignment_role'] ?? null;
            $role     = $roleRaw === null ? 'ADVISER' : strtoupper((string) $roleRaw);

            if ($role === 'ADVISER') {
                foreach ($sched['slots'] as $slot) {
                    if (! is_array($slot) || empty($slot['adviser_teaches'])) {
                        continue;
                    }
                    $sub = trim((string) ($slot['subject'] ?? ''));
                    if ($sub === '') {
                        continue;
                    }
                    $st = trim((string) ($slot['start'] ?? ''));
                    $en = trim((string) ($slot['end'] ?? ''));
                    if ($st !== '' && $en !== '') {
                        $busy[] = [
                            'start' => $st,
                            'end'   => $en,
                            'label' => $secLabel . ' (Adviser: ' . $sub . ')',
                        ];
                    }
                }
            } else {
                $sub = trim((string) ($row['subject_name'] ?? ''));
                if ($sub === '') {
                    continue;
                }
                $match = $this->findNonAdviserSlotForSubject($sched, $sub);
                if ($match !== null) {
                    $busy[] = [
                        'start' => (string) $match['start'],
                        'end'   => (string) $match['end'],
                        'label' => $secLabel . ' (' . $sub . ')',
                    ];
                }
            }
        }

        return $busy;
    }

    private function teacherInviteOverlapsExisting(int $teacherId, string $newStart, string $newEnd): ?string
    {
        foreach ($this->collectBusyRangesForTeacher($teacherId) as $b) {
            if ($this->timeRangesOverlap($b['start'], $b['end'], $newStart, $newEnd)) {
                return 'Not available: this teacher already has a class that overlaps this time slot '
                    . '(' . $this->formatTimeRangeLabel($newStart, $newEnd) . '). '
                    . 'Conflict: ' . $b['label'] . ' at ' . $this->formatTimeRangeLabel($b['start'], $b['end']) . '.';
            }
        }

        return null;
    }

    /** @param array<string, mixed> $schedDecoded */
    private function buildSectionScheduleDisplayRows(array $schedDecoded): array
    {
        $out = [];
        if (empty($schedDecoded['slots']) || ! is_array($schedDecoded['slots'])) {
            return $out;
        }
        foreach ($schedDecoded['slots'] as $slot) {
            if (! is_array($slot)) {
                continue;
            }
            $sub = trim((string) ($slot['subject'] ?? ''));
            $st  = trim((string) ($slot['start'] ?? ''));
            $en  = trim((string) ($slot['end'] ?? ''));
            $adv = ! empty($slot['adviser_teaches']);
            $out[] = [
                'subject'     => $sub !== '' ? $sub : '—',
                'time_label'  => ($st !== '' && $en !== '') ? $this->formatTimeRangeLabel($st, $en) : '—',
                'taught_note' => $adv ? 'Class adviser' : 'Subject teacher (invite if needed)',
            ];
        }

        return $out;
    }

    private function compactScheduleTimesSummary(?string $classScheduleRaw): string
    {
        $sched = json_decode($classScheduleRaw ?? '', true);
        if (! is_array($sched) || empty($sched['slots'][0]) || ! is_array($sched['slots'][0])) {
            return '—';
        }
        $slots = $sched['slots'];
        $first = $slots[0];
        $last  = $slots[count($slots) - 1];
        $a     = $this->formatClock(trim((string) ($first['start'] ?? '')));
        $b     = $this->formatClock(trim((string) ($last['end'] ?? '')));
        if ($a === '' || $b === '') {
            return '—';
        }

        return $a . ' – ' . $b . ' (school day)';
    }

    /**
     * Non-deleted section with same display name (case-insensitive) and grade already exists.
     *
     * @param int|null $excludeId When updating, exclude this section id (same row allowed).
     */
    private function sectionNameGradeExists(string $name, string $gradeLevel, ?int $excludeId = null): bool
    {
        $name       = trim($name);
        $gradeLevel = trim($gradeLevel);
        $rows       = $this->sections->where('grade_level', $gradeLevel)->findAll();
        foreach ($rows as $r) {
            $rid = (int) ($r['id'] ?? 0);
            if ($excludeId !== null && $rid === $excludeId) {
                continue;
            }
            if (strcasecmp(trim((string) ($r['name'] ?? '')), $name) === 0) {
                return true;
            }
        }

        return false;
    }
}
