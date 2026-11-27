<?php

declare(strict_types=1);

/**
 * @var \App\Models\User $user
 * @var \App\Models\Order|null $order
 * @var array<int, \App\Models\Product> $catalog
 * @var callable $e
 * @var callable $t
 * @var callable $url
 * @var callable $csrfField
 */

$isEdit = $order !== null;
$actionUrl = $isEdit ? $url('/app/order-edit', ['id' => $order->id]) : $url('/app/order-new');

$existingItems = $isEdit && $order->items !== []
    ? $order->items
    : [new \App\Models\OrderItem(0, 0, null, '', 'stuk', 1, 0.0, 0.0)];

$pickupDateVal = $isEdit ? $order->pickupDate : ($user->pickupDates[0] ?? '2026-12-24');
$pickupSlotVal = $isEdit ? $order->pickupSlot : ($user->timeSlots[0] ?? '10:00 - 11:00');
?>
<div class="container app-shell">
    <div class="page-top">
        <div>
            <h1><?= $e($isEdit ? $t('orders.edit') . ' ' . $order->orderNumber : $t('orders.new')) ?></h1>
        </div>
        <a href="<?= $e($url('/app/orders')) ?>" class="btn btn-outline"><?= $e($t('orders.print_back')) ?></a>
    </div>

    <form method="post" action="<?= $e($actionUrl) ?>" class="card">
        <?= $csrfField() ?>

        <div class="grid-3">
            <div class="form-group">
                <label class="form-label" for="o-name"><?= $e($t('orders.customer_name')) ?> *</label>
                <input id="o-name" class="form-control" type="text" name="customer_name" required value="<?= $e($isEdit ? $order->customerName : '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="o-phone"><?= $e($t('orders.customer_phone')) ?></label>
                <input id="o-phone" class="form-control" type="text" name="customer_phone" value="<?= $e($isEdit ? $order->customerPhone : '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="o-email"><?= $e($t('orders.customer_email')) ?></label>
                <input id="o-email" class="form-control" type="email" name="customer_email" value="<?= $e($isEdit ? $order->customerEmail : '') ?>">
            </div>
        </div>

        <div class="grid-4">
            <div class="form-group">
                <label class="form-label" for="o-date"><?= $e($t('orders.pickup_date')) ?> *</label>
                <input id="o-date" class="form-control" type="date" name="pickup_date" list="pickup-dates-list" required value="<?= $e($pickupDateVal) ?>">
                <datalist id="pickup-dates-list">
                    <?php foreach ($user->pickupDates as $d): ?>
                        <option value="<?= $e($d) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label class="form-label" for="o-slot"><?= $e($t('orders.pickup_slot')) ?></label>
                <input id="o-slot" class="form-control" type="text" name="pickup_slot" list="pickup-slots-list" value="<?= $e($pickupSlotVal) ?>">
                <datalist id="pickup-slots-list">
                    <?php foreach ($user->timeSlots as $slot): ?>
                        <option value="<?= $e($slot) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label class="form-label" for="o-status"><?= $e($t('orders.status')) ?></label>
                <select id="o-status" class="form-select" name="status">
                    <?php $curStatus = $isEdit ? $order->status : 'confirmed'; ?>
                    <?php foreach (\App\Models\Order::STATUSES as $st): ?>
                        <option value="<?= $e($st) ?>" <?= $curStatus === $st ? 'selected' : '' ?>><?= $e($t('status.' . $st)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="o-payment"><?= $e($t('orders.payment_status')) ?></label>
                <select id="o-payment" class="form-select" name="payment_status">
                    <?php $curPay = $isEdit ? $order->paymentStatus : 'unpaid'; ?>
                    <?php foreach (\App\Models\Order::PAYMENT_STATUSES as $pst): ?>
                        <option value="<?= $e($pst) ?>" <?= $curPay === $pst ? 'selected' : '' ?>><?= $e($t('payment.' . $pst)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="o-deposit"><?= $e($t('orders.deposit_amount')) ?></label>
                <input id="o-deposit" class="form-control" type="number" step="0.01" min="0" name="deposit_amount" value="<?= $e($isEdit ? number_format($order->depositAmount, 2, '.', '') : '0.00') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="o-notes"><?= $e($t('orders.notes')) ?></label>
                <input id="o-notes" class="form-control" type="text" name="notes" value="<?= $e($isEdit ? $order->notes : '') ?>">
            </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h2 style="margin:0;font-size:1.15rem;"><?= $e($t('orders.items_title')) ?></h2>
            <button type="button" id="add-order-item-btn" class="btn btn-outline btn-sm"><?= $e($t('orders.add_item')) ?></button>
        </div>

        <div id="order-items-container">
            <?php foreach ($existingItems as $item): ?>
                <div class="item-row">
                    <select class="form-select" name="item_product_id[]" data-field="product-select">
                        <option value=""><?= $e($t('orders.select_product')) ?></option>
                        <?php foreach ($catalog as $prod): ?>
                            <option
                                value="<?= $e($prod->id) ?>"
                                data-name="<?= $e($prod->name) ?>"
                                data-unit="<?= $e($prod->unit) ?>"
                                data-price="<?= $e(number_format($prod->price, 2, '.', '')) ?>"
                                <?= $item->productId === $prod->id ? 'selected' : '' ?>
                            ><?= $e($prod->name) ?> (€ <?= $e(number_format($prod->price, 2, ',', '.')) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control" type="text" name="item_name[]" data-field="name" placeholder="<?= $e($t('orders.item_name')) ?>" value="<?= $e($item->productName) ?>" required>
                    <input class="form-control" type="text" name="item_unit[]" data-field="unit" placeholder="<?= $e($t('orders.item_unit')) ?>" value="<?= $e($item->unit) ?>">
                    <input class="form-control" type="number" step="0.5" min="0.5" name="item_qty[]" data-field="qty" placeholder="<?= $e($t('orders.item_qty')) ?>" value="<?= $e($item->quantity) ?>" required>
                    <input class="form-control" type="number" step="0.01" min="0" name="item_price[]" data-field="price" placeholder="<?= $e($t('orders.item_price')) ?>" value="<?= $e(number_format($item->unitPrice, 2, '.', '')) ?>" required>
                    <strong data-field="line-total" style="text-align:right;font-size:0.9rem;">EUR 0,00</strong>
                    <button type="button" class="btn btn-danger btn-sm" data-action="remove-row">×</button>
                </div>
            <?php endforeach; ?>
        </div>

        <template id="order-item-row-template">
            <div class="item-row">
                <select class="form-select" name="item_product_id[]" data-field="product-select">
                    <option value=""><?= $e($t('orders.select_product')) ?></option>
                    <?php foreach ($catalog as $prod): ?>
                        <option
                            value="<?= $e($prod->id) ?>"
                            data-name="<?= $e($prod->name) ?>"
                            data-unit="<?= $e($prod->unit) ?>"
                            data-price="<?= $e(number_format($prod->price, 2, '.', '')) ?>"
                        ><?= $e($prod->name) ?> (€ <?= $e(number_format($prod->price, 2, ',', '.')) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input class="form-control" type="text" name="item_name[]" data-field="name" placeholder="<?= $e($t('orders.item_name')) ?>" required>
                <input class="form-control" type="text" name="item_unit[]" data-field="unit" placeholder="<?= $e($t('orders.item_unit')) ?>" value="stuk">
                <input class="form-control" type="number" step="0.5" min="0.5" name="item_qty[]" data-field="qty" placeholder="<?= $e($t('orders.item_qty')) ?>" value="1" required>
                <input class="form-control" type="number" step="0.01" min="0" name="item_price[]" data-field="price" placeholder="<?= $e($t('orders.item_price')) ?>" value="0.00" required>
                <strong data-field="line-total" style="text-align:right;font-size:0.9rem;">EUR 0,00</strong>
                <button type="button" class="btn btn-danger btn-sm" data-action="remove-row">×</button>
            </div>
        </template>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);flex-wrap:wrap;gap:12px;">
            <div style="font-size:1.15rem;">
                <?= $e($t('orders.grand_total')) ?>: <strong id="order-grand-total" style="color:var(--pine-900);">EUR 0,00</strong>
            </div>
            <button type="submit" class="btn btn-primary"><?= $e($t('orders.save_btn')) ?></button>
        </div>
    </form>
</div>
