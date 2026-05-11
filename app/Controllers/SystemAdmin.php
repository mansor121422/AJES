<?php

namespace App\Controllers;

class SystemAdmin extends BaseController
{
    public function settings(): string
    {
        return view('SystemAdmin/index', $this->pageData('settings'));
    }

    public function chatbot(): string
    {
        return view('SystemAdmin/index', $this->pageData('chatbot'));
    }

    public function backup(): string
    {
        return view('SystemAdmin/index', $this->pageData('backup'));
    }

    public function securityLogs(): string
    {
        return view('SystemAdmin/index', $this->pageData('security-logs'));
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
            'role' => session()->get('role') ?? 'SUPER_ADMIN',
            'name' => session()->get('name') ?? 'Super Administrator',
            'tab' => $tab,
            'log_count' => $logCount,
        ];
    }
}
