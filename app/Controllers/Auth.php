<?php

namespace App\Controllers;

use App\Libraries\LoginLockout;
use App\Libraries\AdminPrivilege;
use App\Libraries\AuditLogger;
use App\Libraries\JwtAuth;
use App\Libraries\PasswordReuseGuard;
use App\Libraries\SecureHash;
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

        $lockedFor = LoginLockout::lockedRemainingSeconds($user['locked_until'] ?? null);
        if ($lockedFor !== null) {
            return redirect()->back()->withInput()
                ->with('error', LoginLockout::lockoutMessage($lockedFor))
                ->with('login_lockout_seconds', $lockedFor);
        }

        if (! password_verify($password, $user['password_hash'])) {
            AuditLogger::loginFailed($login);
            $patch = LoginLockout::fieldsAfterFailedPassword((int) ($user['failed_attempts'] ?? 0));
            $this->users->update($user['id'], $patch);

            $msg     = 'Invalid credentials.';
            $lockSec = null;
            if ($patch['locked_until'] !== null) {
                $sec = LoginLockout::lockedRemainingSeconds($patch['locked_until']);
                if ($sec !== null) {
                    $msg     = LoginLockout::lockoutMessage($sec);
                    $lockSec = $sec;
                }
            }

            $response = redirect()->back()->withInput()->with('error', $msg);
            if ($lockSec !== null) {
                $response = $response->with('login_lockout_seconds', $lockSec);
            }

            return $response;
        }

        $this->users->update($user['id'], [
            'failed_attempts' => 0,
            'last_failed_at'  => null,
            'locked_until'    => null,
        ]);

        // Transparent rehash: upgrade bcrypt hashes to Argon2id when available.
        if (SecureHash::needsRehash($user['password_hash'])) {
            $this->users->update($user['id'], [
                'password_hash' => SecureHash::make($password),
            ]);
        }

        // ── MFA check ──
        if (! empty($user['mfa_enabled'])) {
            $session->regenerate();
            $session->set('mfa_pending_user_id', (int) $user['id']);
            $this->generateAndSendMfaCode($user);
            return redirect()->to(base_url('auth/mfa'));
        }

        return $this->completeLogin($user, $session);
    }

    /**
     * Generate a 6-digit OTP, store it, and send via email (dev fallback shows code on screen).
     */
    private function generateAndSendMfaCode(array $user): void
    {
        $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes

        $this->users->update($user['id'], [
            'mfa_code'       => $code,
            'mfa_expires_at' => $expires,
        ]);

        $email = $user['email'] ?? '';
        if ($email === '') {
            session()->setFlashdata('dev_mfa_code', $code);
            return;
        }

        $logoUrl = rtrim(config('App')->baseURL, '/') . 'public/assets/images/ajes-logo.png';
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
            . '<body style="margin:0;padding:0;font-family:\'Segoe UI\',system-ui,sans-serif;background:#e8f5e9;">'
            . '<table width="100%" cellspacing="0" cellpadding="0" style="background:#e8f5e9;padding:32px 16px;"><tr><td align="center">'
            . '<table cellspacing="0" cellpadding="0" style="max-width:420px;width:100%;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(27,94,32,0.15);overflow:hidden;">'
            . '<tr><td style="padding:28px 24px;text-align:center;background:linear-gradient(135deg,#2e7d32 0%,#1b5e20 100%);">'
            . '<img src="' . esc($logoUrl) . '" alt="AJES" style="max-width:80px;height:auto;">'
            . '<p style="margin:10px 0 0;color:#fff;font-size:17px;font-weight:700;">AJES CRIER</p>'
            . '<p style="margin:4px 0 0;color:#c8e6c9;font-size:12px;">Two-Factor Authentication</p>'
            . '</td></tr>'
            . '<tr><td style="padding:28px 24px;text-align:center;">'
            . '<p style="margin:0 0 8px;color:#333;font-size:14px;">Your verification code is:</p>'
            . '<p style="margin:0 0 16px;font-size:2.2rem;font-weight:800;letter-spacing:0.35em;color:#1b5e20;">' . esc($code) . '</p>'
            . '<p style="margin:0;color:#888;font-size:12px;">This code expires in 5 minutes.</p>'
            . '</td></tr></table></td></tr></table></body></html>';

        try {
            $emailService = service('email');
            $from = (string) env('EMAIL_FROM', (string) env('SMTP_USER', ''));
            if ($from !== '') {
                $emailService->setFrom($from, (string) env('EMAIL_FROM_NAME', 'AJES CRIER'));
            }
            $emailService->setTo($email);
            $emailService->setSubject('AJES Login Verification Code');
            $emailService->setMailType('html');
            $emailService->setMessage($html);
            $sent = $emailService->send();

            if (! $sent) {
                session()->setFlashdata('dev_mfa_code', $code);
            }
        } catch (\Throwable $e) {
            log_message('error', 'MFA email failed: ' . $e->getMessage());
            session()->setFlashdata('dev_mfa_code', $code);
        }
    }

    /**
     * Show the MFA verification form.
     */
    public function showMfa(): string|ResponseInterface
    {
        if (! session()->get('mfa_pending_user_id')) {
            return redirect()->to(base_url('/'));
        }

        return view('Auth/mfa_verify');
    }

    /**
     * Verify the submitted MFA code.
     */
    public function verifyMfa(): RedirectResponse
    {
        $session = session();
        $userId  = (int) $session->get('mfa_pending_user_id');

        if (! $userId) {
            return redirect()->to(base_url('/'))->with('error', 'Session expired. Please log in again.');
        }

        $code = trim((string) $this->request->getPost('mfa_code'));
        if ($code === '' || strlen($code) !== 6) {
            return redirect()->to(base_url('auth/mfa'))->with('error', 'Please enter the 6-digit code.');
        }

        $user = $this->users->find($userId);
        if (! $user) {
            $session->remove('mfa_pending_user_id');
            return redirect()->to(base_url('/'))->with('error', 'User not found.');
        }

        if (($user['mfa_code'] ?? '') !== $code) {
            AuditLogger::log('MFA_FAILED', $userId, 'users', $userId, 'Invalid MFA code entered.');
            return redirect()->to(base_url('auth/mfa'))->with('error', 'Invalid verification code.');
        }

        if (isset($user['mfa_expires_at']) && $user['mfa_expires_at'] < date('Y-m-d H:i:s')) {
            return redirect()->to(base_url('auth/mfa'))->with('error', 'Code expired. Click "Resend code" to get a new one.');
        }

        $this->users->update($userId, [
            'mfa_code'       => null,
            'mfa_expires_at' => null,
        ]);

        $session->remove('mfa_pending_user_id');
        AuditLogger::log('MFA_SUCCESS', $userId, 'users', $userId, 'MFA verification passed.');

        return $this->completeLogin($user, $session);
    }

    /**
     * Resend the MFA code.
     */
    public function resendMfa(): RedirectResponse
    {
        $userId = (int) session()->get('mfa_pending_user_id');
        if (! $userId) {
            return redirect()->to(base_url('/'));
        }

        $user = $this->users->find($userId);
        if ($user) {
            $this->generateAndSendMfaCode($user);
        }

        return redirect()->to(base_url('auth/mfa'))->with('success', 'A new code has been sent.');
    }

    /**
     * Finalize login: set session, presence, audit log, redirect.
     */
    private function completeLogin(array $user, \CodeIgniter\Session\Session $session): RedirectResponse
    {
        $session->regenerate();

        $session->set([
            'user_id' => $user['id'],
            'name'    => $user['name'],
            'role'    => $user['role'],
            'feature_privileges' => AdminPrivilege::effectiveForRole((string) ($user['role'] ?? ''), $user['admin_privileges'] ?? []),
        ]);

        AuditLogger::loginSuccess((int) $user['id'], $user['username'] ?? '');

        try {
            $this->users->update($user['id'], [
                'is_online'   => 1,
                'last_seen_at'=> date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Presence update failed on login for user_id=' . (int) $user['id'] . ': ' . $e->getMessage());
        }

        return redirect()->to($this->redirectForRole($user['role'], $user['admin_privileges'] ?? null));
    }

    // ── JWT API Login ──

    /**
     * POST /auth/api-login — returns a JWT for stateless API access.
     */
    public function apiLogin(): ResponseInterface
    {
        $request = service('request');
        $login   = trim((string) $request->getPost('username'));
        $password = (string) $request->getPost('password');

        if ($login === '' || $password === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Username and password are required.',
            ]);
        }

        $user = $this->users->findByLogin($login);
        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ]);
        }

        if (! empty($user['locked_until']) && $user['locked_until'] > date('Y-m-d H:i:s')) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Account temporarily locked.',
            ]);
        }

        AuditLogger::loginSuccess((int) $user['id'], $user['username'] ?? $login);

        $token = JwtAuth::encode($user);

        return $this->response->setJSON([
            'status' => 'success',
            'token'  => $token,
            'user'   => [
                'id'       => (int) $user['id'],
                'name'     => $user['name'] ?? '',
                'username' => $user['username'] ?? '',
                'role'     => $user['role'] ?? '',
            ],
        ]);
    }

    public function logout(): RedirectResponse
    {
        $session = session();

        $userId = (int) $session->get('user_id');
        if ($userId > 0) {
            AuditLogger::logout($userId);
            try {
                $this->users->update($userId, [
                    'is_online'   => 0,
                    'last_seen_at'=> date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Presence update failed on logout for user_id=' . $userId . ': ' . $e->getMessage());
            }
        }
        $session->destroy();

        return redirect()->to(base_url('/'));
    }

    public function showForgotPassword(): RedirectResponse
    {
        return redirect()->to($this->forgotPasswordRedirect());
    }

    protected function forgotPasswordRedirect(): string
    {
        return site_url('/') . '?forgot=1';
    }

    public function sendResetLink(): RedirectResponse
    {
        $email   = trim((string) service('request')->getPost('email'));
        $session = session();

        if ($email === '') {
            return redirect()->to($this->forgotPasswordRedirect())->withInput()->with('error', 'Email is required.');
        }

        $user = $this->users->where('email', $email)->first();

        if (! $user) {
            return redirect()->to($this->forgotPasswordRedirect())->withInput()->with('error', 'If that email exists, a reset link has been sent.');
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
        $logoUrl    = rtrim(config('App')->baseURL, '/') . 'public/assets/images/ajes-logo.png';

        $htmlMessage = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="margin:0; padding:0; font-family: \'Segoe UI\', system-ui, sans-serif; background:#e8f5e9;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#e8f5e9; padding: 32px 16px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="max-width: 420px; width:100%; background:#fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(27,94,32,0.15); overflow: hidden;">'
            . '<tr><td style="padding: 32px 24px; text-align: center; background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);">'
            . '<img src="' . esc($logoUrl) . '" alt="AJES" style="max-width: 100px; height: auto; display: inline-block;" />'
            . '<p style="margin: 12px 0 0 0; color: #fff; font-size: 18px; font-weight: 700;">AJES CRIER</p>'
            . '<p style="margin: 4px 0 0 0; color: #c8e6c9; font-size: 13px;">Password Reset</p>'
            . '</td></tr>'
            . '<tr><td style="padding: 28px 24px;">'
            . '<p style="margin: 0 0 16px 0; color: #333; font-size: 15px; line-height: 1.5;">Hello,</p>'
            . '<p style="margin: 0 0 20px 0; color: #555; font-size: 14px; line-height: 1.5;">You requested to reset your password. Click the button below to set a new password. This link expires in 1 hour.</p>'
            . '<p style="margin: 0 0 24px 0; text-align: center;">'
            . '<a href="' . esc($resetLink) . '" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%); color: #fff; text-decoration: none; font-weight: 600; font-size: 15px; border-radius: 10px; box-shadow: 0 2px 8px rgba(27,94,32,0.3);">Reset Password</a>'
            . '</p>'
            . '<p style="margin: 0; color: #888; font-size: 12px; line-height: 1.5;">If you didn\'t request this, you can ignore this email.</p>'
            . '<p style="margin: 16px 0 0 0; color: #999; font-size: 11px;">If the button doesn\'t work, copy and paste this link into your browser:<br/><a href="' . esc($resetLink) . '" style="color: #2e7d32; word-break: break-all;">' . esc($resetLink) . '</a></p>'
            . '</td></tr>'
            . '<tr><td style="padding: 16px 24px; background: #f1f8e9; border-top: 1px solid #c8e6c9; text-align: center;">'
            . '<p style="margin: 0; color: #558b2f; font-size: 12px;">Ano Jay Elementary School · AJES Philippines</p>'
            . '</td></tr></table></td></tr></table></body></html>';

        $emailService = service('email');
        $fromAddress = (string) env('EMAIL_FROM', (string) env('SMTP_USER', ''));
        $fromName = (string) env('EMAIL_FROM_NAME', 'AJES CRIER');
        if ($fromAddress !== '') {
            $emailService->setFrom($fromAddress, $fromName);
        }
        $emailService->setTo($email);
        $emailService->setSubject('AJES Password Reset');
        $emailService->setMailType('html');
        $emailService->setMessage($htmlMessage);
        $sent = $emailService->send();

        if (! $sent) {
            $debug = strip_tags($emailService->printDebugger([]));
            log_message('error', 'Forgot password email failed: ' . $debug);

            if (ENVIRONMENT === 'development') {
                session()->setFlashdata('success', 'Email send failed on this local setup. Use the temporary reset link below.');
                session()->setFlashdata('dev_reset_link', $resetLink);
                return redirect()->to($this->forgotPasswordRedirect())->withInput();
            }

            return redirect()->to($this->forgotPasswordRedirect())->withInput()->with('error', 'Email could not be sent. Configure .env with SMTP_HOST, SMTP_USER, SMTP_PASS, and EMAIL_FROM (or let EMAIL_FROM fallback to SMTP_USER). Check writable/logs for detailed SMTP errors.');
        }

        $session->setFlashdata('success', 'If that email exists, a reset link has been sent.');

        return redirect()->to($this->forgotPasswordRedirect());
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

        $acct = $this->users->where('email', $row['email'])->first();
        $hist = $acct ? PasswordReuseGuard::historyFromDb($acct['password_history'] ?? null) : [];
        if ($acct && PasswordReuseGuard::isPasswordReused($password, (string) ($acct['password_hash'] ?? ''), $hist)) {
            return redirect()->back()->withInput()->with('error', 'You cannot reuse your current password or a recently used one. Please choose a different password.');
        }

        $hash = SecureHash::make($password);

        $set = [
            'password_hash'   => $hash,
            'failed_attempts' => 0,
            'last_failed_at'  => null,
            'locked_until'    => null,
        ];
        if ($acct) {
            $set['password_history'] = PasswordReuseGuard::appendPreviousHash((string) ($acct['password_hash'] ?? ''), $hist);
        }

        $this->users->where('email', $row['email'])->set($set)->update();

        if ($acct) {
            AuditLogger::passwordReset((int) $acct['id']);
        }

        $db->table('password_resets')->where('email', $row['email'])->delete();

        session()->destroy();
        return redirect()->to('/?password_changed=1');
    }

    protected function redirectForRole(string $role, mixed $adminPrivileges = null): string
    {
        $granted = AdminPrivilege::normalize($adminPrivileges);
        if ($granted !== []) {
            if ($role === 'ADMIN' && in_array('user_management', $granted, true)) {
                return base_url('admin/users');
            }
            if (in_array('dashboard', $granted, true)) {
                return match ($role) {
                    'SUPER_ADMIN' => base_url('dashboard/admin'),
                    'ADMIN'      => base_url('dashboard/admin'),
                    'PRINCIPAL'  => base_url('dashboard/principal'),
                    'VICE_PRINCIPAL', 'HEAD_TEACHER' => base_url('dashboard/vice-principal'),
                    'ANNOUNCER'  => base_url('dashboard/announcer'),
                    'TEACHER'    => base_url('dashboard/teacher'),
                    'GUIDANCE'   => base_url('dashboard/guidance'),
                    'STUDENT'    => base_url('dashboard/student'),
                    default      => base_url('dashboard'),
                };
            }
            if ($role === 'ADMIN' && in_array('sections', $granted, true)) {
                return base_url('admin/sections');
            }
            if ($role === 'TEACHER' && in_array('teacher_sections', $granted, true)) {
                return base_url('teacher/sections');
            }
            if (in_array('announcements', $granted, true)) {
                return base_url('announcements');
            }
            if (in_array('records', $granted, true)) {
                return base_url('records');
            }
            if ($role === 'ADMIN' && in_array('chat_logs', $granted, true)) {
                return base_url('admin/chat-logs');
            }
            return base_url('chat');
        }

        return match ($role) {
            'SUPER_ADMIN' => base_url('dashboard/admin'),
            'ADMIN'      => base_url('dashboard/admin'),
            'PRINCIPAL'  => base_url('dashboard/principal'),
            'VICE_PRINCIPAL', 'HEAD_TEACHER' => base_url('dashboard/vice-principal'),
            'ANNOUNCER'  => base_url('dashboard/announcer'),
            'TEACHER'    => base_url('dashboard/teacher'),
            'GUIDANCE'   => base_url('dashboard/guidance'),
            'STUDENT'    => base_url('dashboard/student'),
            'PARENT'     => base_url('dashboard/student'),
            default      => base_url('dashboard'),
        };
    }
}
