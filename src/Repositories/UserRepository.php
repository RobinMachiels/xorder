<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;

/**
 * Persistence operations for merchant user accounts.
 */
final class UserRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?User
    {
        $row = $this->db->fetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        return $row !== null ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db->fetchOne('SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1', [trim($email)]);
        return $row !== null ? User::fromArray($row) : null;
    }

    /**
     * @param array<int, string> $pickupDates
     * @param array<int, string> $timeSlots
     */
    public function create(
        string $businessName,
        string $sector,
        string $email,
        string $passwordHash,
        string $locale,
        array $pickupDates,
        array $timeSlots,
        bool $hasDemoData
    ): int {
        $now = date('Y-m-d H:i:s');
        $this->db->execute(
            'INSERT INTO users (
                business_name, sector, email, password_hash, locale,
                pickup_dates_json, time_slots_json, is_founding_user, has_demo_data,
                last_backup_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NULL, ?, ?)',
            [
                trim($businessName),
                $sector,
                strtolower(trim($email)),
                $passwordHash,
                $locale,
                json_encode(array_values($pickupDates), JSON_UNESCAPED_UNICODE) ?: '[]',
                json_encode(array_values($timeSlots), JSON_UNESCAPED_UNICODE) ?: '[]',
                $hasDemoData ? 1 : 0,
                $now,
                $now,
            ]
        );

        return $this->db->lastInsertId();
    }

    /**
     * @param array<int, string> $pickupDates
     * @param array<int, string> $timeSlots
     */
    public function updateSettings(
        int $userId,
        string $businessName,
        string $sector,
        string $locale,
        array $pickupDates,
        array $timeSlots
    ): void {
        $this->db->execute(
            'UPDATE users
             SET business_name = ?, sector = ?, locale = ?, pickup_dates_json = ?, time_slots_json = ?, updated_at = ?
             WHERE id = ?',
            [
                trim($businessName),
                $sector,
                $locale,
                json_encode(array_values($pickupDates), JSON_UNESCAPED_UNICODE) ?: '[]',
                json_encode(array_values($timeSlots), JSON_UNESCAPED_UNICODE) ?: '[]',
                date('Y-m-d H:i:s'),
                $userId,
            ]
        );
    }

    public function markBackupDone(int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->execute(
            'UPDATE users SET last_backup_at = ?, updated_at = ? WHERE id = ?',
            [$now, $now, $userId]
        );
    }

    public function setHasDemoData(int $userId, bool $hasDemoData): void
    {
        $this->db->execute(
            'UPDATE users SET has_demo_data = ?, updated_at = ? WHERE id = ?',
            [$hasDemoData ? 1 : 0, date('Y-m-d H:i:s'), $userId]
        );
    }
}
