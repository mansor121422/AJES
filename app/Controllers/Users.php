<?php

namespace App\Controllers;

use App\Libraries\PasswordReuseGuard;
use App\Libraries\AdminPrivilege;
use App\Libraries\RoleRegistry;
use App\Libraries\AuditLogger;
use App\Libraries\ActivityLogger;
use App\Libraries\SecureHash;
use App\Libraries\TransactionManager;
use App\Models\UserModel;
use App\Models\SectionModel;
use App\Models\RoleModel;
use CodeIgniter\HTTP\RedirectResponse;

class Users extends BaseController
{
    private const DEFAULT_NEW_USER_PASSWORD = 'ajes2026';

    protected UserModel   $users;
    protected SectionModel $sections;
    protected RoleModel $roleModel;

    public function __construct()
    {
        $this->users     = new UserModel();
        $this->sections  = new SectionModel();
        $this->roleModel = new RoleModel();
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $section = strtolower(trim((string) $this->request->getGet('section')));
        if ($section === 'roles') {
            return view('Admin/Users/index', [
                'active_section' => 'roles',
                'roles'          => $this->roleModel->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll(),
                'privilege_labels' => AdminPrivilege::labels(),
                'role'           => session()->get('role') ?? 'ADMIN',
                'name'           => session()->get('name') ?? 'User',
            ]);
        }

        $showDeleted = (bool) $this->request->getGet('deleted');
        if ($showDeleted) {
            $list = $this->users->onlyDeleted()->orderBy('role')->orderBy('name')->findAll();
            $deletedCount = count($list);
        } else {
            $list = $this->users->orderBy('role')->orderBy('name')->findAll();
            $deletedCount = $this->users->onlyDeleted()->countAllResults();
        }

        return view('Admin/Users/index', [
            'active_section' => 'users',
            'users'          => $list,
            'show_deleted'   => $showDeleted,
            'deleted_count'  => $deletedCount,
            'role'           => session()->get('role') ?? 'ADMIN',
            'name'           => session()->get('name') ?? 'User',
        ]);
    }

    public function createRole(): string
    {
        return view('Admin/Users/role_form', $this->roleFormData(null));
    }

    public function storeRole(): RedirectResponse
    {
        return $this->saveRole(null);
    }

    public function editRole(int $id): string|RedirectResponse
    {
        $row = $this->roleModel->find($id);
        if (! $row) {
            return redirect()->to(base_url('admin/users?section=roles'))->with('error', 'Role not found.');
        }

        return view('Admin/Users/role_form', $this->roleFormData($row));
    }

    public function updateRole(int $id): RedirectResponse
    {
        $row = $this->roleModel->find($id);
        if (! $row) {
            return redirect()->to(base_url('admin/users?section=roles'))->with('error', 'Role not found.');
        }

        return $this->saveRole($row);
    }

    public function deleteRole(int $id): RedirectResponse
    {
        $row = $this->roleModel->find($id);
        if (! $row) {
            return redirect()->to(base_url('admin/users?section=roles'))->with('error', 'Role not found.');
        }
        if ((int) ($row['is_system'] ?? 0) === 1) {
            return redirect()->to(base_url('admin/users?section=roles'))->with('error', 'System roles cannot be deleted.');
        }

        $slug = strtoupper((string) ($row['slug'] ?? ''));
        if ($this->users->where('role', $slug)->countAllResults() > 0) {
            return redirect()->to(base_url('admin/users?section=roles'))->with('error', 'Cannot delete a role that is assigned to users.');
        }

        $this->roleModel->delete($id);
        RoleRegistry::clearCache();

        return redirect()->to(base_url('admin/users?section=roles'))->with('success', 'Role deleted.');
    }

