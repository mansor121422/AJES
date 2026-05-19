<?php

namespace App\Controllers;

use App\Libraries\SectionEnrollment;
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
        $list              = $this->sections->orderBy('grade_level')->orderBy('name')->findAll();
        $adviserBySection  = $this->adviserDisplayBySectionId();
        foreach ($list as &$secRow) {
            $sectionId = (int) ($secRow['id'] ?? 0);
            $secRow['schedule_time_summary'] = $this->compactScheduleTimesSummary($secRow['class_schedule'] ?? null);
            $secRow['student_count']         = SectionEnrollment::countStudentsInSection($sectionId);
            $secRow['adviser_name']          = $adviserBySection[$sectionId] ?? '—';
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
        $adviserTeacherIds = $this->adviserTeacherIds();

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
            'adviser_teacher_ids'          => $adviserTeacherIds,
            'schedule'                     => $this->emptyScheduleStructure(),
            'deped_subjects_by_grade'      => $this->depedSubjectsByGrade(),
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
        if ($teacherId > 0 && $this->isAdviserOnlyGrade($gradeLevel)) {
            $adviserSlots = $this->allScheduleSlotNumbers();
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
            if ($this->hasExistingAdviserAssignment($teacherId)) {
                return redirect()->back()->withInput()->with('error', 'This teacher is already an adviser in another section and cannot be assigned again as adviser.');
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
        $adviserRow = $this->findSectionAdviserAssignment($id);

        $data = [
            'section'                 => $section,
            'schedule'                => $this->parseClassSchedule($section['class_schedule'] ?? null),
            'section_adviser_id'      => (int) ($adviserRow['teacher_id'] ?? 0),
            'section_adviser_status'  => (string) ($adviserRow['status'] ?? ''),
            'available_teachers'      => $this->availableAdviserTeachersForSection($id),
            'deped_subjects_by_grade' => $this->depedSubjectsByGrade(),
            'role'                    => session()->get('role') ?? 'ADMIN',
            'name'                    => session()->get('name') ?? 'User',
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

        $adviserRow = $this->findSectionAdviserAssignment($id);
        $newTeacherId = (int) $this->request->getPost('teacher_id');
        $assignNow    = (int) $this->request->getPost('assign_now') === 1;
        $teacherErr   = $this->applySectionAdviserChange($id, $newTeacherId, $assignNow, $adviserRow);
        if ($teacherErr !== null) {
            return redirect()->back()->withInput()->with('error', $teacherErr);
        }

        $adviserId = $newTeacherId > 0 ? $newTeacherId : $this->resolveSectionAdviserTeacherId($id);
        [$adviserSlots, $adviserErr] = $this->adviserTeachesSlotsFromRequest($adviserId);
        if ($adviserErr !== null) {
            return redirect()->back()->withInput()->with('error', $adviserErr);
        }
        if ($adviserId > 0 && $this->isAdviserOnlyGrade($gradeLevel)) {
            $adviserSlots = $this->allScheduleSlotNumbers();
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

    public function sectionStudents(int $id): string|RedirectResponse
    {
        $section = $this->sections->find($id);
        if (! $section) {
            return redirect()->to(base_url('admin/sections'))->with('error', 'Section not found.');
        }

        $sectionId           = (int) $section['id'];
        $studentsInSection   = $this->studentsInSection($sectionId);
        $studentCount        = count($studentsInSection);
        $sectionGradeDigit   = $this->normalizeGradeToDigit($section['grade_level'] ?? '');
        $addableStudents     = [];
        $sectionHasCapacity  = ! SectionEnrollment::isFull($sectionId);

        if ($sectionHasCapacity) {
            $candidates = $this->users
                ->whereIn('role', SectionEnrollment::studentRoleSlugs())
                ->where('is_active', 1)
                ->groupStart()
                    ->where('section_id', null)
                    ->orWhere('section_id', 0)
                ->groupEnd()
                ->orderBy('surname', 'ASC')
                ->orderBy('first_name', 'ASC')
                ->orderBy('name', 'ASC')
                ->findAll();

            foreach ($candidates as $row) {
                if ($sectionGradeDigit !== '' && $this->normalizeGradeToDigit($row['grade_level'] ?? '') === $sectionGradeDigit) {
                    $addableStudents[] = $row;
                }
            }
        }

        return view('Admin/Sections/section_students', [
            'section'            => $section,
            'studentsInSection'  => $studentsInSection,
            'addableStudents'    => $addableStudents,
            'student_count'      => $studentCount,
            'max_students'       => SectionEnrollment::MAX_STUDENTS,
            'section_has_capacity' => $sectionHasCapacity,
            'role'               => session()->get('role') ?? 'ADMIN',
            'name'               => session()->get('name') ?? 'User',
        ]);
    }

    public function addStudent(): RedirectResponse
    {
        $sectionId = (int) $this->request->getPost('section_id');
        $studentId = (int) $this->request->getPost('student_id');

        if ($sectionId <= 0 || $studentId <= 0) {
            return redirect()->back()->with('error', 'Section and student are required.');
        }

        $err = $this->enrollStudentInSection($sectionId, $studentId);
        if ($err !== null) {
            return redirect()->back()->with('error', $err);
        }

        return redirect()->back()->with('success', 'Student enrolled in section.');
    }

    public function removeStudent(): RedirectResponse
    {
        $sectionId = (int) $this->request->getPost('section_id');
        $studentId = (int) $this->request->getPost('student_id');

        if ($sectionId <= 0 || $studentId <= 0) {
            return redirect()->back()->with('error', 'Section and student are required.');
        }

        $student = $this->users->find($studentId);
        if (! $student || ! SectionEnrollment::isStudentUser($student)) {
            return redirect()->back()->with('error', 'Student not found.');
        }
        if ((int) ($student['section_id'] ?? 0) !== $sectionId) {
            return redirect()->back()->with('error', 'This student is not enrolled in this section.');
        }

        $this->users->update($studentId, ['section_id' => null]);

        return redirect()->back()->with('success', 'Student removed from section.');
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
        if ($this->isAdviserOnlyGrade((string) ($section['grade_level'] ?? ''))) {
            return redirect()->back()->with('error', 'Grade 1 to Grade 3 sections are adviser-only. Subject teacher invites are not allowed.');
        }

        $assignable = $this->subjectsFromScheduleNonAdviserSlots($section['class_schedule'] ?? null);
        if ($subjectName === '' || ! $this->subjectIsAssignableForSection($subjectName, $assignable)) {
            return redirect()->back()->with('error', 'Choose a subject from the section schedule (slots not handled by the adviser).');
        }

        $adviserIds = $this->adviserTeacherIdsForSection($sectionId);
        if (in_array($teacherId, $adviserIds, true)) {
            return redirect()->back()->with('error', 'The class adviser cannot be invited again as a subject teacher from this form.');
        }
        if ($this->hasAdviserAssignmentInGradesOneToThree($teacherId)) {
            return redirect()->back()->with('error', 'This teacher is an adviser in Grade 1 to 3 and cannot be assigned as subject teacher.');
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
            function (array $t) use ($adviserIds): bool {
                $tid = (int) ($t['id'] ?? 0);
                if (in_array($tid, $adviserIds, true)) {
                    return false;
                }
                if ($this->hasAdviserAssignmentInGradesOneToThree($tid)) {
                    return false;
                }
                return true;
            }
        ));

        $isAdviserOnlyGrade = $this->isAdviserOnlyGrade((string) ($section['grade_level'] ?? ''));
        $assignableSubjects = $isAdviserOnlyGrade ? [] : $this->subjectsFromScheduleNonAdviserSlots($section['class_schedule'] ?? null);
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
            'is_adviser_only_grade'   => $isAdviserOnlyGrade,
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
        $isAdviserOnlyGrade = $this->isAdviserOnlyGrade((string) ($section['grade_level'] ?? ''));
        $assignable = $this->subjectsFromScheduleNonAdviserSlots($section['class_schedule'] ?? null);

        if (! in_array($assignmentRole, ['ADVISER', 'SUBJECT_TEACHER'], true)) {
            $assignmentRole = 'SUBJECT_TEACHER';
        }
        if ($isAdviserOnlyGrade && $assignmentRole === 'SUBJECT_TEACHER') {
            return redirect()->back()->with('error', 'Grade 1 to Grade 3 sections are adviser-only. Subject teacher assignment is not allowed.');
        }
        if ($assignmentRole === 'SUBJECT_TEACHER') {
            if ($this->hasAdviserAssignmentInGradesOneToThree($teacherId, $assignmentId)) {
                return redirect()->back()->with('error', 'This teacher is an adviser in Grade 1 to 3 and cannot be assigned as subject teacher.');
            }
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
            if ($this->hasExistingAdviserAssignment($teacherId, $assignmentId)) {
                return redirect()->back()->with('error', 'This teacher is already an adviser in another section and cannot be assigned again as adviser.');
            }
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

    /** Default daily template: eight 50-minute subjects with recess/lunch breaks. */
    private function emptyScheduleStructure(): array
    {
        return [
            'slots' => [
                ['slot' => 1, 'subject' => '', 'start' => '07:30', 'end' => '08:20', 'adviser_teaches' => false],
                ['slot' => 2, 'subject' => '', 'start' => '08:20', 'end' => '09:10', 'adviser_teaches' => false],
                ['slot' => 3, 'subject' => '', 'start' => '09:30', 'end' => '10:20', 'adviser_teaches' => false],
                ['slot' => 4, 'subject' => '', 'start' => '10:20', 'end' => '11:10', 'adviser_teaches' => false],
                ['slot' => 5, 'subject' => '', 'start' => '11:10', 'end' => '12:00', 'adviser_teaches' => false],
                ['slot' => 6, 'subject' => '', 'start' => '13:00', 'end' => '13:50', 'adviser_teaches' => false],
                ['slot' => 7, 'subject' => '', 'start' => '13:50', 'end' => '14:40', 'adviser_teaches' => false],
                ['slot' => 8, 'subject' => '', 'start' => '14:40', 'end' => '15:30', 'adviser_teaches' => false],
            ],
            'breaks' => [
                ['label' => 'Recess', 'start' => '09:10', 'end' => '09:30'],
                ['label' => 'Lunch break', 'start' => '12:00', 'end' => '13:00'],
            ],
            'dismissal_time' => '15:30',
        ];
    }

    /** @param list<int> $adviserSlotNumbers slot numbers from schedule the assigned adviser teaches */
    private function buildClassScheduleFromRequest(array $adviserSlotNumbers): string
    {
        $base = $this->emptyScheduleStructure();
        $adviserSet = array_fill_keys($adviserSlotNumbers, true);
        foreach ($base['slots'] as $idx => $slot) {
            $slotNo = (int) ($slot['slot'] ?? ($idx + 1));
            $subj = trim((string) $this->request->getPost('schedule_subj_' . $slotNo));
            $base['slots'][$idx]['subject'] = $subj;
            $base['slots'][$idx]['adviser_teaches'] = isset($adviserSet[$slotNo]);
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
        $maxSlot = count($this->emptyScheduleStructure()['slots'] ?? []);
        foreach ($raw as $v) {
            $n = (int) $v;
            if ($n >= 1 && $n <= $maxSlot) {
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

    private function findSectionAdviserAssignment(int $sectionId): ?array
    {
        $row = $this->teacherSections
            ->where('section_id', $sectionId)
            ->where('assignment_role', 'ADVISER')
            ->groupStart()
                ->where('status', 'accepted')
                ->orWhere('status', 'pending')
            ->groupEnd()
            ->first();

        return $row ?: null;
    }

    private function resolveSectionAdviserTeacherId(int $sectionId): int
    {
        $row = $this->findSectionAdviserAssignment($sectionId);

        return (int) ($row['teacher_id'] ?? 0);
    }

    /**
     * Active teachers with no adviser section elsewhere; includes this section’s current adviser.
     *
     * @return list<array<string, mixed>>
     */
    private function availableAdviserTeachersForSection(int $sectionId): array
    {
        $teachers   = $this->users
            ->where('role', 'TEACHER')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();
        $adviserRow = $this->findSectionAdviserAssignment($sectionId);
        $excludeId  = $adviserRow ? (int) ($adviserRow['id'] ?? 0) : 0;

        $available = [];
        foreach ($teachers as $teacher) {
            $tid = (int) ($teacher['id'] ?? 0);
            if ($tid <= 0) {
                continue;
            }
            if (! $this->hasExistingAdviserAssignment($tid, $excludeId > 0 ? $excludeId : null)) {
                $available[] = $teacher;
            }
        }

        return $available;
    }

    private function applySectionAdviserChange(int $sectionId, int $newTeacherId, bool $assignNow, ?array $existingRow): ?string
    {
        $currentTeacherId = (int) ($existingRow['teacher_id'] ?? 0);

        if ($newTeacherId === $currentTeacherId) {
            if ($existingRow !== null && $assignNow && strtolower((string) ($existingRow['status'] ?? '')) === 'pending') {
                $this->teacherSections->update((int) $existingRow['id'], ['status' => 'accepted']);
            }

            return null;
        }

        if ($existingRow !== null) {
            $this->teacherSections->delete((int) $existingRow['id']);
        }

        if ($newTeacherId <= 0) {
            return null;
        }

        $teacher = $this->users
            ->where('id', $newTeacherId)
            ->where('role', 'TEACHER')
            ->where('is_active', 1)
            ->first();
        if (! $teacher) {
            return 'Selected teacher was not found or is not active.';
        }
        if ($this->hasExistingAdviserAssignment($newTeacherId)) {
            return 'This teacher is already an adviser in another section and cannot be assigned here.';
        }

        $this->teacherSections->insert([
            'section_id'       => $sectionId,
            'teacher_id'       => $newTeacherId,
            'assignment_role'  => 'ADVISER',
            'subject_name'     => null,
            'status'           => $assignNow ? 'accepted' : 'pending',
        ]);

        return null;
    }

    /**
     * @return array<int, string> section_id => adviser display label
     */
    private function adviserDisplayBySectionId(): array
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('teacher_sections ts')
            ->select('ts.section_id, ts.status, users.name AS teacher_name')
            ->join('users', 'users.id = ts.teacher_id', 'inner')
            ->where('ts.assignment_role', 'ADVISER')
            ->groupStart()
                ->where('ts.status', 'accepted')
                ->orWhere('ts.status', 'pending')
            ->groupEnd()
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $sectionId = (int) ($row['section_id'] ?? 0);
            if ($sectionId <= 0 || isset($out[$sectionId])) {
                continue;
            }
            $name = trim((string) ($row['teacher_name'] ?? ''));
            if ($name === '') {
                $name = '—';
            }
            if (strtolower((string) ($row['status'] ?? '')) === 'pending') {
                $name .= ' (pending)';
            }
            $out[$sectionId] = $name;
        }

        return $out;
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

    private function isAdviserOnlyGrade(string $gradeLevel): bool
    {
        $g = trim($gradeLevel);
        if ($g === '') {
            return false;
        }
        if (preg_match('/^([1-6])$/', $g, $m)) {
            $n = (int) $m[1];
            return $n >= 1 && $n <= 3;
        }
        if (preg_match('/grade\s*([1-6])/i', $g, $m)) {
            $n = (int) $m[1];
            return $n >= 1 && $n <= 3;
        }
        return false;
    }

    /** @return array<string, list<string>> */
    private function depedSubjectsByGrade(): array
    {
        return [
            '1' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MTB-MLE', 'MAPEH', 'ESP'],
            '2' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MTB-MLE', 'MAPEH', 'ESP'],
            '3' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MTB-MLE', 'MAPEH', 'ESP'],
            '4' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MAPEH', 'ESP', 'EPP / Homeroom'],
            '5' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MAPEH', 'ESP', 'EPP / Homeroom'],
            '6' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MAPEH', 'ESP', 'EPP / Homeroom'],
        ];
    }

    /** @return list<int> */
    private function allScheduleSlotNumbers(): array
    {
        $slots = $this->emptyScheduleStructure()['slots'] ?? [];
        $out = [];
        foreach ($slots as $idx => $slot) {
            $out[] = (int) ($slot['slot'] ?? ($idx + 1));
        }
        return $out;
    }

    /** @return list<int> */
    private function adviserTeacherIds(): array
    {
        $rows = $this->teacherSections
            ->select('teacher_id')
            ->where('assignment_role', 'ADVISER')
            ->groupStart()
                ->where('status', 'accepted')
                ->orWhere('status', 'pending')
            ->groupEnd()
            ->findAll();

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) ($row['teacher_id'] ?? 0);
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function hasExistingAdviserAssignment(int $teacherId, ?int $excludeAssignmentId = null): bool
    {
        if ($teacherId <= 0) {
            return false;
        }

        $builder = $this->teacherSections
            ->where('teacher_id', $teacherId)
            ->where('assignment_role', 'ADVISER')
            ->groupStart()
                ->where('status', 'accepted')
                ->orWhere('status', 'pending')
            ->groupEnd();
        if ($excludeAssignmentId !== null && $excludeAssignmentId > 0) {
            $builder->where('id !=', $excludeAssignmentId);
        }
        return $builder->first() !== null;
    }

    private function hasAdviserAssignmentInGradesOneToThree(int $teacherId, ?int $excludeAssignmentId = null): bool
    {
        if ($teacherId <= 0) {
            return false;
        }

        $db = \Config\Database::connect();
        $builder = $db->table('teacher_sections ts')
            ->select('ts.id, s.grade_level')
            ->join('sections s', 's.id = ts.section_id', 'inner')
            ->where('ts.teacher_id', $teacherId)
            ->where('ts.assignment_role', 'ADVISER')
            ->groupStart()
                ->where('ts.status', 'accepted')
                ->orWhere('ts.status', 'pending')
            ->groupEnd();
        if ($excludeAssignmentId !== null && $excludeAssignmentId > 0) {
            $builder->where('ts.id !=', $excludeAssignmentId);
        }
        $rows = $builder->get()->getResultArray();
        foreach ($rows as $row) {
            $g = trim((string) ($row['grade_level'] ?? ''));
            if (preg_match('/^([1-3])$/', $g)) {
                return true;
            }
            if (preg_match('/grade\s*([1-3])/i', $g)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array<string, mixed>> */
    private function studentsInSection(int $sectionId): array
    {
        return $this->users
            ->where('section_id', $sectionId)
            ->whereIn('role', SectionEnrollment::studentRoleSlugs())
            ->orderBy('surname', 'ASC')
            ->orderBy('first_name', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    private function enrollStudentInSection(int $sectionId, int $studentId): ?string
    {
        $section = $this->sections->find($sectionId);
        if (! $section) {
            return 'Section not found.';
        }
        if (SectionEnrollment::isFull($sectionId)) {
            return SectionEnrollment::capacityMessage($sectionId);
        }

        $student = $this->users->find($studentId);
        if (! $student || ! SectionEnrollment::isStudentUser($student)) {
            return 'Student not found.';
        }

        $sg = $this->normalizeGradeToDigit($section['grade_level'] ?? '');
        $ug = $this->normalizeGradeToDigit($student['grade_level'] ?? '');
        if ($sg === '' || $ug === '' || $sg !== $ug) {
            return 'This student’s grade does not match this section.';
        }

        $currentSectionId = (int) ($student['section_id'] ?? 0);
        if ($currentSectionId > 0 && $currentSectionId !== $sectionId) {
            return 'This student is already enrolled in another section.';
        }
        if ($currentSectionId === $sectionId) {
            return 'This student is already in this section.';
        }

        $this->users->update($studentId, ['section_id' => $sectionId]);

        return null;
    }

    private function normalizeGradeToDigit(?string $grade): string
    {
        $g = trim((string) $grade);
        if ($g === '') {
            return '';
        }
        if (preg_match('/^([1-6])$/', $g, $m)) {
            return $m[1];
        }
        if (preg_match('/grade\s*([1-6])/i', $g, $m)) {
            return $m[1];
        }
        if (preg_match('/^(\d+)$/', $g, $m)) {
            $n = (int) $m[1];
            if ($n >= 1 && $n <= 6) {
                return (string) $n;
            }
        }

        return '';
    }
}
