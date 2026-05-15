<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\AdminPrivilege;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Mobile app home / dashboard summary (AJESCHAT).
 */
class Mobile extends BaseController
{
    public function summary(): ResponseInterface
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId < 1) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Please log in first.',
            ]);
        }

        $users = new UserModel();
        $user  = $users->find($userId);
        if (! $user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'User not found.',
            ]);
        }

        $role          = strtoupper(trim((string) ($user['role'] ?? 'STUDENT')));
        $assignedPriv  = $user['admin_privileges'] ?? [];

        $unread = 0;
        try {
            $db     = \Config\Database::connect();
            $unread = (int) $db->table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->countAllResults();
        } catch (\Throwable $e) {
            log_message('debug', 'api/mobile/summary unread: ' . $e->getMessage());
        }

        $canManageAnnouncements = AdminPrivilege::canAccess($role, $assignedPriv, 'announcements');
        $teacherSections        = AdminPrivilege::canAccess($role, $assignedPriv, 'teacher_sections');
        $records                = AdminPrivilege::canAccess($role, $assignedPriv, 'records');
        $chatLogs               = AdminPrivilege::canAccess($role, $assignedPriv, 'chat_logs');
        $userManagementRead     = AdminPrivilege::canAccess($role, $assignedPriv, 'user_management:read');
        $sectionsRead           = AdminPrivilege::canAccess($role, $assignedPriv, 'sections:read');
        $systemSettings         = AdminPrivilege::canAccess($role, $assignedPriv, 'system_settings');
        $chatbotManagement      = AdminPrivilege::canAccess($role, $assignedPriv, 'chatbot_management');
        $backupRestore          = AdminPrivilege::canAccess($role, $assignedPriv, 'backup_restore');
        $securityLogs           = AdminPrivilege::canAccess($role, $assignedPriv, 'security_logs');

        $variant     = $this->dashboardVariant($role);
        $displayName = (string) ($user['name'] ?? $user['username'] ?? 'User');

        $data = [
            'user_id'                   => $userId,
            'role'                      => $role,
            'name'                      => $displayName,
            'unread_notifications'      => $unread,
            'can_manage_announcements'  => $canManageAnnouncements,
            'teacher_sections'          => $teacherSections,
            'records'                   => $records,
            'chat_logs'                 => $chatLogs,
            'user_management_read'      => $userManagementRead,
            'sections_read'             => $sectionsRead,
            'system_settings'           => $systemSettings,
            'chatbot_management'        => $chatbotManagement,
            'backup_restore'            => $backupRestore,
            'security_logs'             => $securityLogs,
            'dashboard'                 => [
                'variant'       => $variant,
                'welcome_line'  => 'Signed in as ' . $displayName . ' — open News, Chats, or Alerts from the tabs below.',
                'kpis'          => [],
                'recent_announcements' => [],
                'recent_messages'      => [],
                'section_activity'     => [],
                'activity_chart'       => [],
            ],
        ];

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    private function dashboardVariant(string $role): string
    {
        return match ($role) {
            'SUPER_ADMIN', 'ADMIN' => 'admin',
            'PRINCIPAL' => 'leadership',
            'VICE_PRINCIPAL', 'HEAD_TEACHER' => 'leadership',
            'TEACHER' => 'teacher',
            'STUDENT', 'PARENT' => 'student',
            'ANNOUNCER' => 'announcer',
            'GUIDANCE' => 'guidance',
            default => 'student',
        };
    }
}
