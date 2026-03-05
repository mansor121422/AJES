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

    public function index(): string
    {
        $userId = (int) session()->get('user_id');
        $invites   = $this->teacherSections->getInvitesForTeacher($userId);
        $accepted  = $this->teacherSections->getAcceptedSectionsForTeacher($userId);

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

    public function sectionStudents(int $sectionId): string|RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $section = $this->sections->find($sectionId);
        if (! $section) {
            return redirect()->to(base_url('teacher/sections'))->with('error', 'Section not found.');
        }
        $assignment = $this->teacherSections->where('section_id', $sectionId)
            ->where('teacher_id', $userId)->where('status', 'accepted')->first();
        if (! $assignment) {
            return redirect()->to(base_url('teacher/sections'))->with('error', 'You are not assigned to this section.');
        }

        $studentsInSection = $this->users->where('role', 'STUDENT')->where('section_id', $sectionId)->findAll();

        $db = \Config\Database::connect();
        $studentIdsWithRecords = $db->table('records')->select('student_id')->distinct()->get()->getResultArray();
        $idsWithRecords = array_column($studentIdsWithRecords, 'student_id');
        $addableStudents = [];
        if (! empty($idsWithRecords)) {
            $addableStudents = $this->users->where('role', 'STUDENT')
                ->whereIn('id', $idsWithRecords)
                ->groupStart()
                ->where('section_id', null)
                ->orWhere('section_id !=', $sectionId)
                ->groupEnd()
                ->findAll();
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
            ->where('teacher_id', $userId)->where('status', 'accepted')->first();
        if (! $assignment) {
            return redirect()->back()->with('error', 'You are not assigned to this section.');
        }
        $student = $this->users->where('role', 'STUDENT')->find($studentId);
        if (! $student) {
            return redirect()->back()->with('error', 'Student not found.');
        }
        $db = \Config\Database::connect();
        $hasRecord = $db->table('records')->where('student_id', $studentId)->countAllResults() > 0;
        if (! $hasRecord) {
            return redirect()->back()->with('error', 'Only students who have a record (from Guidance) can be added to a section.');
        }
        $this->users->update($studentId, ['section_id' => $sectionId]);
        return redirect()->back()->with('success', 'Student added to section.');
    }
}
