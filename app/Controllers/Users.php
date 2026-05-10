<?php

namespace App\Controllers;

use App\Libraries\PasswordReuseGuard;
use App\Models\UserModel;
use App\Models\SectionModel;
use CodeIgniter\HTTP\RedirectResponse;

class Users extends BaseController
{
    private const DEFAULT_NEW_USER_PASSWORD = 'ajes2026';

    protected UserModel   $users;
    protected SectionModel $sections;

    public function __construct()
    {
        $this->users    = new UserModel();
        $this->sections = new SectionModel();
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $showDeleted = (bool) $this->request->getGet('deleted');
        if ($showDeleted) {
            $list = $this->users->onlyDeleted()->orderBy('role')->orderBy('name')->findAll();
            $deletedCount = count($list);
        } else {
            $list = $this->users->orderBy('role')->orderBy('name')->findAll();
            $deletedCount = $this->users->onlyDeleted()->countAllResults();
        }
        $data = [
            'users'        => $list,
            'show_deleted' => $showDeleted,
            'deleted_count' => $deletedCount,
            'role'         => session()->get('role') ?? 'ADMIN',
            'name'         => session()->get('name') ?? 'User',
        ];
        return view('Admin/Users/index', $data);
    }

    public function create(): string
    {
        $data = [
            'role' => session()->get('role') ?? 'ADMIN',
            'name' => session()->get('name') ?? 'User',
        ];
        return view('Admin/Users/create', $data);
    }

    public function store(): RedirectResponse
    {
        $firstName = trim((string) $this->request->getPost('first_name'));
        $middleName = trim((string) $this->request->getPost('middle_name'));
        $surname = trim((string) $this->request->getPost('surname'));
        $suffix = trim((string) $this->request->getPost('suffix'));
        $name     = trim($firstName . ' ' . $middleName . ' ' . $surname . ' ' . $suffix);
        $email    = trim((string) $this->request->getPost('email'));
        $username = trim((string) $this->request->getPost('username'));
        $password = self::DEFAULT_NEW_USER_PASSWORD;
        $role     = trim((string) $this->request->getPost('role'));
        $isActive  = (int) $this->request->getPost('is_active');
        $birthdate = trim((string) $this->request->getPost('birthdate'));
        $age       = $this->computeAgeFromBirthdate($birthdate);

        if ($firstName === '' || $surname === '' || $email === '' || $username === '' || $role === '') {
            return redirect()->back()->withInput()->with('error', 'First name, surname, email, username and role are required.');
        }
        if (! preg_match('/^[a-zA-ZÑñ ]+$/', $firstName) || ($middleName !== '' && ! preg_match('/^[a-zA-ZÑñ ]+$/', $middleName)) || ! preg_match('/^[a-zA-ZÑñ ]+$/', $surname) || ($suffix !== '' && ! preg_match('/^[a-zA-ZÑñ. ]+$/', $suffix))) {
            return redirect()->back()->withInput()->with('error', 'First name, middle name, surname, and suffix: letters (including Ñ/ñ) and spaces only. Suffix may include dot.');
        }
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return redirect()->back()->withInput()->with('error', 'Username: letters, numbers, and underscore only. No special characters.');
        }
        if (! preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            return redirect()->back()->withInput()->with('error', 'Email: invalid format. Only letters, numbers, and @ are allowed (e.g. user@domain.com).');
        }
        if ($this->users->where('email', $email)->first()) {
            return redirect()->back()->withInput()->with('error', 'Email already in use.');
        }
        if ($this->users->where('username', $username)->first()) {
            return redirect()->back()->withInput()->with('error', 'Username already in use.');
        }

