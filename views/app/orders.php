<?php

declare(strict_types=1);

/**
 * @var array<int, \App\Models\Order> $orders
 * @var array{search: string, pickup_date: string, status: string, payment_status: string} $filters
 * @var array<int, string> $availableDates
 * @var callable $e
 * @var callable $t
 * @var callable $url
 * @var callable $csrfField
 */
?>
<div class="container app-shell">
    <div class="page-top">
        <div>
            <h1><?= $e($t('orders.title')) ?></h1>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="<?= $e($url('/app/export/xlsx')) ?>" class="btn btn-gold"><?= $e($t('dash.backup_btn')) ?></a>
            <a href="<?= $e($url('/app/orders/new')) ?>" class="btn btn-primary"><?= $e($t('orders.new')) ?></a>
        </div>
    </div>

    <form method="get" action="<?= $e($url('/app/orders')) ?>" class="card filter-bar" style="padding:16px;">
        <div>
            <label class="form-label" for="f-search"><?= $e($t('orders.col_customer')) ?></label>
            <input id="f-search" class="form-control" type="text" name="search" value="<?= $e($filters['search']) ?>" placeholder="<?= $e($t('orders.search_placeholder')) ?>">
        </div>
        <div>
            <label class="form-label" for="f-date"><?= $e($t('orders.pickup_date')) ?></label>
            <select id="f-date" class="form-select" name="pickup_date">
                <option value=""><?= $e($t('orders.all_dates')) ?></option>
                <?php foreach ($availableDates as $date): ?>
                    <option value="<?= $e($date) ?>" <?= $filters['pickup_date'] === $date ? 'selected' : '' ?>><?= $e($date) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="f-status"><?= $e($t('orders.col_status')) ?></label>
            <select id="f-status" class="form-select" name="status">
                <option value=""><?= $e($t('orders.all_statuses')) ?></option>
                <?php foreach (\App\Models\Order::STATUSES as $st): ?>
                    <option value="<?= $e($st) ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= $e($t('status.' . $st)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="f-pay"><?= $e($t('orders.col_payment')) ?></label>
            <select id="f-pay" class="form-select" name="payment_status">
                <option value=""><?= $e($t('orders.all_payments')) ?></option>
                <?php foreach (\App\Models\Order::PAYMENT_STATUSES as $pst): ?>
                    <option value="<?= $e($pst) ?>" <?= $filters['payment_status'] === $pst ? 'selected' : '' ?>><?= $e($t('payment.' . $pst)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm"><?= $e($t('orders.filter_btn')) ?></button>
            <a href="<?= $e($url('/app/orders')) ?>" class="btn btn-outline btn-sm"><?= $e($t('orders.reset_btn')) ?></a>
        </div>
    </form>

    <?php if ($orders === []): ?>
        <div class="card">
            <p style="margin:0;color:var(--ink-600);"><?= $e($t('dash.no_orders_yet')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= $e($t('orders.col_number')) ?></th>
                        <th><?= $e($t('orders.col_customer')) ?></th>
                        <th><?= $e($t('orders.col_pickup')) ?></th>
                        <th><?= $e($t('orders.col_items')) ?></th>
                        <th><?= $e($t('orders.col_total')) ?></th>
                        <th><?= $e($t('orders.col_status')) ?> / <?= $e($t('orders.col_payment')) ?></th>
                        <th><?= $e($t('orders.col_actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?= $e($order->orderNumber) ?></strong>
                                <?php if ($order->isDemo): ?>
                                    <div><span class="badge badge-gold" style="margin-top:4px;"><?= $e($t('orders.demo_tag')) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= $e($order->customerName) ?></strong><br>
                                <small style="color:var(--ink-600);"><?= $e($order->customerPhone) ?></small>
                            </td>
                            <td>
                                <strong><?= $e($order->pickupDate) ?></strong><br>
                                <small style="color:var(--ink-600);"><?= $e($order->pickupSlot) ?></small>
                            </td>
                            <td>
                                <?= $e($order->itemsSummary()) ?>
                                <?php if ($order->notes !== ''): ?>
                                    <div style="margin-top:4px;font-size:0.82rem;color:var(--warn-ink);">Note: <?= $e($order->notes) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>€ <?= $e(number_format($order->totalAmount, 2, ',', '.')) ?></strong>
                                <?php if ($order->remainingBalance() > 0): ?>
                                    <div><small style="color:var(--ink-600);">Saldo: € <?= $e(number_format($order->remainingBalance(), 2, ',', '.')) ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?= $e($url('/app/orders/' . $order->id . '/status')) ?>" style="display:grid;gap:6px;min-width:155px;">
                                    <?= $csrfField() ?>
                                    <select name="status" class="form-select" style="padding:5px 8px;font-size:0.82rem;" data-autosubmit>
                                        <?php foreach (\App\Models\Order::STATUSES as $st): ?>
                                            <option value="<?= $e($st) ?>" <?= $order->status === $st ? 'selected' : '' ?>><?= $e($t('status.' . $st)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="payment_status" class="form-select" style="padding:5px 8px;font-size:0.82rem;" data-autosubmit>
                                        <?php foreach (\App\Models\Order::PAYMENT_STATUSES as $pst): ?>
                                            <option value="<?= $e($pst) ?>" <?= $order->paymentStatus === $pst ? 'selected' : '' ?>><?= $e($t('payment.' . $pst)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="<?= $e($url('/app/orders/' . $order->id . '/edit')) ?>" class="btn btn-outline btn-sm"><?= $e($t('orders.edit')) ?></a>
                                    <a href="<?= $e($url('/app/orders/' . $order->id . '/print')) ?>" target="_blank" class="btn btn-outline btn-sm"><?= $e($t('orders.print_btn')) ?></a>
                                    <form method="post" action="<?= $e($url('/app/orders/' . $order->id . '/delete')) ?>" style="margin:0;">
                                        <?= $csrfField() ?>
                                        <button type="submit" class="btn btn-danger btn-sm" data-confirm="<?= $e($t('orders.confirm_delete')) ?>"><?= $e($t('orders.delete_btn')) ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
