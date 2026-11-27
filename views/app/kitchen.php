<?php

declare(strict_types=1);

/**
 * @var array<string, array<int, array{pickup_date: string, product_name: string, unit: string, total_qty: float, order_count: int}>> $grouped
 * @var string $selectedDate
 * @var array<int, string> $availableDates
 * @var callable $e
 * @var callable $t
 * @var callable $url
 */
?>
<div class="container app-shell">
    <div class="page-top">
        <div>
            <h1><?= $e($t('kitchen.title')) ?></h1>
            <p style="margin:0;color:var(--ink-600);"><?= $e($t('kitchen.subtitle')) ?></p>
        </div>
        <div class="no-print" style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="button" data-print class="btn btn-outline"><?= $e($t('kitchen.print')) ?></button>
            <a href="<?= $e($url('/app/export')) ?>" class="btn btn-gold"><?= $e($t('dash.backup_btn')) ?></a>
        </div>
    </div>

    <form method="get" action="<?= $e($url('/app/kitchen')) ?>" class="card no-print" style="padding:16px;margin-bottom:20px;display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <div style="min-width:240px;">
            <label class="form-label" for="k-date"><?= $e($t('kitchen.filter_date')) ?></label>
            <select id="k-date" class="form-select" name="pickup_date" data-autosubmit>
                <option value=""><?= $e($t('orders.all_dates')) ?></option>
                <?php foreach ($availableDates as $date): ?>
                    <option value="<?= $e($date) ?>" <?= $selectedDate === $date ? 'selected' : '' ?>><?= $e($date) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= $e($t('orders.filter_btn')) ?></button>
    </form>

    <?php if ($grouped === []): ?>
        <div class="card">
            <p style="margin:0;color:var(--ink-600);"><?= $e($t('kitchen.empty')) ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($grouped as $date => $rows): ?>
            <div class="card" style="margin-bottom:20px;">
                <h2 style="margin:0 0 14px;font-size:1.2rem;color:var(--pine-900);"><?= $e($date) ?></h2>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?= $e($t('orders.item_name')) ?></th>
                                <th><?= $e($t('orders.item_unit')) ?></th>
                                <th><?= $e($t('kitchen.total_qty')) ?></th>
                                <th><?= $e($t('kitchen.order_count')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><strong><?= $e($row['product_name']) ?></strong></td>
                                    <td><?= $e($row['unit']) ?></td>
                                    <td><strong><?= $e($row['total_qty']) ?></strong></td>
                                    <td><?= $e($row['order_count']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
