<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Authenticated caterer/merchant account entity.
 */
final class User
{
    public int $id;
    public string $businessName;
    public string $sector;
    public string $email;
    public string $passwordHash;
    public string $locale;

    /** @var array<int, string> */
    public array $pickupDates;

    /** @var array<int, string> */
    public array $timeSlots;

    public bool $isFoundingUser;
    public bool $hasDemoData;
    public ?string $lastBackupAt;
    public string $createdAt;
    public string $updatedAt;

    /**
     * @param array<int, string> $pickupDates
     * @param array<int, string> $timeSlots
     */
    public function __construct(
        int $id,
        string $businessName,
        string $sector,
        string $email,
        string $passwordHash,
        string $locale,
        array $pickupDates,
        array $timeSlots,
        bool $isFoundingUser,
        bool $hasDemoData,
        ?string $lastBackupAt,
        string $createdAt,
        string $updatedAt
    ) {
        $this->id = $id;
        $this->businessName = $businessName;
        $this->sector = $sector;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->locale = $locale;
        $this->pickupDates = $pickupDates;
        $this->timeSlots = $timeSlots;
        $this->isFoundingUser = $isFoundingUser;
        $this->hasDemoData = $hasDemoData;
        $this->lastBackupAt = $lastBackupAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $dates = json_decode((string) ($row['pickup_dates_json'] ?? '[]'), true);
        $slots = json_decode((string) ($row['time_slots_json'] ?? '[]'), true);

        return new self(
            (int) $row['id'],
            (string) $row['business_name'],
            (string) ($row['sector'] ?? 'traiteur'),
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) ($row['locale'] ?? 'nl'),
            is_array($dates) ? array_values(array_map('strval', $dates)) : [],
            is_array($slots) ? array_values(array_map('strval', $slots)) : [],
            (bool) ($row['is_founding_user'] ?? true),
            (bool) ($row['has_demo_data'] ?? false),
            isset($row['last_backup_at']) && $row['last_backup_at'] !== '' ? (string) $row['last_backup_at'] : null,
            (string) $row['created_at'],
            (string) $row['updated_at']
        );
    }
}
