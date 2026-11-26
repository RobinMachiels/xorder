<?php

declare(strict_types=1);

/**
 * @var array<int, \App\Models\Product> $products
 * @var callable $e
 * @var callable $t
 * @var callable $url
 * @var callable $csrfField
 */
?>
<div class="container app-shell">
    <div class="page-top">
        <div>
            <h1><?= $e($t('products.title')) ?></h1>
            <p style="margin:0;color:var(--ink-600);"><?= $e($t('products.subtitle')) ?></p>
        </div>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <h2 style="margin:0 0 14px;font-size:1.15rem;"><?= $e($t('products.add_title')) ?></h2>
        <form method="post" action="<?= $e($url('/app/products')) ?>" style="display:grid;grid-template-columns:2fr 1.2fr 1fr 1fr auto;gap:10px;align-items:end;">
            <?= $csrfField() ?>
            <div>
                <label class="form-label" for="p-name"><?= $e($t('products.name')) ?> *</label>
                <input id="p-name" class="form-control" type="text" name="name" required>
            </div>
            <div>
                <label class="form-label" for="p-cat"><?= $e($t('products.category')) ?></label>
                <input id="p-cat" class="form-control" type="text" name="category" placeholder="Hoofdgerecht">
            </div>
            <div>
                <label class="form-label" for="p-unit"><?= $e($t('products.unit')) ?></label>
                <input id="p-unit" class="form-control" type="text" name="unit" value="stuk">
            </div>
            <div>
                <label class="form-label" for="p-price"><?= $e($t('products.price')) ?></label>
                <input id="p-price" class="form-control" type="number" step="0.01" min="0" name="price" value="0.00">
            </div>
            <button type="submit" class="btn btn-primary"><?= $e($t('products.add_btn')) ?></button>
        </form>
    </div>

    <?php if ($products === []): ?>
        <div class="card">
            <p style="margin:0;color:var(--ink-600);"><?= $e($t('products.empty')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= $e($t('products.name')) ?></th>
                        <th><?= $e($t('products.category')) ?></th>
                        <th><?= $e($t('products.unit')) ?></th>
                        <th><?= $e($t('products.price')) ?></th>
                        <th><?= $e($t('products.active')) ?></th>
                        <th><?= $e($t('orders.col_actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): ?>
                        <tr>
                            <td colspan="6" style="padding:8px 12px;">
                                <form method="post" action="<?= $e($url('/app/products/' . $prod->id . '/update')) ?>" style="display:grid;grid-template-columns:2fr 1.2fr 0.9fr 0.9fr 0.8fr auto;gap:8px;align-items:center;">
                                    <?= $csrfField() ?>
                                    <input class="form-control" type="text" name="name" value="<?= $e($prod->name) ?>" required>
                                    <input class="form-control" type="text" name="category" value="<?= $e($prod->category) ?>">
                                    <input class="form-control" type="text" name="unit" value="<?= $e($prod->unit) ?>">
                                    <input class="form-control" type="number" step="0.01" min="0" name="price" value="<?= $e(number_format($prod->price, 2, '.', '')) ?>">
                                    <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;">
                                        <input type="checkbox" name="is_active" value="1" <?= $prod->isActive ? 'checked' : '' ?>>
                                        <span><?= $e($t('products.active')) ?></span>
                                    </label>
                                    <div style="display:flex;gap:6px;">
                                        <button type="submit" class="btn btn-outline btn-sm"><?= $e($t('products.save_btn')) ?></button>
                                        <button
                                            type="submit"
                                            formaction="<?= $e($url('/app/products/' . $prod->id . '/delete')) ?>"
                                            class="btn btn-danger btn-sm"
                                            data-confirm="<?= $e($t('products.confirm_delete')) ?>"
                                        ><?= $e($t('products.delete_btn')) ?></button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
