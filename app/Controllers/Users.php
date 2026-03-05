<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\SectionModel;
use CodeIgniter\HTTP\RedirectResponse;

class Users extends BaseController
{
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
        $list = $this->users->orderBy('role')->orderBy('name')->findAll();
        $data = [
            'users' => $list,
            'role'  => session()->get('role') ?? 'ADMIN',
            'name'  => session()->get('name') ?? 'User',
        ];
        return view('Admin/Users/index', $data);
    }

    public function create(): string
    {
        $sections = $this->sections->orderBy('grade_level')->orderBy('name')->findAll();
        $data = [
            'sections' => $sections,
            'role'     => session()->get('role') ?? 'ADMIN',
            'name'     => session()->get('name') ?? 'User',
        ];
        return view('Admin/Users/create', $data);
    }

    public function store(): RedirectResponse
    {
        $name     = trim((string) $this->request->getPost('name'));
        $email    = trim((string) $this->request->getPost('email'));
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $role     = trim((string) $this->request->getPost('role'));
        $sectionId = $this->request->getPost('section_id');
        $isActive  = (int) $this->request->getPost('is_active');

        if ($name === '' || $email === '' || $username === '' || $password === '' || $role === '') {
            return redirect()->back()->withInput()->with('error', 'Name, email, username, password and role are required.');
        }
        if (strlen($password) < 6) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 6 characters.');
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
        if ($sectionId !== null && $sectionId !== '' && in_array($role, ['TEACHER', 'STUDENT'], true)) {
            $data['section_id'] = (int) $sectionId;
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
        $sections = $this->sections->orderBy('grade_level')->orderBy('name')->findAll();
        $data = [
            'user'     => $user,
            'sections' => $sections,
            'role'     => session()->get('role') ?? 'ADMIN',
            'name'     => session()->get('name') ?? 'User',
        ];
        return view('Admin/Users/edit', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $user = $this->users->find($id);
        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'User not found.');
        }
        $name     = trim((string) $this->request->getPost('name'));
        $email    = trim((string) $this->request->getPost('email'));
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $role     = trim((string) $this->request->getPost('role'));
        $sectionId = $this->request->getPost('section_id');
        $isActive  = (int) $this->request->getPost('is_active');

        if ($name === '' || $email === '' || $username === '' || $role === '') {
            return redirect()->back()->withInput()->with('error', 'Name, email, username and role are required.');
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
        if ($password !== '') {
            if (strlen($password) < 6) {
                return redirect()->back()->withInput()->with('error', 'Password must be at least 6 characters.');
            }
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($sectionId !== null && $sectionId !== '' && in_array($role, ['TEACHER', 'STUDENT'], true)) {
            $data['section_id'] = (int) $sectionId;
        } else {
            $data['section_id'] = null;
        }
        $this->users->update($id, $data);
        return redirect()->to(base_url('admin/users'))->with('success', 'User updated.');
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
        $this->users->delete($id);
        return redirect()->to(base_url('admin/users'))->with('success', 'User deleted.');
    }
}
