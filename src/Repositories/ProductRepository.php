<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Product;

/**
 * Tenant-scoped persistence for catalog products.
 */
final class ProductRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return array<int, Product>
     */
    public function allForUser(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM products WHERE user_id = ? ORDER BY is_active DESC, category ASC, name ASC',
            [$userId]
        );

        return array_map(static fn (array $row): Product => Product::fromArray($row), $rows);
    }

    /**
     * @return array<int, Product>
     */
    public function activeForUser(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM products WHERE user_id = ? AND is_active = 1 ORDER BY category ASC, name ASC',
            [$userId]
        );

        return array_map(static fn (array $row): Product => Product::fromArray($row), $rows);
    }

    public function findForUser(int $id, int $userId): ?Product
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM products WHERE id = ? AND user_id = ? LIMIT 1',
            [$id, $userId]
        );

        return $row !== null ? Product::fromArray($row) : null;
    }

    public function countActiveForUser(int $userId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM products WHERE user_id = ? AND is_active = 1',
            [$userId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function create(
        int $userId,
        string $name,
        string $category,
        string $unit,
        float $price,
        bool $isActive = true,
        bool $isDemo = false
    ): int {
        $now = date('Y-m-d H:i:s');
        $this->db->execute(
            'INSERT INTO products (user_id, name, category, unit, price, is_active, is_demo, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                trim($name),
                trim($category),
                trim($unit) !== '' ? trim($unit) : 'stuk',
                round($price, 2),
                $isActive ? 1 : 0,
                $isDemo ? 1 : 0,
                $now,
                $now,
            ]
        );

        return $this->db->lastInsertId();
    }

    public function update(
        int $id,
        int $userId,
        string $name,
        string $category,
        string $unit,
        float $price,
        bool $isActive
    ): void {
        $this->db->execute(
            'UPDATE products
             SET name = ?, category = ?, unit = ?, price = ?, is_active = ?, updated_at = ?
             WHERE id = ? AND user_id = ?',
            [
                trim($name),
                trim($category),
                trim($unit) !== '' ? trim($unit) : 'stuk',
                round($price, 2),
                $isActive ? 1 : 0,
                date('Y-m-d H:i:s'),
                $id,
                $userId,
            ]
        );
    }

    public function deleteForUser(int $id, int $userId): void
    {
        $this->db->execute('DELETE FROM products WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function deleteDemoForUser(int $userId): void
    {
        $this->db->execute('DELETE FROM products WHERE user_id = ? AND is_demo = 1', [$userId]);
    }
}
