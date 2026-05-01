<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Profile extends BaseController
{
    protected UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
        helper(['form', 'url']);
    }

    public function index(): string|RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return redirect()->to(base_url('auth/login'));
        }

        $user = $this->users->find($userId);
        if (! $user) {
            return redirect()->to(base_url('auth/login'));
        }

        return view('Profile/index', [
            'role' => strtoupper((string) ($user['role'] ?? session()->get('role') ?? 'STUDENT')),
            'name' => (string) ($user['name'] ?? session()->get('name') ?? 'User'),
            'user' => $user,
        ]);
    }

    public function update(): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return redirect()->to(base_url('auth/login'));
        }

        $user = $this->users->find($userId);
        if (! $user) {
            return redirect()->to(base_url('auth/login'));
        }

        // Allow deleting only the current profile photo without editing other fields.
        if ((string) $this->request->getPost('remove_photo') === '1') {
            $oldPhoto = (string) ($user['profile_photo'] ?? '');
            if ($oldPhoto !== '' && str_starts_with($oldPhoto, 'uploads/avatars/')) {
                $oldPath = FCPATH . $oldPhoto;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $this->users->update($userId, ['profile_photo' => null]);
            return redirect()->to(base_url('profile'))->with('success', 'Profile photo removed.');
        }

        $name = trim((string) $this->request->getPost('name'));
        $email = trim((string) $this->request->getPost('email'));
        $contactNumber = trim((string) $this->request->getPost('contact_number'));
        $bio = trim((string) $this->request->getPost('bio'));

        if ($name === '' || $email === '') {
            return redirect()->back()->withInput()->with('error', 'Name and email are required.');
        }

        $updates = [
            'name' => $name,
            'email' => $email,
            'contact_number' => $contactNumber !== '' ? $contactNumber : null,
            'bio' => $bio !== '' ? $bio : null,
        ];

        $password = (string) $this->request->getPost('new_password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');
        $oldPassword = (string) $this->request->getPost('old_password');
        if ($password !== '' || $confirmPassword !== '') {
            if ($oldPassword === '') {
                return redirect()->back()->withInput()->with('error', 'Old password is required to change password.');
            }
            if (! password_verify($oldPassword, (string) ($user['password_hash'] ?? ''))) {
                return redirect()->back()->withInput()->with('error', 'Old password is incorrect.');
            }
            if (strlen($password) < 8) {
                return redirect()->back()->withInput()->with('error', 'New password must be at least 8 characters.');
            }
            if ($password !== $confirmPassword) {
                return redirect()->back()->withInput()->with('error', 'Password confirmation does not match.');
            }
            $updates['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $existing = $this->users
            ->where('email', $email)
            ->where('id !=', $userId)
            ->first();
        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Email is already used by another account.');
        }

        $photo = $this->request->getFile('profile_photo');
        if ($photo && $photo->isValid() && ! $photo->hasMoved()) {
            $maxSizeBytes = 2 * 1024 * 1024;
            if ($photo->getSize() > $maxSizeBytes) {
                return redirect()->back()->withInput()->with('error', 'Profile photo must be 2MB or less.');
            }

            $mime = (string) $photo->getMimeType();
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (! in_array($mime, $allowed, true)) {
                return redirect()->back()->withInput()->with('error', 'Profile photo must be JPG, PNG, or WEBP.');
            }

            $targetDir = FCPATH . 'uploads/avatars';
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }

            $newName = $photo->getRandomName();
            $photo->move($targetDir, $newName);

            $oldPhoto = (string) ($user['profile_photo'] ?? '');
            if ($oldPhoto !== '' && str_starts_with($oldPhoto, 'uploads/avatars/')) {
                $oldPath = FCPATH . $oldPhoto;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $updates['profile_photo'] = 'uploads/avatars/' . $newName;
        }

        $this->users->update($userId, $updates);

        session()->set('name', $name);

        return redirect()->to(base_url('profile'))->with('success', 'Profile updated successfully.');
    }
}

