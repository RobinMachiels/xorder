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
            <h1 style="margin:0 0 6px;font-size:1.55rem;"><?= $e($t('auth.login_title')) ?></h1>
            <p style="margin:0 0 22px;color:var(--ink-600);"><?= $e($t('auth.login_subtitle')) ?></p>

            <form method="post" action="<?= $e($url('/login')) ?>">
                <?= $csrfField() ?>

                <div class="form-group">
                    <label class="form-label" for="login-email"><?= $e($t('auth.email')) ?></label>
                    <input class="form-control" id="login-email" type="email" name="email" required placeholder="<?= $e($t('auth.email_placeholder')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="login-password"><?= $e($t('auth.password')) ?></label>
                    <input class="form-control" id="login-password" type="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:6px;"><?= $e($t('auth.login_btn')) ?></button>
            </form>

            <p style="margin:18px 0 0;font-size:0.9rem;color:var(--ink-600);text-align:center;">
                <?= $e($t('auth.no_account')) ?>
                <a href="<?= $e($url('/register')) ?>" style="font-weight:700;"><?= $e($t('auth.register_btn')) ?></a>
            </p>
        </div>
    </div>
</section>