    public function create(): string|RedirectResponse
    {
        $roleOptions = RoleRegistry::roleOptions();
        if ($roleOptions === []) {
            return redirect()->to(base_url('admin/users/roles/create'))
                ->with('error', 'Create a role first, then you can add users.');
        }

        return view('Admin/Users/create', [
            'role'                  => session()->get('role') ?? 'ADMIN',
            'name'                  => session()->get('name') ?? 'User',
            'role_options'          => $roleOptions,
            'role_dashboard_types'  => $this->roleDashboardTypesMap(),
            'sections'              => $this->sections->orderBy('grade_level')->orderBy('name')->findAll(),
        ]);
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
        $role = strtoupper(trim((string) $this->request->getPost('role')));
        $isActive  = (int) $this->request->getPost('is_active');
        $birthdate = trim((string) $this->request->getPost('birthdate'));
        $age       = $this->computeAgeFromBirthdate($birthdate);
        $sectionId = $this->request->getPost('section_id');

        if ($firstName === '' || $surname === '' || $email === '' || $username === '' || $role === '') {
            return redirect()->back()->withInput()->with('error', 'First name, surname, email, username and role are required.');
        }
        if (! RoleRegistry::exists($role)) {
            return redirect()->back()->withInput()->with('error', 'Please select a valid role.');
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
            'password_hash' => SecureHash::make($password),
            'role'          => $role,
            'is_active'     => $isActive ? 1 : 0,
            'mfa_enabled'   => (int) $this->request->getPost('mfa_enabled'),
        ];
        $privilegesJson = $this->encodedPrivilegesForRole($role);
        if ($privilegesJson === false) {
            return redirect()->back()->withInput()->with('error', 'The selected role has no privileges. Edit the role and assign privileges first.');
        }
        $data['admin_privileges'] = $privilegesJson;
        if ($this->roleUsesTeacherFields($role)) {
            $data['section_id'] = ($sectionId !== null && $sectionId !== '') ? (int) $sectionId : null;
        }
        if ($this->roleUsesStudentFields($role)) {
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
            if ($sectionId !== null && $sectionId !== '') {
                $data['section_id'] = (int) $sectionId;
            }
        }
        try {
            $newId = TransactionManager::run('USER_CREATE', function ($db) use ($data, $name, $role) {
                $this->users->insert($data);
                $id = $this->users->getInsertID();
                AuditLogger::userCreated((int) $id, $name, $role);
                ActivityLogger::log('USER_CREATE', 'users', 'Created user "' . $name . '" (role: ' . $role . ').');
                return (int) $id;
            }, 'users');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create user: ' . $e->getMessage());
        }

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
        $is_editing_self = (in_array($currentRole, ['ADMIN', 'SUPER_ADMIN'], true) && $currentUserId === (int) $id);
        $sections = $this->sections->orderBy('grade_level')->orderBy('name')->findAll();
        $data = [
            'user'            => $user,
            'sections'        => $sections,
            'role'            => $currentRole ?: 'ADMIN',
            'name'            => session()->get('name') ?? 'User',
            'is_editing_self'      => $is_editing_self,
            'role_options'         => RoleRegistry::roleOptions(),
            'role_dashboard_types' => $this->roleDashboardTypesMap(),
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
        $is_editing_self = (in_array($currentRole, ['ADMIN', 'SUPER_ADMIN'], true) && $currentUserId === $id);

        $firstName = trim((string) $this->request->getPost('first_name'));
        $middleName = trim((string) $this->request->getPost('middle_name'));
        $surname = trim((string) $this->request->getPost('surname'));
        $suffix = trim((string) $this->request->getPost('suffix'));
        $name     = trim($firstName . ' ' . $middleName . ' ' . $surname . ' ' . $suffix);
        $email    = trim((string) $this->request->getPost('email'));
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $existingRole = strtoupper((string) ($user['role'] ?? 'STAFF'));
        $postedRole = strtoupper(trim((string) $this->request->getPost('role')));
        if ($is_editing_self) {
            $role = $existingRole !== '' ? $existingRole : 'ADMIN';
        } else {
            $role = ($postedRole !== '' && RoleRegistry::exists($postedRole)) ? $postedRole : $existingRole;
        }
        if ($role === '') {
            $role = 'STAFF';
        }
        $sectionId = $this->request->getPost('section_id');
        $isActive  = (int) $this->request->getPost('is_active');
        $birthdate = trim((string) $this->request->getPost('birthdate'));
        $age       = $this->computeAgeFromBirthdate($birthdate);

        if ($firstName === '' || $surname === '' || $email === '' || $username === '') {
            return redirect()->back()->withInput()->with('error', 'First name, surname, email and username are required.');
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
            'name'        => $name,
            'email'       => $email,
            'username'    => $username,
            'role'        => $role,
            'is_active'   => $isActive ? 1 : 0,
            'mfa_enabled' => (int) $this->request->getPost('mfa_enabled'),
        ];
        if (! $is_editing_self) {
            $privilegesJson = $this->encodedPrivilegesForRole($role);
            if ($privilegesJson === false) {
                return redirect()->back()->withInput()->with('error', 'The selected role has no privileges. Edit the role and assign privileges first.');
            }
            $data['admin_privileges'] = $privilegesJson;
        }
        if ($this->roleUsesStudentFields($role)) {
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
            $data['password_hash']    = SecureHash::make($password);
        }
        if ($this->roleUsesTeacherFields($role)) {
            $data['section_id'] = ($sectionId !== null && $sectionId !== '') ? (int) $sectionId : null;
        } elseif ($this->roleUsesStudentFields($role)) {
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
        try {
            TransactionManager::run('USER_UPDATE', function ($db) use ($id, $data, $user, $name, $role) {
                $this->users->update($id, $data);

                $oldRole = $user['role'] ?? '';
                if ($oldRole !== '' && $oldRole !== $role) {
                    AuditLogger::roleChanged($id, $oldRole, $role);
                }
                AuditLogger::userUpdated($id, 'Updated user "' . $name . '" (role: ' . $role . ').');
                ActivityLogger::log('USER_UPDATE', 'users', 'Updated user "' . $name . '" (ID: ' . $id . ').');
            }, 'users', $id);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update user: ' . $e->getMessage());
        }

        if ($currentUserId === (int) $id) {
            session()->set('feature_privileges', AdminPrivilege::normalize($data['admin_privileges'] ?? []));
        }

        if ($password !== '' && $currentUserId === (int) $id) {
            session()->destroy();
            return redirect()->to(base_url('/?password_changed=1'));
        }

        return redirect()->to(base_url('admin/users'))->with('success', 'User updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function roleFormData(?array $row): array
    {
        return [
            'role_row'               => $row,
            'privilege_labels'       => AdminPrivilege::labels(),
            'assigned_privileges'    => AdminPrivilege::normalize($row['privileges'] ?? []),
            'role'                   => session()->get('role') ?? 'ADMIN',
            'name'                   => session()->get('name') ?? 'User',
        ];
    }

    /**
     * @param array<string, mixed>|null $existing
     */
    private function saveRole(?array $existing): RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        $isEdit = $existing !== null;

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Role name is required.');
        }

        $slug = $isEdit
            ? strtoupper((string) ($existing['slug'] ?? ''))
            : RoleModel::slugFromName($name);

        if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $slug)) {
            return redirect()->back()->withInput()->with('error', 'Role code must be uppercase letters, numbers, and underscores.');
        }

        $dup = $this->roleModel->where('slug', $slug);
        if ($isEdit) {
            $dup = $dup->where('id !=', (int) $existing['id']);
        }
        if ($dup->first()) {
            return redirect()->back()->withInput()->with('error', 'Role code already exists.');
        }

        $privileges = AdminPrivilege::normalize($this->request->getPost('privileges'));
        if ($privileges === []) {
            return redirect()->back()->withInput()->with('error', 'Select at least one privilege for this role.');
        }

        $data = [
            'name'       => $name,
            'privileges' => json_encode($privileges, JSON_UNESCAPED_SLASHES),
        ];

        if (! $isEdit) {
            $data['slug'] = $slug;
            $data['dashboard_type'] = 'generic';
            $data['is_system'] = 0;
            $maxRow = $this->roleModel->selectMax('sort_order', 'max_sort')->first();
            $data['sort_order'] = ((int) ($maxRow['max_sort'] ?? 0)) + 1;
            $this->roleModel->insert($data);
        } else {
            $this->roleModel->update((int) $existing['id'], $data);
        }

        RoleRegistry::clearCache();

        if ($this->request->getPost('return_to') === 'create') {
            return redirect()->to(base_url('admin/users/create'))
                ->with('success', 'Role created. Select it below when adding the user.')
                ->with('new_role_slug', $slug);
        }

        return redirect()->to(base_url('admin/users?section=roles'))
            ->with('success', $isEdit ? 'Role updated.' : 'Role created.');
    }

