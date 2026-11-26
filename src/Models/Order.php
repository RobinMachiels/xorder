<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Customer festive order entity with attached line items.
 */
final class Order
{
    public const STATUSES = ['new', 'confirmed', 'ready', 'picked_up', 'cancelled'];
    public const PAYMENT_STATUSES = ['unpaid', 'deposit', 'paid'];

    public int $id;
    public int $userId;
    public string $orderNumber;
    public string $customerName;
    public string $customerPhone;
    public string $customerEmail;
    public string $pickupDate;
    public string $pickupSlot;
    public string $status;
    public string $paymentStatus;
    public float $depositAmount;
    public float $totalAmount;
    public string $notes;
    public bool $isDemo;
    public string $createdAt;
    public string $updatedAt;

    /** @var array<int, OrderItem> */
    public array $items;

    /**
     * @param array<int, OrderItem> $items
     */
    public function __construct(
        int $id,
        int $userId,
        string $orderNumber,
        string $customerName,
        string $customerPhone,
        string $customerEmail,
        string $pickupDate,
        string $pickupSlot,
        string $status,
        string $paymentStatus,
        float $depositAmount,
        float $totalAmount,
        string $notes,
        bool $isDemo,
        string $createdAt,
        string $updatedAt,
        array $items = []
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->orderNumber = $orderNumber;
        $this->customerName = $customerName;
        $this->customerPhone = $customerPhone;
        $this->customerEmail = $customerEmail;
        $this->pickupDate = $pickupDate;
        $this->pickupSlot = $pickupSlot;
        $this->status = $status;
        $this->paymentStatus = $paymentStatus;
        $this->depositAmount = $depositAmount;
        $this->totalAmount = $totalAmount;
        $this->notes = $notes;
        $this->isDemo = $isDemo;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->items = $items;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, OrderItem> $items
     */
    public static function fromArray(array $row, array $items = []): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (string) $row['order_number'],
            (string) $row['customer_name'],
            (string) ($row['customer_phone'] ?? ''),
            (string) ($row['customer_email'] ?? ''),
            (string) $row['pickup_date'],
            (string) ($row['pickup_slot'] ?? ''),
            (string) ($row['status'] ?? 'confirmed'),
            (string) ($row['payment_status'] ?? 'unpaid'),
            (float) ($row['deposit_amount'] ?? 0.0),
            (float) ($row['total_amount'] ?? 0.0),
            (string) ($row['notes'] ?? ''),
            (bool) ($row['is_demo'] ?? false),
            (string) $row['created_at'],
            (string) $row['updated_at'],
            $items
        );
    }

    public function remainingBalance(): float
    {
        if ($this->paymentStatus === 'paid' || $this->status === 'cancelled') {
            return 0.0;
        }

        return max(0.0, round($this->totalAmount - $this->depositAmount, 2));
    }

    public function itemsSummary(): string
    {
        $parts = [];
        foreach ($this->items as $item) {
            $qty = fmod($item->quantity, 1.0) === 0.0
                ? (string) (int) $item->quantity
                : number_format($item->quantity, 2, ',', '');
            $parts[] = $qty . 'x ' . $item->productName;
        }

        return implode(', ', $parts);
    }
}
