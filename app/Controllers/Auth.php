<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends BaseController
{
    protected UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
        helper(['url', 'form']);
    }

    public function login(): RedirectResponse
    {
        $request = service('request');
        $session = session();

        $login    = trim((string) $request->getPost('username'));
        $password = (string) $request->getPost('password');

        if ($login === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Username and password are required.');
        }

        $user = $this->users->findByLogin($login);

        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials.');
        }

        if (($user['failed_attempts'] ?? 0) >= 5) {
            return redirect()->back()->with('error', 'Account locked. Please contact administrator.');
        }

        if (! password_verify($password, $user['password_hash'])) {
            $this->users->update($user['id'], [
                'failed_attempts' => ($user['failed_attempts'] ?? 0) + 1,
                'last_failed_at'  => date('Y-m-d H:i:s'),
            ]);

            return redirect()->back()->withInput()->with('error', 'Invalid credentials.');
        }

        $this->users->update($user['id'], [
            'failed_attempts' => 0,
            'last_failed_at'  => null,
        ]);

        $session->regenerate();

        $session->set([
            'user_id' => $user['id'],
            'name'    => $user['name'],
            'role'    => $user['role'],
        ]);

        return redirect()->to($this->redirectForRole($user['role']));
    }

    public function logout(): RedirectResponse
    {
        $session = session();
        $session->destroy();

        return redirect()->to('/');
    }

    public function showForgotPassword(): string
    {
        return view('Auth/forgot_password');
    }

    public function sendResetLink(): RedirectResponse
    {
        $email   = trim((string) service('request')->getPost('email'));
        $session = session();

        if ($email === '') {
            return redirect()->back()->withInput()->with('error', 'Email is required.');
        }

        $user = $this->users->where('email', $email)->first();

        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'If that email exists, a reset link has been sent.');
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $db = db_connect();
        $db->table('password_resets')->insert([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => $expires,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $resetLink = base_url('auth/reset-password/' . $token);

        $emailService = service('email');
        $emailService->setTo($email);
        $emailService->setSubject('AJES Password Reset');
        $emailService->setMessage("To reset your password, click this link:\n\n" . $resetLink);
        $emailService->send();

        $session->setFlashdata('success', 'If that email exists, a reset link has been sent.');

        return redirect()->back();
    }

    public function showResetForm(string $token): string|ResponseInterface
    {
        $db  = db_connect();
        $row = $db->table('password_resets')->where('token', $token)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');

        if (! $row || $row['expires_at'] < $now) {
            return redirect()->to('/')->with('error', 'Reset link is invalid or expired.');
        }

        return view('Auth/reset_password', ['token' => $token]);
    }

    public function resetPassword(string $token): RedirectResponse
    {
        $request = service('request');
        $db      = db_connect();

        $row = $db->table('password_resets')->where('token', $token)->get()->getRowArray();

        if (! $row || $row['expires_at'] < date('Y-m-d H:i:s')) {
            return redirect()->to('/')->with('error', 'Reset link is invalid or expired.');
        }

        $password        = (string) $request->getPost('password');
        $passwordConfirm = (string) $request->getPost('password_confirm');

        if ($password === '' || $passwordConfirm === '') {
            return redirect()->back()->withInput()->with('error', 'Both password fields are required.');
        }

        if ($password !== $passwordConfirm) {
            return redirect()->back()->withInput()->with('error', 'Passwords do not match.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->users->where('email', $row['email'])->set([
            'password_hash'   => $hash,
            'failed_attempts' => 0,
            'last_failed_at'  => null,
        ])->update();

        $db->table('password_resets')->where('email', $row['email'])->delete();

        return redirect()->to('/')->with('success', 'Password updated. You can now log in.');
    }

    protected function redirectForRole(string $role): string
    {
        return match ($role) {
            'ADMIN'      => '/dashboard/admin',
            'PRINCIPAL'  => '/dashboard/principal',
            'ANNOUNCER'  => '/dashboard/announcer',
            'TEACHER'    => '/dashboard/teacher',
            'GUIDANCE'   => '/dashboard/guidance',
            'STUDENT'    => '/dashboard/student',
            default      => '/dashboard',
        };
    }
}

