<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Catalog dish or festive platter entity.
 */
final class Product
{
    public int $id;
    public int $userId;
    public string $name;
    public string $category;
    public string $unit;
    public float $price;
    public bool $isActive;
    public bool $isDemo;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(
        int $id,
        int $userId,
        string $name,
        string $category,
        string $unit,
        float $price,
        bool $isActive,
        bool $isDemo,
        string $createdAt,
        string $updatedAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->category = $category;
        $this->unit = $unit;
        $this->price = $price;
        $this->isActive = $isActive;
        $this->isDemo = $isDemo;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (string) $row['name'],
            (string) ($row['category'] ?? ''),
            (string) ($row['unit'] ?? 'stuk'),
            (float) ($row['price'] ?? 0.0),
            (bool) ($row['is_active'] ?? true),
            (bool) ($row['is_demo'] ?? false),
            (string) $row['created_at'],
            (string) $row['updated_at']
        );
    }
}
