<?php

declare(strict_types=1);

/**
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
                <img src="<?= $e($url('/assets/img/xorder-mark.svg')) ?>" alt="" width="42" height="42">
                <span class="brand-wordmark">
                    <span class="brand-name" style="font-size:1.22rem;"><?= $e($t('brand.name')) ?></span>
                    <span class="brand-sub"><?= $e($t('brand.by')) ?></span>
                </span>
            </div>
            <h1 style="margin:0 0 6px;font-size:1.55rem;"><?= $e($t('auth.forgot_title')) ?></h1>
            <p style="margin:0 0 22px;color:var(--ink-600);"><?= $e($t('auth.forgot_subtitle')) ?></p>

            <form method="post" action="<?= $e($url('/forgot')) ?>">
                <?= $csrfField() ?>
                <div class="form-group">
                    <label class="form-label" for="forgot-email"><?= $e($t('auth.email')) ?></label>
                    <input class="form-control" id="forgot-email" type="email" name="email" required placeholder="<?= $e($t('auth.email_placeholder')) ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;"><?= $e($t('auth.forgot_btn')) ?></button>
            </form>

            <a class="form-link" href="<?= $e($url('/login')) ?>"><?= $e($t('auth.back_to_login')) ?></a>
        </div>
    </div>
</section>
