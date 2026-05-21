x   <?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Profile extends BaseController
{
    protected UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
        helper(['url']);
    }

    public function show(): ResponseInterface
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId < 1) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Please log in first.',
            ]);
        }

        $user = $this->users->find($userId);
        if (! $user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'User not found.',
            ]);
        }

        $photoRel = trim((string) ($user['profile_photo'] ?? ''));

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'id' => (int) $user['id'],
                'username' => (string) ($user['username'] ?? ''),
                'name' => (string) ($user['name'] ?? ''),
                'email' => (string) ($user['email'] ?? ''),
                'role' => (string) ($user['role'] ?? ''),
                'contact_number' => $user['contact_number'] ?? null,
                'bio' => $user['bio'] ?? null,
                'profile_photo_url' => $photoRel !== '' ? base_url($photoRel) : null,
            ],
        ]);
    }

    public function update(): ResponseInterface
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId < 1) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Please log in first.',
            ]);
        }

        $user = $this->users->find($userId);
        if (! $user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'User not found.',
            ]);
        }

        $contentType = strtolower($this->request->getHeaderLine('Content-Type'));
        $isMultipart = str_contains($contentType, 'multipart/form-data');

        if ($isMultipart) {
            $name = trim((string) $this->request->getPost('name'));
            $email = trim((string) $this->request->getPost('email'));
            $contact = trim((string) $this->request->getPost('contact_number'));
            $bio = trim((string) $this->request->getPost('bio'));
            $newPassword = trim((string) $this->request->getPost('new_password'));
            $confirmPassword = trim((string) $this->request->getPost('confirm_password'));
            $oldPassword = (string) $this->request->getPost('old_password');
            $removePhoto = in_array((string) $this->request->getPost('remove_photo'), ['1', 'true', 'yes'], true);
            $photo = $this->request->getFile('profile_photo');
        } else {
            $payload = $this->request->getJSON(true);
            if (! is_array($payload)) {
                $payload = [
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'contact_number' => $this->request->getPost('contact_number'),
                    'bio' => $this->request->getPost('bio'),
                    'old_password' => $this->request->getPost('old_password'),
                    'new_password' => $this->request->getPost('new_password'),
                    'confirm_password' => $this->request->getPost('confirm_password'),
                    'remove_photo' => $this->request->getPost('remove_photo'),
                ];
            }

            $name = trim((string) ($payload['name'] ?? ''));
            $email = trim((string) ($payload['email'] ?? ''));
            $contact = trim((string) ($payload['contact_number'] ?? ''));
            $bio = trim((string) ($payload['bio'] ?? ''));
            $newPassword = trim((string) ($payload['new_password'] ?? ''));
            $confirmPassword = trim((string) ($payload['confirm_password'] ?? ''));
            $oldPassword = (string) ($payload['old_password'] ?? '');
            $removePhoto = filter_var($payload['remove_photo'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $photo = null;
        }

        if ($name === '' || $email === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Name and email are required.',
            ]);
        }

        $existing = $this->users
            ->where('email', $email)
            ->where('id !=', $userId)
            ->first();
        if ($existing) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => 'error',
                'message' => 'Email is already used by another account.',
            ]);
        }

        $updates = [
            'name' => $name,
            'email' => $email,
            'contact_number' => $contact !== '' ? $contact : null,
            'bio' => $bio !== '' ? $bio : null,
        ];

        if ($newPassword !== '' || $confirmPassword !== '') {
            if ($oldPassword === '') {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'Old password is required to change password.',
                ]);
            }
            if (! password_verify($oldPassword, (string) ($user['password_hash'] ?? ''))) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'Old password is incorrect.',
                ]);
            }
            if (strlen($newPassword) < 8) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'New password must be at least 8 characters.',
                ]);
            }
            if ($newPassword !== $confirmPassword) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'Password confirmation does not match.',
                ]);
            }
            $updates['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if ($removePhoto) {
            $oldPhoto = (string) ($user['profile_photo'] ?? '');
            if ($oldPhoto !== '' && str_starts_with($oldPhoto, 'uploads/avatars/')) {
                $oldPath = FCPATH . $oldPhoto;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $updates['profile_photo'] = null;
        }

        if ($isMultipart && $photo && $photo->isValid() && ! $photo->hasMoved()) {
            $maxSizeBytes = 2 * 1024 * 1024;
            if ($photo->getSize() > $maxSizeBytes) {
                return $this->response->setStatusCode(413)->setJSON([
                    'status' => 'error',
                    'message' => 'Profile photo must be 2MB or less.',
                ]);
            }

            $mime = (string) $photo->getMimeType();
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (! in_array($mime, $allowed, true)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'Profile photo must be JPG, PNG, or WEBP.',
                ]);
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

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
        ]);
    }
}
