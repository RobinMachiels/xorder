<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Individual line item inside an order.
 */
final class OrderItem
{
    public int $id;
    public int $orderId;
    public ?int $productId;
    public string $productName;
    public string $unit;
    public float $quantity;
    public float $unitPrice;
    public float $lineTotal;

    public function __construct(
        int $id,
        int $orderId,
        ?int $productId,
        string $productName,
        string $unit,
        float $quantity,
        float $unitPrice,
        float $lineTotal
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->unit = $unit;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->lineTotal = $lineTotal;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['order_id'],
            isset($row['product_id']) && $row['product_id'] !== null && $row['product_id'] !== '' ? (int) $row['product_id'] : null,
            (string) $row['product_name'],
            (string) ($row['unit'] ?? 'stuk'),
            (float) $row['quantity'],
            (float) $row['unit_price'],
            (float) $row['line_total']
        );
    }
}
