<?php

namespace App\Controllers;

use App\Libraries\AuditLogger;

class SystemAdmin extends BaseController
{
    public function settings(): string
    {
        $data = $this->pageData('settings');
        $app = config('App');
        $data['settings_data'] = [
            'base_url' => (string) ($app->baseURL ?? ''),
            'index_page' => (string) ($app->indexPage ?? ''),
            'default_locale' => (string) ($app->defaultLocale ?? 'en'),
            'timezone' => (string) ($app->appTimezone ?? 'UTC'),
            'secure_requests' => (bool) ($app->forceGlobalSecureRequests ?? false),
            'presence_timeout' => (int) ($app->presenceTimeoutSeconds ?? 300),
            'presence_heartbeat' => (int) ($app->presenceHeartbeatSeconds ?? 60),
        ];
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
        $backupDir = WRITEPATH . 'backups';
        if (! is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        $items = [];
        $files = glob($backupDir . DIRECTORY_SEPARATOR . '*');
        if (is_array($files)) {
            rsort($files);
            foreach (array_slice($files, 0, 15) as $path) {
                if (! is_file($path)) {
                    continue;
                }
                $items[] = [
                    'name' => basename($path),
                    'size' => $this->formatBytes((int) filesize($path)),
                    'modified_at' => date('Y-m-d H:i:s', (int) filemtime($path)),
                ];
            }
        }

        $data['backup_data'] = [
            'backup_dir' => $backupDir,
            'file_count' => count($items),
            'files' => $items,
            'commands' => [
                'backup_ps'       => 'powershell -ExecutionPolicy Bypass -File scripts/backup-db.ps1',
                'backup_sh'       => './scripts/backup-db.sh',
                'restore_ps'      => 'powershell -ExecutionPolicy Bypass -File scripts/restore-db.ps1 -DumpFile .\\writable\\backups\\ajesdb_TIMESTAMP.sql',
                'restore_ps_safe' => 'powershell -ExecutionPolicy Bypass -File scripts/restore-db.ps1 -PreBackup -DumpFile .\\writable\\backups\\ajesdb_TIMESTAMP.sql',
                'restore_sh'      => './scripts/restore-db.sh ./writable/backups/ajesdb_TIMESTAMP.sql',
                'docs'            => 'See scripts/README.md; Lab write-up: docs/LAB4_SYSTEM_DEVELOPMENT.md',
            ],
        ];
        return view('SystemAdmin/index', $data);
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
