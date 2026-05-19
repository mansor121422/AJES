<?php

namespace App\Libraries;

/**
 * Live AJES system status for the admin System Settings panel.
 */
class SystemSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function collect(): array
    {
        $app     = config('App');
        $dbConf  = config('Database')->default;
        $email   = config('Email');
        $ai      = config('AIChat');
        $request = service('request');

        $configuredBase = rtrim((string) ($app->baseURL ?? ''), '/') . '/';
        $envBase        = trim((string) env('app.baseURL', ''));
        if ($envBase !== '' && ! str_ends_with($envBase, '/')) {
            $envBase .= '/';
        }

        $scheme = $request->isSecure() ? 'https' : 'http';
        $host   = $request->getServer('HTTP_HOST') ?? 'localhost';
        $currentBase = $scheme . '://' . $host . '/';
        $script      = $request->getServer('SCRIPT_NAME') ?? '';
        $basePath    = '';
        if ($script !== '') {
            $dir = str_replace('\\', '/', dirname($script));
            if ($dir !== '/' && $dir !== '.') {
                $basePath = rtrim($dir, '/') . '/';
            }
        }
        $currentAccess = $scheme . '://' . $host . $basePath;

        $androidApi = rtrim($configuredBase, '/') . '/' . ltrim((string) ($app->indexPage ?? 'index.php'), '/');
        if (! str_ends_with($androidApi, '/')) {
            $androidApi .= '/';
        }

        $dbStatus = self::databaseStatus();

        return [
            'environment'        => (string) env('CI_ENVIRONMENT', ENVIRONMENT),
            'php_version'        => PHP_VERSION,
            'ci_version'         => \CodeIgniter\CodeIgniter::CI_VERSION,
            'env_file'           => is_file(ROOTPATH . '.env') ? 'Found (.env)' : 'Missing — using defaults only',
            'configured_base_url'=> $configuredBase,
            'env_base_url'       => $envBase !== '' ? $envBase : '(not set in .env — using App.php default)',
            'current_access_url' => $currentAccess,
            'url_match'          => self::urlsMatch($configuredBase, $currentAccess),
            'index_page'         => (string) ($app->indexPage ?? ''),
            'android_api_url'    => $androidApi,
            'extra_hosts'        => (string) env('app.extraHosts', ''),
            'allowed_hostnames'  => implode(', ', (array) ($app->allowedHostnames ?? [])),
            'default_locale'     => (string) ($app->defaultLocale ?? 'en'),
            'app_timezone'       => (string) ($app->appTimezone ?? 'UTC'),
            'php_timezone'       => date_default_timezone_get(),
            'secure_requests'    => (bool) ($app->forceGlobalSecureRequests ?? false),
            'presence_timeout'   => (int) ($app->presenceTimeoutSeconds ?? 300),
            'presence_heartbeat' => (int) ($app->presenceHeartbeatSeconds ?? 60),
            'encryption_ok'      => self::encryptionConfigured(),
            'smtp_configured'    => trim((string) env('SMTP_HOST', '')) !== '',
            'groq_configured'    => trim((string) env('GROQ_API_KEY', '')) !== '',
            'ai_enabled'         => (bool) ($ai->enabled ?? false),
            'database'           => $dbStatus,
            'writable'           => self::writableStatus(),
            'backup_count'       => count(DatabaseBackup::listBackups(50)),
        ];
    }

    private static function urlsMatch(string $configured, string $current): bool
    {
        $a = strtolower(rtrim($configured, '/'));
        $b = strtolower(rtrim($current, '/'));

        if ($a === $b) {
            return true;
        }

        $hostA = parse_url($a, PHP_URL_HOST);
        $hostB = parse_url($b, PHP_URL_HOST);
        $pathA = parse_url($a, PHP_URL_PATH) ?: '';
        $pathB = parse_url($b, PHP_URL_PATH) ?: '';

        return $hostA === $hostB && $pathA === $pathB;
    }

    /**
     * @return array{ok: bool, message: string, hostname: string, database: string, tables: int|null}
     */
    private static function databaseStatus(): array
    {
        $hostname = (string) (config('Database')->default['hostname'] ?? '');
        $database = (string) (config('Database')->default['database'] ?? '');

        try {
            $db = \Config\Database::connect();
            $db->initialize();
            $tables = $db->listTables();

            return [
                'ok'       => true,
                'message'  => 'Connected',
                'hostname' => $hostname,
                'database' => $database,
                'tables'   => is_array($tables) ? count($tables) : null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok'       => false,
                'message'  => $e->getMessage(),
                'hostname' => $hostname,
                'database' => $database,
                'tables'   => null,
            ];
        }
    }

    private static function encryptionConfigured(): bool
    {
        try {
            $key = env('ENCRYPTION_KEY', '');
            if ($key === '' || $key === false) {
                $key = config('Encryption')->key ?? '';
            }

            return is_string($key) && strlen($key) >= 16;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<string, array{ok: bool, path: string}>
     */
    private static function writableStatus(): array
    {
        $dirs = ['cache', 'logs', 'session', 'uploads', 'backups'];
        $out  = [];
        foreach ($dirs as $dir) {
            $path = WRITEPATH . $dir;
            $out[$dir] = [
                'path' => $path,
                'ok'   => is_dir($path) && is_writable($path),
            ];
        }

        return $out;
    }
}
