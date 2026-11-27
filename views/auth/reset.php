<?php

declare(strict_types=1);

/**
 * @var string $token
 * @var callable $e
 * @var callable $t
 * @var callable $url
 * @var callable $csrfField
 */
?>
<section class="section">
    <div class="container" style="max-width:460px;">
        <div class="card" style="box-shadow:var(--shadow-md);">
            <div class="auth-card-brand">
                <img src="<?= $e($assetUrl('/assets/img/xorder-mark.svg')) ?>" alt="" width="42" height="42">
                <span class="brand-wordmark">
                    <span class="brand-name" style="font-size:1.22rem;"><?= $e($t('brand.name')) ?></span>
                    <span class="brand-sub"><?= $e($t('brand.by')) ?></span>
                </span>
            </div>
            <h1 style="margin:0 0 18px;font-size:1.55rem;"><?= $e($t('auth.reset_title')) ?></h1>

            <form method="post" action="<?= $e($url('/reset', ['token' => $token])) ?>">
                <?= $csrfField() ?>
                <div class="form-group">
                    <label class="form-label" for="reset-pw"><?= $e($t('auth.password')) ?> <small style="font-weight:500;color:var(--ink-400);">(<?= $e($t('auth.password_hint')) ?>)</small></label>
                    <input class="form-control" id="reset-pw" type="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="reset-pw2"><?= $e($t('auth.password_confirm')) ?></label>
                    <input class="form-control" id="reset-pw2" type="password" name="password_confirm" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;"><?= $e($t('auth.reset_btn')) ?></button>
            </form>
        </div>
    </div>
</section>