    /**
     * @return array<string, string> slug => dashboard_type
     */
    private function roleDashboardTypesMap(): array
    {
        $map = [];
        foreach (RoleRegistry::all() as $slug => $row) {
            $map[$slug] = RoleRegistry::dashboardType($slug);
        }

        return $map;
    }

    private function roleUsesStudentFields(string $role): bool
    {
        $slug = strtoupper(trim($role));

        return $slug === 'STUDENT' || RoleRegistry::dashboardType($slug) === 'student';
    }

    private function roleUsesTeacherFields(string $role): bool
    {
        $slug = strtoupper(trim($role));

        return $slug === 'TEACHER' || RoleRegistry::dashboardType($slug) === 'teacher';
    }

    /**
     * @return string|false JSON string, false if role has no privileges
     */
    private function encodedPrivilegesForRole(string $roleSlug): string|false
    {
        $privileges = RoleRegistry::privilegesForRole($roleSlug);
        if ($privileges === []) {
            return false;
        }

        return json_encode($privileges, JSON_UNESCAPED_SLASHES);
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
        try {
            $userName = $user['name'] ?? $user['username'] ?? '';
            TransactionManager::run('USER_DELETE', function ($db) use ($id, $userName) {
                $this->users->update($id, ['is_active' => 0]);
                $this->users->delete($id);
                AuditLogger::userDeleted($id, $userName);
                ActivityLogger::log('USER_DELETE', 'users', 'Archived user "' . $userName . '" (ID: ' . $id . ').');
            }, 'users', $id);
        } catch (\Throwable $e) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Failed to delete user: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/users'))->with('success', 'User archived. You can restore them from Deleted users.');
    }

    public function restore(int $id): RedirectResponse
    {
        $user = $this->users->onlyDeleted()->find($id);
        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'User not found or not deleted.');
        }
        try {
            $userName = $user['name'] ?? $user['username'] ?? '';
            TransactionManager::run('USER_RESTORE', function ($db) use ($id, $userName) {
                $this->users->update($id, ['deleted_at' => null, 'is_active' => 1]);
                AuditLogger::userRestored($id, $userName);
                ActivityLogger::log('USER_RESTORE', 'users', 'Restored user "' . $userName . '" (ID: ' . $id . ').');
            }, 'users', $id);
        } catch (\Throwable $e) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Failed to restore user: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/users'))->with('success', 'User restored.');
    }
}