        $data = [
            'name'          => $name,
            'email'         => $email,
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => $role,
            'is_active'     => $isActive ? 1 : 0,
        ];
        if ($role === 'STUDENT') {
            $gradePick = trim((string) $this->request->getPost('grade_level'));
            if (! in_array($gradePick, ['1', '2', '3', '4', '5', '6'], true)) {
                return redirect()->back()->withInput()->with('error', 'Choose a grade level from Grade 1 to Grade 6.');
            }
            $data['student_id'] = trim((string) $this->request->getPost('student_id'));
            $data['gender'] = trim((string) $this->request->getPost('gender'));
            $data['grade_level'] = $gradePick;
            $data['birthdate'] = $birthdate !== '' ? $birthdate : null;
            $data['age'] = $age;
            $data['address'] = trim((string) $this->request->getPost('address'));
            $data['guardian_name'] = trim((string) $this->request->getPost('guardian_name'));
            $data['guardian_contact'] = trim((string) $this->request->getPost('guardian_contact'));
        }
        $this->users->insert($data);
        return redirect()->to(base_url('admin/users'))->with('success', 'User created.');
    }

    public function edit(int $id): string|RedirectResponse
    {
        $user = $this->users->find($id);
        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'User not found.');
        }
        $currentRole = session()->get('role') ?? '';
        $currentUserId = (int) session()->get('user_id');
        $is_editing_self = ($currentRole === 'ADMIN' && $currentUserId === (int) $id);
        $sections = $this->sections->orderBy('grade_level')->orderBy('name')->findAll();
        $data = [
            'user'            => $user,
            'sections'        => $sections,
            'role'            => $currentRole ?: 'ADMIN',
            'name'            => session()->get('name') ?? 'User',
            'is_editing_self' => $is_editing_self,
        ];
        return view('Admin/Users/edit', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $user = $this->users->find($id);
        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'User not found.');
        }
        $currentRole = session()->get('role') ?? '';
        $currentUserId = (int) session()->get('user_id');
        $is_editing_self = ($currentRole === 'ADMIN' && $currentUserId === $id);

        $firstName = trim((string) $this->request->getPost('first_name'));
        $middleName = trim((string) $this->request->getPost('middle_name'));
        $surname = trim((string) $this->request->getPost('surname'));
        $suffix = trim((string) $this->request->getPost('suffix'));
        $name     = trim($firstName . ' ' . $middleName . ' ' . $surname . ' ' . $suffix);
        $email    = trim((string) $this->request->getPost('email'));
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $role     = $is_editing_self ? ($user['role'] ?? 'ADMIN') : trim((string) $this->request->getPost('role'));
        $sectionId = $this->request->getPost('section_id');
        $isActive  = (int) $this->request->getPost('is_active');
        $birthdate = trim((string) $this->request->getPost('birthdate'));
        $age       = $this->computeAgeFromBirthdate($birthdate);

        if ($firstName === '' || $surname === '' || $email === '' || $username === '' || $role === '') {
            return redirect()->back()->withInput()->with('error', 'First name, surname, email, username and role are required.');
        }
        if (! preg_match('/^[a-zA-ZÑñ ]+$/', $firstName) || ($middleName !== '' && ! preg_match('/^[a-zA-ZÑñ ]+$/', $middleName)) || ! preg_match('/^[a-zA-ZÑñ ]+$/', $surname) || ($suffix !== '' && ! preg_match('/^[a-zA-ZÑñ. ]+$/', $suffix))) {
            return redirect()->back()->withInput()->with('error', 'First name, middle name, surname, and suffix: letters (including Ñ/ñ) and spaces only. Suffix may include dot.');
        }
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return redirect()->back()->withInput()->with('error', 'Username: letters, numbers, and underscore only. No special characters.');
        }
        if (! preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            return redirect()->back()->withInput()->with('error', 'Email: invalid format. Only letters, numbers, and @ are allowed (e.g. user@domain.com).');
        }
        $existing = $this->users->where('email', $email)->where('id !=', $id)->first();
        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Email already in use.');
        }
        $existing = $this->users->where('username', $username)->where('id !=', $id)->first();
        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Username already in use.');
        }

        $data = [
            'name'      => $name,
            'email'     => $email,
            'username'  => $username,
            'role'      => $role,
            'is_active' => $isActive ? 1 : 0,
        ];
        if ($role === 'STUDENT') {
            $data['student_id'] = trim((string) $this->request->getPost('student_id'));
            $data['gender'] = trim((string) $this->request->getPost('gender'));
            $data['grade_level'] = trim((string) $this->request->getPost('grade_level'));
            $data['birthdate'] = $birthdate !== '' ? $birthdate : null;
            $data['age'] = $age;
            $data['address'] = trim((string) $this->request->getPost('address'));
            $data['guardian_name'] = trim((string) $this->request->getPost('guardian_name'));
            $data['guardian_contact'] = trim((string) $this->request->getPost('guardian_contact'));
        }
        if ($password !== '') {
            if (strlen($password) < 6) {
                return redirect()->back()->withInput()->with('error', 'Password must be at least 6 characters.');
            }
            $hist = PasswordReuseGuard::historyFromDb($user['password_history'] ?? null);
            if (PasswordReuseGuard::isPasswordReused($password, (string) ($user['password_hash'] ?? ''), $hist)) {
                return redirect()->back()->withInput()->with('error', 'You cannot reuse your current password or a recently used one. Please choose a different password.');
            }
            $data['password_history'] = PasswordReuseGuard::appendPreviousHash((string) ($user['password_hash'] ?? ''), $hist);
            $data['password_hash']    = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($role === 'TEACHER') {
            $data['section_id'] = ($sectionId !== null && $sectionId !== '') ? (int) $sectionId : null;
        } elseif ($role === 'STUDENT') {
            $existingSectionId = (int) ($user['section_id'] ?? 0);
            if ($existingSectionId > 0) {
                $data['section_id'] = $existingSectionId;
            } elseif ($sectionId !== null && $sectionId !== '') {
                $data['section_id'] = (int) $sectionId;
            } else {
                $data['section_id'] = null;
            }
        } else {
            $data['section_id'] = null;
        }
        $this->users->update($id, $data);

        if ($password !== '' && $currentUserId === (int) $id) {
            session()->destroy();
            return redirect()->to(base_url('/?password_changed=1'));
        }

        return redirect()->to(base_url('admin/users'))->with('success', 'User updated.');
    }

    private function computeAgeFromBirthdate(string $birthdate): ?int
    {
        if ($birthdate === '') {
            return null;
        }
        try {
            $dob = new \DateTimeImmutable($birthdate);
            $now = new \DateTimeImmutable('today');
            if ($dob > $now) {
                return null;
            }
            return (int) $dob->diff($now)->y;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function delete(int $id): RedirectResponse
    {
        if ((int) session()->get('user_id') === $id) {
            return redirect()->to(base_url('admin/users'))->with('error', 'You cannot delete your own account.');
        }
        $user = $this->users->find($id);
        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'User not found.');
        }
        if (($user['role'] ?? '') === 'ADMIN') {
            return redirect()->to(base_url('admin/users'))->with('error', 'Admin accounts cannot be deleted.');
        }
        $this->users->update($id, ['is_active' => 0]);
        $this->users->delete($id);
        return redirect()->to(base_url('admin/users'))->with('success', 'User archived. You can restore them from Deleted users.');
    }

    public function restore(int $id): RedirectResponse
    {
        $user = $this->users->onlyDeleted()->find($id);
        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'User not found or not deleted.');
        }
        $this->users->update($id, ['deleted_at' => null, 'is_active' => 1]);
        return redirect()->to(base_url('admin/users'))->with('success', 'User restored.');
    }
}
