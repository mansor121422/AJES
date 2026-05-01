<?php

namespace App\Controllers;

use App\Models\TeacherSectionModel;
use App\Models\UserModel;
use App\Models\SectionModel;
use App\Models\RecordModel;
use CodeIgniter\HTTP\RedirectResponse;

class TeacherSections extends BaseController
{
    protected TeacherSectionModel $teacherSections;
    protected UserModel $users;
    protected SectionModel $sections;
    protected RecordModel $records;

    public function __construct()
    {
        $this->teacherSections = new TeacherSectionModel();
        $this->users           = new UserModel();
        $this->sections        = new SectionModel();
        $this->records         = new RecordModel();
        helper(['url', 'form']);
    }

    /**
     * Subject column: subject teacher uses subject_name; adviser uses section class_schedule slots marked adviser_teaches.
     */
    private function subjectColumnLabel(array $teacherSectionRow): string
    {
        $role = strtoupper((string) ($teacherSectionRow['assignment_role'] ?? 'ADVISER'));
        if ($role === 'SUBJECT_TEACHER') {
            $name = trim((string) ($teacherSectionRow['subject_name'] ?? ''));

            return $name !== '' ? $name : '—';
        }

        $raw = $teacherSectionRow['class_schedule'] ?? null;
        if ($raw === null || $raw === '') {
            return '—';
        }
        $sched = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($sched) || empty($sched['slots']) || ! is_array($sched['slots'])) {
            return '—';
        }

        $parts = [];
        foreach ($sched['slots'] as $slot) {
            if (empty($slot['adviser_teaches'])) {
                continue;
            }
            $subj = trim((string) ($slot['subject'] ?? ''));
            if ($subj !== '') {
                $parts[] = $subj;
            }
        }

        if ($parts === []) {
            return '—';
        }

