<?php

namespace App\Libraries;

/**
 * MySQL logical backup / restore using mysqldump and mysql CLI (XAMPP-friendly).
 */
class DatabaseBackup
{
    private const MIN_DUMP_BYTES = 64;

    public static function backupDirectory(): string
    {
        $dir = WRITEPATH . 'backups';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * @return list<array{name: string, size: string, size_bytes: int, modified_at: string}>
     */
    public static function listBackups(int $limit = 20): array
    {
        $dir   = self::backupDirectory();
        $files = glob($dir . DIRECTORY_SEPARATOR . 'ajesdb_*.sql');
        if (! is_array($files)) {
            return [];
        }

        rsort($files);
        $out = [];
        foreach (array_slice($files, 0, $limit) as $path) {
            if (! is_file($path) || ! self::isValidDumpFile($path)) {
                continue;
            }
            $bytes = (int) filesize($path);
            $out[] = [
                'name'        => basename($path),
                'size'        => self::formatBytes($bytes),
                'size_bytes'  => $bytes,
                'modified_at' => date('Y-m-d H:i:s', (int) filemtime($path)),
            ];
        }

        return $out;
    }

    /**
     * @return array{ok: bool, message: string, file?: string}
     */
    public static function createBackup(): array
    {
        $db     = self::dbConfig();
        $dump   = self::resolveTool('mysqldump');
        if ($dump === null) {
            return ['ok' => false, 'message' => 'mysqldump not found. Install MySQL or set MYSQL_BIN in the server environment (e.g. C:\\xampp\\mysql\\bin).'];
        }

        $dir      = self::backupDirectory();
        $stamp    = date('Ymd_His');
        $outFile  = $dir . DIRECTORY_SEPARATOR . 'ajesdb_' . $stamp . '.sql';
        $cnf      = self::writeClientCnf($db);
        $errFile  = $dir . DIRECTORY_SEPARATOR . '_mysqldump_err_' . $stamp . '.txt';

        $args = [
            $dump,
            '--defaults-extra-file=' . $cnf,
            '--single-transaction',
            '--routines',
            '--events',
            '--add-drop-table',
            '--default-character-set=utf8mb4',
            $db['database'],
        ];

        $code = self::runProcess($args, $outFile, $errFile);
        @unlink($cnf);

        if ($code !== 0 || ! self::isValidDumpFile($outFile)) {
            @unlink($outFile);
            $err = is_file($errFile) ? trim((string) file_get_contents($errFile)) : 'Unknown error';
            @unlink($errFile);

            return ['ok' => false, 'message' => 'Backup failed: ' . ($err !== '' ? $err : 'invalid or empty SQL output')];
        }

        @unlink($errFile);

        return [
            'ok'      => true,
            'message' => 'Database backup created successfully.',
            'file'    => basename($outFile),
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function restoreBackup(string $filename, bool $preBackup = true): array
    {
        $safe = self::safeFilename($filename);
        if ($safe === null) {
            return ['ok' => false, 'message' => 'Invalid backup file name.'];
        }

        $path = self::backupDirectory() . DIRECTORY_SEPARATOR . $safe;
        if (! is_file($path) || ! self::isValidDumpFile($path)) {
            return ['ok' => false, 'message' => 'Backup file not found or not a valid SQL dump.'];
        }

        if ($preBackup) {
            $snap = self::createBackup();
            if (! $snap['ok']) {
                return ['ok' => false, 'message' => 'Pre-restore safety backup failed: ' . $snap['message']];
            }
        }

        $db    = self::dbConfig();
        $mysql = self::resolveTool('mysql');
        if ($mysql === null) {
            return ['ok' => false, 'message' => 'mysql client not found. Set MYSQL_BIN or install MySQL.'];
        }

        $cnf     = self::writeClientCnf($db);
        $errFile = self::backupDirectory() . DIRECTORY_SEPARATOR . '_mysql_restore_err.txt';

        $args = [
            $mysql,
            '--defaults-extra-file=' . $cnf,
            $db['database'],
        ];

        $code = self::runProcess($args, null, $errFile, $path);
        @unlink($cnf);

        if ($code !== 0) {
            $err = is_file($errFile) ? trim((string) file_get_contents($errFile)) : 'Unknown error';
            @unlink($errFile);

            return ['ok' => false, 'message' => 'Restore failed: ' . $err];
        }

        @unlink($errFile);

        return ['ok' => true, 'message' => 'Database restored from ' . $safe . '.'];
    }

    public static function safeFilename(string $name): ?string
    {
        $base = basename($name);
        if (! preg_match('/^ajesdb_\d{8}_\d{6}\.sql$/', $base)) {
            return null;
        }

        return $base;
    }

    public static function isValidDumpFile(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }
        if ((int) filesize($path) < self::MIN_DUMP_BYTES) {
            return false;
        }

        $head = (string) file_get_contents($path, false, null, 0, 4096);

        return (bool) preg_match('/CREATE\s+TABLE|INSERT\s+INTO|DROP\s+TABLE|mysqldump/i', $head);
    }

    /**
     * @return array{hostname: string, database: string, username: string, password: string, port: int}
     */
    private static function dbConfig(): array
    {
        $db = config('Database')->default;

        return [
            'hostname' => (string) ($db['hostname'] ?? 'localhost'),
            'database' => (string) ($db['database'] ?? ''),
            'username' => (string) ($db['username'] ?? ''),
            'password' => (string) ($db['password'] ?? ''),
            'port'     => (int) ($db['port'] ?? 3306),
        ];
    }

    private static function resolveTool(string $name): ?string
    {
        $exe = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? $name . '.exe' : $name;

        $bin = getenv('MYSQL_BIN');
        if (is_string($bin) && $bin !== '') {
            $cand = rtrim($bin, '\\/') . DIRECTORY_SEPARATOR . $exe;
            if (is_file($cand)) {
                return $cand;
            }
        }

        $xampp = [
            'C:\\xampp\\mysql\\bin\\' . $exe,
            'C:\\XAMPP\\mysql\\bin\\' . $exe,
        ];
        foreach ($xampp as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
        $out   = [];
        @exec($which . ' ' . escapeshellarg($name) . ' 2>nul', $out);
        if (isset($out[0]) && is_file(trim($out[0]))) {
            return trim($out[0]);
        }

        return null;
    }

    /**
     * @param array{hostname: string, database: string, username: string, password: string, port: int} $db
     */
    private static function writeClientCnf(array $db): string
    {
        $cnf = tempnam(sys_get_temp_dir(), 'ajes_mysql_');
        if ($cnf === false) {
            throw new \RuntimeException('Could not create temporary MySQL config.');
        }

        $lines = [
            '[client]',
            'host=' . $db['hostname'],
            'port=' . $db['port'],
            'user=' . $db['username'],
        ];
        if ($db['password'] !== '') {
            $lines[] = 'password=' . $db['password'];
        }

        file_put_contents($cnf, implode("\n", $lines) . "\n");

        return $cnf;
    }

    /**
     * @param list<string> $command
     */
    private static function runProcess(array $command, ?string $stdoutFile, ?string $stderrFile, ?string $stdinFile = null): int
    {
        $descriptor = [
            0 => $stdinFile !== null && is_file($stdinFile) ? ['file', $stdinFile, 'r'] : ['pipe', 'r'],
            1 => $stdoutFile !== null ? ['file', $stdoutFile, 'w'] : ['pipe', 'w'],
            2 => $stderrFile !== null ? ['file', $stderrFile, 'w'] : ['pipe', 'w'],
        ];

        $proc = proc_open($command, $descriptor, $pipes);
        if (! is_resource($proc)) {
            return 1;
        }

        if ($stdinFile === null && isset($pipes[0])) {
            fclose($pipes[0]);
        }

        if ($stdoutFile === null && isset($pipes[1])) {
            fclose($pipes[1]);
        }
        if ($stderrFile === null && isset($pipes[2])) {
            fclose($pipes[2]);
        }

        return proc_close($proc);
    }

    public static function formatBytes(int $bytes): string
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
