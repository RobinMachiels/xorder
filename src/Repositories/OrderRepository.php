<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Order;
use App\Models\OrderItem;
use Throwable;

/**
 * Tenant-scoped persistence and aggregations for festive orders.
 */
final class OrderRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param array{search?: string, pickup_date?: string, status?: string, payment_status?: string} $filters
     * @return array<int, Order>
     */
    public function listForUser(int $userId, array $filters = []): array
    {
        $sql = 'SELECT * FROM orders WHERE user_id = ?';
        $params = [$userId];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $sql .= ' AND (customer_name LIKE ? OR customer_phone LIKE ? OR order_number LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $pickupDate = trim((string) ($filters['pickup_date'] ?? ''));
        if ($pickupDate !== '') {
            $sql .= ' AND pickup_date = ?';
            $params[] = $pickupDate;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        $paymentStatus = trim((string) ($filters['payment_status'] ?? ''));
        if ($paymentStatus !== '') {
            $sql .= ' AND payment_status = ?';
            $params[] = $paymentStatus;
        }

        $sql .= ' ORDER BY pickup_date ASC, pickup_slot ASC, id DESC';

        $rows = $this->db->fetchAll($sql, $params);
        return $this->hydrateOrdersWithItems($rows);
    }

    /**
     * @return array<int, Order>
     */
    public function recentForUser(int $userId, int $limit = 6): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT ' . max(1, $limit),
            [$userId]
        );

        return $this->hydrateOrdersWithItems($rows);
    }

    /**
     * @return array<int, Order>
     */
    public function getAllWithItemsForExport(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY pickup_date ASC, pickup_slot ASC, id ASC',
            [$userId]
        );

        return $this->hydrateOrdersWithItems($rows);
    }

    public function findForUserWithItems(int $orderId, int $userId): ?Order
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1',
            [$orderId, $userId]
        );
        if ($row === null) {
            return null;
        }

        $orders = $this->hydrateOrdersWithItems([$row]);
        return $orders[0] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array{product_id: ?int, product_name: string, unit: string, quantity: float, unit_price: float}> $items
     */
    public function createWithItems(int $userId, array $data, array $items): int
    {
        $this->db->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $total = $this->calculateTotal($items);
            $orderNumber = $this->generateOrderNumber($userId);

            $paymentStatus = (string) ($data['payment_status'] ?? 'unpaid');
            $deposit = round((float) ($data['deposit_amount'] ?? 0.0), 2);
            if ($paymentStatus === 'paid') {
                $deposit = $total;
            } elseif ($paymentStatus === 'unpaid') {
                $deposit = 0.0;
            }

            $this->db->execute(
                'INSERT INTO orders (
                    user_id, order_number, customer_name, customer_phone, customer_email,
                    pickup_date, pickup_slot, status, payment_status, deposit_amount, total_amount,
                    notes, is_demo, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $orderNumber,
                    trim((string) $data['customer_name']),
                    trim((string) ($data['customer_phone'] ?? '')),
                    trim((string) ($data['customer_email'] ?? '')),
                    (string) $data['pickup_date'],
                    trim((string) ($data['pickup_slot'] ?? '')),
                    (string) ($data['status'] ?? 'confirmed'),
                    $paymentStatus,
                    $deposit,
                    $total,
                    trim((string) ($data['notes'] ?? '')),
                    !empty($data['is_demo']) ? 1 : 0,
                    $now,
                    $now,
                ]
            );

            $orderId = $this->db->lastInsertId();
            $this->insertItems($orderId, $items);

            $this->db->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array{product_id: ?int, product_name: string, unit: string, quantity: float, unit_price: float}> $items
     */
    public function updateWithItems(int $orderId, int $userId, array $data, array $items): void
    {
        $existing = $this->findForUserWithItems($orderId, $userId);
        if ($existing === null) {
            return;
        }

        $this->db->beginTransaction();
        try {
            $total = $this->calculateTotal($items);
            $paymentStatus = (string) ($data['payment_status'] ?? $existing->paymentStatus);
            $deposit = round((float) ($data['deposit_amount'] ?? 0.0), 2);
            if ($paymentStatus === 'paid') {
                $deposit = $total;
            } elseif ($paymentStatus === 'unpaid') {
                $deposit = 0.0;
            }

            $this->db->execute(
                'UPDATE orders SET
                    customer_name = ?, customer_phone = ?, customer_email = ?,
                    pickup_date = ?, pickup_slot = ?, status = ?, payment_status = ?,
                    deposit_amount = ?, total_amount = ?, notes = ?, updated_at = ?
                 WHERE id = ? AND user_id = ?',
                [
                    trim((string) $data['customer_name']),
                    trim((string) ($data['customer_phone'] ?? '')),
                    trim((string) ($data['customer_email'] ?? '')),
                    (string) $data['pickup_date'],
                    trim((string) ($data['pickup_slot'] ?? '')),
                    (string) ($data['status'] ?? 'confirmed'),
                    $paymentStatus,
                    $deposit,
                    $total,
                    trim((string) ($data['notes'] ?? '')),
                    date('Y-m-d H:i:s'),
                    $orderId,
                    $userId,
                ]
            );

            $this->db->execute('DELETE FROM order_items WHERE order_id = ?', [$orderId]);
            $this->insertItems($orderId, $items);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateQuickStatus(int $orderId, int $userId, string $status, string $paymentStatus): void
    {
        $order = $this->findForUserWithItems($orderId, $userId);
        if ($order === null) {
            return;
        }

        $deposit = $order->depositAmount;
        if ($paymentStatus === 'paid') {
            $deposit = $order->totalAmount;
        } elseif ($paymentStatus === 'unpaid') {
            $deposit = 0.0;
        }

        $this->db->execute(
            'UPDATE orders SET status = ?, payment_status = ?, deposit_amount = ?, updated_at = ? WHERE id = ? AND user_id = ?',
            [$status, $paymentStatus, $deposit, date('Y-m-d H:i:s'), $orderId, $userId]
        );
    }

    public function deleteForUser(int $orderId, int $userId): void
    {
        $this->db->execute('DELETE FROM orders WHERE id = ? AND user_id = ?', [$orderId, $userId]);
    }

    public function deleteDemoForUser(int $userId): void
    {
        $this->db->execute('DELETE FROM orders WHERE user_id = ? AND is_demo = 1', [$userId]);
    }

    /**
     * @return array{active_orders: int, total_revenue: float, open_balance: float, upcoming_dates: array<int, array{pickup_date: string, order_count: int, total_amount: float}>}
     */
    public function getDashboardStats(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT pickup_date, status, payment_status, deposit_amount, total_amount
             FROM orders
             WHERE user_id = ? AND status != 'cancelled'
             ORDER BY pickup_date ASC",
            [$userId]
        );

        $activeOrders = 0;
        $totalRevenue = 0.0;
        $openBalance = 0.0;
        $byDate = [];

        foreach ($rows as $row) {
            $activeOrders++;
            $total = (float) $row['total_amount'];
            $deposit = (float) $row['deposit_amount'];
            $totalRevenue += $total;

            if ((string) $row['payment_status'] !== 'paid') {
                $openBalance += max(0.0, $total - $deposit);
            }

            $date = (string) $row['pickup_date'];
            if (!isset($byDate[$date])) {
                $byDate[$date] = [
                    'pickup_date' => $date,
                    'order_count' => 0,
                    'total_amount' => 0.0,
                ];
            }
            $byDate[$date]['order_count']++;
            $byDate[$date]['total_amount'] += $total;
        }

        return [
            'active_orders' => $activeOrders,
            'total_revenue' => round($totalRevenue, 2),
            'open_balance' => round($openBalance, 2),
            'upcoming_dates' => array_values($byDate),
        ];
    }

    /**
     * @return array<int, array{pickup_date: string, product_name: string, unit: string, total_qty: float, order_count: int}>
     */
    public function getKitchenSummary(int $userId, string $pickupDate = ''): array
    {
        $sql = "SELECT
                    o.pickup_date,
                    oi.product_name,
                    oi.unit,
                    SUM(oi.quantity) AS total_qty,
                    COUNT(DISTINCT o.id) AS order_count
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
                WHERE o.user_id = ? AND o.status != 'cancelled'";
        $params = [$userId];

        if ($pickupDate !== '') {
            $sql .= ' AND o.pickup_date = ?';
            $params[] = $pickupDate;
        }

        $sql .= ' GROUP BY o.pickup_date, oi.product_name, oi.unit
                  ORDER BY o.pickup_date ASC, total_qty DESC, oi.product_name ASC';

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'pickup_date' => (string) $row['pickup_date'],
                'product_name' => (string) $row['product_name'],
                'unit' => (string) $row['unit'],
                'total_qty' => round((float) $row['total_qty'], 2),
                'order_count' => (int) $row['order_count'],
            ];
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function distinctPickupDates(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT DISTINCT pickup_date FROM orders WHERE user_id = ? ORDER BY pickup_date ASC',
            [$userId]
        );

        return array_values(array_map(static fn (array $r): string => (string) $r['pickup_date'], $rows));
    }

    /**
     * @param array<int, array<string, mixed>> $orderRows
     * @return array<int, Order>
     */
    private function hydrateOrdersWithItems(array $orderRows): array
    {
        if ($orderRows === []) {
            return [];
        }

        $orderIds = array_map(static fn (array $r): int => (int) $r['id'], $orderRows);
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

        $itemRows = $this->db->fetchAll(
            "SELECT * FROM order_items WHERE order_id IN ({$placeholders}) ORDER BY id ASC",
            $orderIds
        );

        $groupedItems = [];
        foreach ($itemRows as $itemRow) {
            $oid = (int) $itemRow['order_id'];
            $groupedItems[$oid][] = OrderItem::fromArray($itemRow);
        }

        $orders = [];
        foreach ($orderRows as $row) {
            $oid = (int) $row['id'];
            $orders[] = Order::fromArray($row, $groupedItems[$oid] ?? []);
        }

        return $orders;
    }

    /**
     * @param array<int, array{product_id: ?int, product_name: string, unit: string, quantity: float, unit_price: float}> $items
     */
    private function calculateTotal(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += round((float) $item['quantity'] * (float) $item['unit_price'], 2);
        }

        return round($sum, 2);
    }

    /**
     * @param array<int, array{product_id: ?int, product_name: string, unit: string, quantity: float, unit_price: float}> $items
     */
    private function insertItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $qty = round((float) $item['quantity'], 2);
            $price = round((float) $item['unit_price'], 2);
            $lineTotal = round($qty * $price, 2);

            $this->db->execute(
                'INSERT INTO order_items (order_id, product_id, product_name, unit, quantity, unit_price, line_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $orderId,
                    $item['product_id'],
                    trim((string) $item['product_name']),
                    trim((string) $item['unit']) !== '' ? trim((string) $item['unit']) : 'stuk',
                    $qty,
                    $price,
                    $lineTotal,
                ]
            );
        }
    }

    private function generateOrderNumber(int $userId): string
    {
        $row = $this->db->fetchOne('SELECT COUNT(*) AS cnt FROM orders WHERE user_id = ?', [$userId]);
        $next = ((int) ($row['cnt'] ?? 0)) + 1;

        return sprintf('XO-%s-%03d', date('y'), $next);
    }
}