        return implode(', ', $parts);
    }

    public function index(): string
    {
        $userId = (int) session()->get('user_id');
        $invites   = $this->teacherSections->getInvitesForTeacher($userId);
        $accepted  = $this->teacherSections->getAcceptedSectionsForTeacher($userId);

        foreach ($invites as &$inv) {
            $inv['subject_display'] = $this->subjectColumnLabel($inv);
        }
        unset($inv);
        foreach ($accepted as &$row) {
            $row['subject_display'] = $this->subjectColumnLabel($row);
        }
        unset($row);

        $data = [
            'invites'  => $invites,
            'accepted' => $accepted,
            'role'     => session()->get('role') ?? 'TEACHER',
            'name'     => session()->get('name') ?? 'User',
        ];
        return view('Teacher/Sections/index', $data);
    }

    public function accept(int $id): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $row = $this->teacherSections->find($id);
        if (! $row || (int) $row['teacher_id'] !== $userId || ($row['status'] ?? '') !== 'pending') {
            return redirect()->to(base_url('teacher/sections'))->with('error', 'Invite not found or already handled.');
        }
        $this->teacherSections->update($id, ['status' => 'accepted']);
        return redirect()->to(base_url('teacher/sections'))->with('success', 'Section accepted. You can now add students.');
    }

    /** Remove this teacher's assignment (decline pending invite or leave accepted section). */
    public function removeAssignment(int $assignmentId): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $row = $this->teacherSections->find($assignmentId);
        if (! $row || (int) ($row['teacher_id'] ?? 0) !== $userId) {
            return redirect()->to(base_url('teacher/sections'))->with('error', 'Assignment not found.');
        }

        $status = (string) ($row['status'] ?? '');
        if (! in_array($status, ['pending', 'accepted'], true)) {
            return redirect()->to(base_url('teacher/sections'))->with('error', 'Cannot remove this assignment.');
        }

        $this->teacherSections->delete($assignmentId);

        $msg = $status === 'pending'
            ? 'Invite declined.'
            : 'You are no longer assigned to this section.';

        return redirect()->to(base_url('teacher/sections'))->with('success', $msg);
    }

    /** Daily class schedule for a section: times, subjects, assigned teacher or tba. */
    public function sectionSchedule(int $sectionId): string|RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $section = $this->sections->find($sectionId);
        if (! $section) {
            return redirect()->to(base_url('teacher/sections'))->with('error', 'Section not found.');
        }

        $mine = $this->teacherSections->where('section_id', $sectionId)
            ->where('teacher_id', $userId)
            ->where('status', 'accepted')
            ->first();
        if (! $mine) {
            return redirect()->to(base_url('teacher/sections'))->with('error', 'You are not assigned to this section.');
        }

        $schedule = $this->decodeSectionSchedule($section['class_schedule'] ?? null);

        $db = \Config\Database::connect();
        $assignRows = $db->table('teacher_sections')
            ->select('teacher_sections.*, users.name as teacher_name, users.username')
            ->join('users', 'users.id = teacher_sections.teacher_id')
            ->where('teacher_sections.section_id', $sectionId)
            ->where('teacher_sections.status', 'accepted')
            ->get()
            ->getResultArray();

        $adviserName = null;
        $subjectToTeacher = [];
        foreach ($assignRows as $ar) {
            $roleRaw = $ar['assignment_role'] ?? null;
            $role = $roleRaw === null ? 'ADVISER' : strtoupper((string) $roleRaw);
            $display = trim((string) ($ar['teacher_name'] ?? ''));
            if ($display === '') {
                $display = trim((string) ($ar['username'] ?? ''));
            }
            if ($display === '') {
                $display = 'Teacher #' . (int) ($ar['teacher_id'] ?? 0);
            }
            if ($role === 'SUBJECT_TEACHER') {
                $sn = trim((string) ($ar['subject_name'] ?? ''));
                if ($sn !== '') {
                    $subjectToTeacher[$this->normalizeSubjectKey($sn)] = $display;
                }
            } else {
                $adviserName = $display;
            }
        }

        $scheduleRows = [];
        $slots = $schedule['slots'] ?? [];
        $si = 0;
        foreach ($slots as $slot) {
            if ($si === 2) {
                $scheduleRows[] = ['kind' => 'break', 'label' => 'Recess', 'start' => '09:45', 'end' => '10:00'];
            }
            if ($si === 3) {
                $scheduleRows[] = ['kind' => 'break', 'label' => 'Lunch break', 'start' => '11:00', 'end' => '13:00'];
            }

            $subj = trim((string) ($slot['subject'] ?? ''));
            $adviserTeaches = ! empty($slot['adviser_teaches']);
            $teacherLabel = 'tba';
            if ($subj !== '') {
                if ($adviserTeaches) {
                    $teacherLabel = ($adviserName !== null && $adviserName !== '') ? $adviserName : 'tba';
                } else {
                    $teacherLabel = $subjectToTeacher[$this->normalizeSubjectKey($subj)] ?? 'tba';
                }
            }

            $scheduleRows[] = [
                'kind'    => 'class',
                'start'   => (string) ($slot['start'] ?? ''),
                'end'     => (string) ($slot['end'] ?? ''),
                'subject' => $subj !== '' ? $subj : '—',
                'teacher' => $teacherLabel,
            ];
            $si++;
        }
        $scheduleRows[] = [
            'kind' => 'dismissal',
            'time' => (string) ($schedule['dismissal_time'] ?? '15:00'),
        ];

        $data = [
            'section'        => $section,
            'scheduleRows'   => $scheduleRows,
            'role'           => session()->get('role') ?? 'TEACHER',
            'name'           => session()->get('name') ?? 'User',
        ];

        return view('Teacher/Sections/section_schedule', $data);
    }

    public function sectionStudents(int $sectionId): string|RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $section = $this->sections->find($sectionId);
        if (! $section) {
            return redirect()->to(base_url('teacher/sections'))->with('error', 'Section not found.');
        }
        $assignment = $this->teacherSections->where('section_id', $sectionId)
            ->where('teacher_id', $userId)
            ->where('status', 'accepted')
            ->groupStart()
                ->where('assignment_role', 'ADVISER')
                ->orWhere('assignment_role', null)
            ->groupEnd()
            ->first();
        if (! $assignment) {
            return redirect()->to(base_url('teacher/sections'))->with('error', 'Only the adviser can manage students for this section.');
        }

        $studentsInSection = $this->users->where('role', 'STUDENT')->where('section_id', $sectionId)->findAll();

        $sectionGradeDigit = $this->normalizeGradeToDigit($section['grade_level'] ?? '');
        // Unassigned students whose grade matches this section only (e.g. Grade 2 section → Grade 2 students only).
        $candidates = $this->users->where('role', 'STUDENT')
            ->where('is_active', 1)
            ->groupStart()
                ->where('section_id', null)
                ->orWhere('section_id', 0)
            ->groupEnd()
            ->orderBy('surname', 'ASC')
            ->orderBy('first_name', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        $addableStudents = [];
        foreach ($candidates as $row) {
            if ($sectionGradeDigit !== '' && $this->normalizeGradeToDigit($row['grade_level'] ?? '') === $sectionGradeDigit) {
                $addableStudents[] = $row;
            }
        }

        $data = [
            'section'           => $section,
            'studentsInSection' => $studentsInSection,
            'addableStudents'   => $addableStudents,
            'role'              => session()->get('role') ?? 'TEACHER',
            'name'              => session()->get('name') ?? 'User',
        ];
        return view('Teacher/Sections/section_students', $data);
    }

    public function addStudent(): RedirectResponse
    {
        $userId    = (int) session()->get('user_id');
        $sectionId = (int) $this->request->getPost('section_id');
        $studentId = (int) $this->request->getPost('student_id');

        if ($sectionId <= 0 || $studentId <= 0) {
            return redirect()->back()->with('error', 'Section and student are required.');
        }
        $assignment = $this->teacherSections->where('section_id', $sectionId)
            ->where('teacher_id', $userId)
            ->where('status', 'accepted')
            ->groupStart()
                ->where('assignment_role', 'ADVISER')
                ->orWhere('assignment_role', null)
            ->groupEnd()
            ->first();
        if (! $assignment) {
            return redirect()->back()->with('error', 'Only the adviser can add students to this section.');
        }
        $sectionRow = $this->sections->find($sectionId);
        if (! $sectionRow) {
            return redirect()->back()->with('error', 'Section not found.');
        }
        $student = $this->users->where('role', 'STUDENT')->find($studentId);
        if (! $student) {
            return redirect()->back()->with('error', 'Student not found.');
        }
        $sg = $this->normalizeGradeToDigit($sectionRow['grade_level'] ?? '');
        $ug = $this->normalizeGradeToDigit($student['grade_level'] ?? '');
        if ($sg === '' || $ug === '' || $sg !== $ug) {
            return redirect()->back()->with('error', 'This student’s grade does not match this section.');
        }
        $currentSectionId = (int) ($student['section_id'] ?? 0);
        if ($currentSectionId > 0 && $currentSectionId !== $sectionId) {
            return redirect()->back()->with('error', 'This student is already enrolled in another section. They cannot be added here.');
        }
        $this->users->update($studentId, ['section_id' => $sectionId]);
        return redirect()->back()->with('success', 'Student added to section.');
    }

    private function normalizeSubjectKey(string $subject): string
    {
        return strtolower(trim($subject));
    }

    /** @return array{slots: list<array<string, mixed>>, dismissal_time: string} */
    private function decodeSectionSchedule(?string $raw): array
    {
        $defaults = [
            'slots' => [
                ['slot' => 1, 'subject' => '', 'start' => '07:45', 'end' => '08:45', 'adviser_teaches' => false],
                ['slot' => 2, 'subject' => '', 'start' => '08:45', 'end' => '09:45', 'adviser_teaches' => false],
                ['slot' => 3, 'subject' => '', 'start' => '10:00', 'end' => '11:00', 'adviser_teaches' => false],
                ['slot' => 4, 'subject' => '', 'start' => '13:00', 'end' => '14:00', 'adviser_teaches' => false],
                ['slot' => 5, 'subject' => '', 'start' => '14:00', 'end' => '15:00', 'adviser_teaches' => false],
            ],
            'dismissal_time' => '15:00',
        ];
        if ($raw === null || $raw === '') {
            return $defaults;
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || empty($decoded['slots']) || ! is_array($decoded['slots'])) {
            return $defaults;
        }
        $out = $defaults;
        foreach ($defaults['slots'] as $idx => $def) {
            $incoming = $decoded['slots'][$idx] ?? [];
            if (is_array($incoming)) {
                $out['slots'][$idx]['subject'] = trim((string) ($incoming['subject'] ?? ''));
                $out['slots'][$idx]['adviser_teaches'] = ! empty($incoming['adviser_teaches']);
            }
        }
        if (! empty($decoded['dismissal_time'])) {
            $out['dismissal_time'] = (string) $decoded['dismissal_time'];
        }

        return $out;
    }

    /** Normalize stored grade text to "1"…"6" for comparison with sections.grade_level. */
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
