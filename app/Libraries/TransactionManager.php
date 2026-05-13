<?php

namespace App\Libraries;

/**
 * Member 3: Wraps business operations in DB transactions with ACID guarantees.
 * Logs every transaction (commit or rollback) to the `transactions_log` table.
 */
class TransactionManager
{
    /**
     * Execute a callable inside a database transaction.
     * On success: commits and logs status=COMMITTED.
     * On failure: rolls back and logs status=ROLLED_BACK with error details.
     *
     * @param string   $operation    Human-readable operation name (e.g. "USER_CREATE")
     * @param callable $callback     The business logic to execute. Receives $db as first arg.
     * @param string|null $targetTable  Related table name for the log entry
     * @param int|null    $targetId     Related record ID for the log entry
     * @return mixed  The return value of $callback on success
     * @throws \Throwable  Re-throws the original exception after logging the rollback
     */
    public static function run(
        string   $operation,
        callable $callback,
        ?string  $targetTable = null,
        ?int     $targetId = null
    ): mixed {
        $db = \Config\Database::connect();
        $userId = (int) (session()->get('user_id') ?? 0) ?: null;
        $start = hrtime(true);

        $db->transStart();

        try {
            $result = $callback($db);

            $db->transComplete();

            $elapsed = (int) ((hrtime(true) - $start) / 1_000_000);

            if ($db->transStatus() === false) {
                self::logTransaction($userId, $operation, $targetTable, $targetId, 'ROLLED_BACK', 'Transaction failed (transStatus=false)', $elapsed);
                throw new \RuntimeException('Database transaction failed for operation: ' . $operation);
            }

            self::logTransaction($userId, $operation, $targetTable, $targetId, 'COMMITTED', null, $elapsed);

            return $result;
        } catch (\Throwable $e) {
            $elapsed = (int) ((hrtime(true) - $start) / 1_000_000);

            $db->transRollback();

            self::logTransaction($userId, $operation, $targetTable, $targetId, 'ROLLED_BACK', $e->getMessage(), $elapsed);

            throw $e;
        }
    }

    private static function logTransaction(
        ?int    $userId,
        string  $operation,
        ?string $targetTable,
        ?int    $targetId,
        string  $status,
        ?string $error,
        ?int    $durationMs
    ): void {
        try {
            $db = \Config\Database::connect();
            $db->table('transactions_log')->insert([
                'user_id'      => $userId,
                'operation'    => $operation,
                'target_table' => $targetTable,
                'target_id'    => $targetId,
                'status'       => $status,
                'error'        => $error,
                'duration_ms'  => $durationMs,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'TransactionManager::logTransaction: ' . $e->getMessage());
        }
    }

    /**
     * Fetch recent transaction log entries for the admin panel.
     *
     * @return list<array<string, mixed>>
     */
    public static function recent(int $limit = 30): array
    {
        try {
            $db = \Config\Database::connect();
            return $db->table('transactions_log')
                ->select('transactions_log.*, users.name as user_name, users.username')
                ->join('users', 'users.id = transactions_log.user_id', 'left')
                ->orderBy('transactions_log.created_at', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'TransactionManager::recent: ' . $e->getMessage());
            return [];
        }
    }
}
