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

        $data = [
            'sections' => $list,
            'role'     => session()->get('role') ?? 'ADMIN',
            'name'     => session()->get('name') ?? 'User',
        ];
        return view('Admin/Sections/index', $data);
    }

    public function create(): string
    {
        $data = [
            'role' => session()->get('role') ?? 'ADMIN',
            'name' => session()->get('name') ?? 'User',
        ];
        return view('Admin/Sections/create', $data);
    }

    public function store(): RedirectResponse
    {
        $name       = trim((string) $this->request->getPost('name'));
        $gradeLevel = trim((string) $this->request->getPost('grade_level'));

        if ($name === '' || $gradeLevel === '') {
            return redirect()->back()->withInput()->with('error', 'Section name and grade level are required.');
        }

        $this->sections->insert(['name' => $name, 'grade_level' => $gradeLevel]);
        return redirect()->to(base_url('admin/sections'))->with('success', 'Section created.');
    }

    public function edit(int $id): string|RedirectResponse
    {
        $section = $this->sections->find($id);
        if (! $section) {
            return redirect()->to(base_url('admin/sections'))->with('error', 'Section not found.');
        }
        $data = [
            'section' => $section,
            'role'    => session()->get('role') ?? 'ADMIN',
            'name'    => session()->get('name') ?? 'User',
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
        $this->sections->update($id, ['name' => $name, 'grade_level' => $gradeLevel]);
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

    /** Invite a teacher to a section (admin). */
    public function invite(): string|RedirectResponse
    {
        $sectionId  = (int) $this->request->getGetPost('section_id');
        $teacherId  = (int) $this->request->getGetPost('teacher_id');

        if ($sectionId <= 0 || $teacherId <= 0) {
            return redirect()->back()->with('error', 'Section and teacher are required.');
        }

        $exists = $this->teacherSections->where('section_id', $sectionId)
            ->where('teacher_id', $teacherId)->first();
        if ($exists) {
            return redirect()->back()->with('error', 'This teacher is already invited or assigned to this section.');
        }

        $this->teacherSections->insert([
            'section_id' => $sectionId,
            'teacher_id' => $teacherId,
            'status'     => 'pending',
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

        $data = [
            'section'     => $section,
            'assignments' => $assignments,
            'teachers'    => $teachers,
            'role'        => session()->get('role') ?? 'ADMIN',
            'name'        => session()->get('name') ?? 'User',
        ];
        return view('Admin/Sections/section_teachers', $data);
    }
}
