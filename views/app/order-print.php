<?php

declare(strict_types=1);

/**
 * @var \App\Models\User $user
 * @var \App\Models\Order $order
 * @var string $locale
 * @var callable $e
 * @var callable $t
 * @var callable $url
 */
?>
<!DOCTYPE html>
<html lang="<?= $e($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($t('orders.print_title')) ?> <?= $e($order->orderNumber) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= $e($assetUrl('/assets/img/xorder-mark.svg')) ?>">
    <link rel="stylesheet" href="<?= $e($assetUrl('/assets/css/app.css')) ?>">
</head>
<body style="background:#fff;padding:24px;">
<div class="container" style="max-width:720px;">
    <div class="no-print" style="display:flex;justify-content:space-between;margin-bottom:20px;">
        <a href="<?= $e($url('/app/orders')) ?>" class="btn btn-outline btn-sm"><?= $e($t('orders.print_back')) ?></a>
        <button type="button" data-print class="btn btn-primary btn-sm"><?= $e($t('orders.print_now')) ?></button>
    </div>

    <div class="card" style="border:2px solid var(--pine-900);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--border);padding-bottom:14px;margin-bottom:16px;">
            <div>
                <h1 style="margin:0;font-size:1.45rem;"><?= $e($user->businessName) ?></h1>
                <small style="color:var(--ink-600);"><?= $e($t('orders.print_title')) ?> · <?= $e($order->orderNumber) ?></small>
            </div>
            <div style="text-align:right;">
                <strong><?= $e($order->pickupDate) ?></strong><br>
                <span><?= $e($order->pickupSlot) ?></span>
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <strong><?= $e($t('orders.col_customer')) ?>:</strong> <?= $e($order->customerName) ?>
            <?php if ($order->customerPhone !== ''): ?>
                · <?= $e($order->customerPhone) ?>
            <?php endif; ?>
        </div>

        <table class="data-table" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th><?= $e($t('orders.item_name')) ?></th>
                    <th><?= $e($t('orders.item_qty')) ?></th>
                    <th><?= $e($t('orders.item_price')) ?></th>
                    <th><?= $e($t('orders.item_total')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->items as $item): ?>
                    <tr>
                        <td><?= $e($item->productName) ?></td>
                        <td><?= $e($item->quantity) ?> <?= $e($item->unit) ?></td>
                        <td>€ <?= $e(number_format($item->unitPrice, 2, ',', '.')) ?></td>
                        <td>€ <?= $e(number_format($item->lineTotal, 2, ',', '.')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($order->notes !== ''): ?>
            <div style="padding:10px 12px;background:var(--cream-50);border:1px solid var(--border);border-radius:8px;margin-bottom:16px;">
                <strong><?= $e($t('orders.notes')) ?>:</strong> <?= $e($order->notes) ?>
            </div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;font-size:1.05rem;">
            <div>
                <span><?= $e($t('orders.payment_status')) ?>: <strong><?= $e($t('payment.' . $order->paymentStatus)) ?></strong></span>
                <?php if ($order->remainingBalance() > 0): ?>
                    · <span><?= $e($t('orders.balance_due')) ?>: <strong>€ <?= $e(number_format($order->remainingBalance(), 2, ',', '.')) ?></strong></span>
                <?php endif; ?>
            </div>
            <div>
                <strong><?= $e($t('orders.grand_total')) ?>: € <?= $e(number_format($order->totalAmount, 2, ',', '.')) ?></strong>
            </div>
        </div>
    </div>
</div>
<script src="<?= $e($assetUrl('/assets/js/app.js')) ?>"></script>
</body>
</html>
