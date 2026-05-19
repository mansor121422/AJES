<?php

namespace App\Controllers;

use App\Libraries\ActivityLogger;
use App\Libraries\AuditLogger;
use App\Libraries\DatabaseBackup;
use App\Libraries\IntrusionDetector;
use App\Libraries\SessionTracker;
use App\Libraries\SystemSnapshot;
use App\Libraries\TransactionManager;
use CodeIgniter\HTTP\RedirectResponse;

class SystemAdmin extends BaseController
{
    public function settings(): string
    {
        $data = $this->pageData('settings');
        $data['settings_data'] = SystemSnapshot::collect();

        return view('SystemAdmin/index', $data);
    }

    public function chatbot(): string
    {
        $data = $this->pageData('chatbot');
        $ai = config('AIChat');
        $data['chatbot_data'] = [
            'enabled' => (bool) ($ai->enabled ?? false),
            'auto_reply' => (bool) ($ai->autoReply ?? false),
            'provider_model' => (string) ($ai->model ?? ''),
            'api_configured' => method_exists($ai, 'isConfigured') ? (bool) $ai->isConfigured() : false,
            'ai_name' => (string) ($ai->aiName ?? 'AjesAI'),
            'trigger_roles' => (array) ($ai->triggerRoles ?? []),
            'receiver_roles' => (array) ($ai->receiverRoles ?? []),
            'mentions' => (array) ($ai->triggerMentions ?? []),
            'max_response_length' => (int) ($ai->maxResponseLength ?? 500),
            'temperature' => (float) ($ai->temperature ?? 0.7),
        ];
        return view('SystemAdmin/index', $data);
    }

    public function backup(): string
    {
        $data = $this->pageData('backup');
        $items = DatabaseBackup::listBackups(20);

        $data['backup_data'] = [
            'backup_dir' => DatabaseBackup::backupDirectory(),
            'file_count' => count($items),
            'files'      => $items,
            'db_name'    => (string) (config('Database')->default['database'] ?? ''),
        ];

        return view('SystemAdmin/index', $data);
    }

    public function createBackup(): RedirectResponse
    {
        $result = DatabaseBackup::createBackup();
        $userId = (int) (session()->get('user_id') ?? 0);

        if ($result['ok']) {
            AuditLogger::log('BACKUP_CREATE', $userId, 'backups', null, 'Created DB backup: ' . ($result['file'] ?? ''));
            ActivityLogger::log('BACKUP_CREATE', 'sysadmin', 'Database backup: ' . ($result['file'] ?? ''));

            return redirect()->to(base_url('sysadmin/backup'))->with('success', $result['message'] . ' File: ' . ($result['file'] ?? ''));
        }

        return redirect()->to(base_url('sysadmin/backup'))->with('error', $result['message']);
    }

    public function restoreBackup(): RedirectResponse
    {
        $filename  = trim((string) $this->request->getPost('dump_file'));
        $confirm   = trim((string) $this->request->getPost('confirm'));
        $preBackup = (bool) $this->request->getPost('pre_backup');

        if ($confirm !== 'YES') {
            return redirect()->to(base_url('sysadmin/backup'))->with('error', 'Restore cancelled. Type YES to confirm.');
        }

        if ($filename === '') {
            return redirect()->to(base_url('sysadmin/backup'))->with('error', 'Select a backup file to restore.');
        }

        $result = DatabaseBackup::restoreBackup($filename, $preBackup);
        $userId = (int) (session()->get('user_id') ?? 0);

        if ($result['ok']) {
            AuditLogger::log('BACKUP_RESTORE', $userId, 'backups', null, 'Restored DB from: ' . $filename);
            ActivityLogger::log('BACKUP_RESTORE', 'sysadmin', 'Database restore: ' . $filename);

            return redirect()->to(base_url('sysadmin/backup'))->with('success', $result['message']);
        }

        return redirect()->to(base_url('sysadmin/backup'))->with('error', $result['message']);
    }

    public function downloadBackup(string $file)
    {
        $safe = DatabaseBackup::safeFilename($file);
        if ($safe === null) {
            return redirect()->to(base_url('sysadmin/backup'))->with('error', 'Invalid backup file.');
        }

        $path = DatabaseBackup::backupDirectory() . DIRECTORY_SEPARATOR . $safe;
        if (! is_file($path)) {
            return redirect()->to(base_url('sysadmin/backup'))->with('error', 'Backup file not found.');
        }

        return $this->response->download($path, null)->setFileName($safe);
    }

    public function securityLogs(): string
    {
        $data = $this->pageData('security-logs');
        $logDir = WRITEPATH . 'logs';
        $items = [];
        $errorCount = 0;
        $warningCount = 0;

        $files = glob($logDir . DIRECTORY_SEPARATOR . 'log-*');
        if (is_array($files)) {
            rsort($files);
            foreach (array_slice($files, 0, 20) as $path) {
                if (! is_file($path)) {
                    continue;
                }
                $content = @file_get_contents($path);
                $content = is_string($content) ? $content : '';
                $errorCount += substr_count($content, 'ERROR -');
                $warningCount += substr_count($content, 'WARNING -');
                $items[] = [
                    'name' => basename($path),
                    'size' => $this->formatBytes((int) filesize($path)),
                    'modified_at' => date('Y-m-d H:i:s', (int) filemtime($path)),
                ];
            }
        }

        $data['security_data'] = [
            'log_dir' => $logDir,
            'files' => $items,
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'audit_logs' => AuditLogger::recent(50),
        ];
        return view('SystemAdmin/index', $data);
    }

    public function activeSessions(): string
    {
        $data = $this->pageData('active-sessions');
        $data['sessions'] = SessionTracker::activeSessions();
        return view('SystemAdmin/index', $data);
    }

    public function auditReport(): string
    {
        $data = $this->pageData('audit-report');
        $days = (int) ($this->request->getGet('days') ?? 7);
        if ($days < 1) {
            $days = 7;
        }
        $data['audit_report'] = IntrusionDetector::auditReport($days);
        return view('SystemAdmin/index', $data);
    }

    public function activityLogs(): string
    {
        $data = $this->pageData('activity-logs');
        $data['activity_logs'] = ActivityLogger::recent(100);
        return view('SystemAdmin/index', $data);
    }

    public function transactionLogs(): string
    {
        $data = $this->pageData('transaction-logs');
        $data['transaction_logs'] = TransactionManager::recent(50);
        return view('SystemAdmin/index', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(string $tab): array
    {
        $logDir = WRITEPATH . 'logs';
        $logCount = 0;
        if (is_dir($logDir)) {
            $files = glob($logDir . DIRECTORY_SEPARATOR . 'log-*');
            $logCount = is_array($files) ? count($files) : 0;
        }

        return [
            'role' => session()->get('role') ?? 'ADMIN',
            'name' => session()->get('name') ?? 'System Administrator',
            'tab' => $tab,
            'log_count' => $logCount,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1024;
        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'GB') {
                return number_format($value, 2) . ' ' . $unit;
            }
            $value /= 1024;
        }
        return (string) $bytes;
    }
}
