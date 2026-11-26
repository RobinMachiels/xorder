<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Simple brute-force protection: max N attempts per action and key within a time window.
 * Only a hash of the key (email + IP) is stored.
 */
final class LoginThrottle
{
    private const LIMITS = [
        'login' => ['max' => 8, 'window' => 900],
        'forgot' => ['max' => 5, 'window' => 3600],
    ];

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function tooManyAttempts(string $action, string $key): bool
    {
        $limit = self::LIMITS[$action] ?? ['max' => 10, 'window' => 900];
        $since = date('Y-m-d H:i:s', time() - $limit['window']);

        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS total FROM login_attempts WHERE action = ? AND key_hash = ? AND created_at > ?',
            [$action, $this->hash($key), $since]
        );

        return (int) ($row['total'] ?? 0) >= $limit['max'];
    }

    public function hit(string $action, string $key): void
    {
        $this->db->execute(
            'INSERT INTO login_attempts (action, key_hash, created_at) VALUES (?, ?, ?)',
            [$action, $this->hash($key), date('Y-m-d H:i:s')]
        );

        // Housekeeping: oude pogingen opruimen (ouder dan 1 dag)
        if (random_int(1, 50) === 1) {
            $this->db->execute('DELETE FROM login_attempts WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
        }
    }

    public function clear(string $action, string $key): void
    {
        $this->db->execute('DELETE FROM login_attempts WHERE action = ? AND key_hash = ?', [$action, $this->hash($key)]);
    }

    private function hash(string $key): string
    {
        return hash('sha256', strtolower(trim($key)));
    }
}
