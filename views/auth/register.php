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
    <div class="container" style="max-width:520px;">
        <div class="card" style="box-shadow:var(--shadow-md);">
            <span class="badge badge-gold"><?= $e($t('landing.badge')) ?></span>
            <h1 style="margin:12px 0 6px;font-size:1.55rem;"><?= $e($t('auth.register_title')) ?></h1>
            <p style="margin:0 0 22px;color:var(--ink-600);"><?= $e($t('auth.register_subtitle')) ?></p>

            <form method="post" action="<?= $e($url('/register')) ?>">
                <?= $csrfField() ?>
                <input type="hidden" name="_source" value="register">

                <div class="form-group">
                    <label class="form-label" for="auth-reg-business"><?= $e($t('auth.business_name')) ?></label>
                    <input class="form-control" id="auth-reg-business" type="text" name="business_name" required placeholder="<?= $e($t('auth.business_placeholder')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="auth-reg-sector"><?= $e($t('auth.sector')) ?></label>
                    <select class="form-select" id="auth-reg-sector" name="sector">
                        <option value="traiteur"><?= $e($t('auth.sector_traiteur')) ?></option>
                        <option value="slager"><?= $e($t('auth.sector_slager')) ?></option>
                        <option value="bakker"><?= $e($t('auth.sector_bakker')) ?></option>
                        <option value="vishandel"><?= $e($t('auth.sector_vishandel')) ?></option>
                        <option value="other"><?= $e($t('auth.sector_other')) ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="auth-reg-email"><?= $e($t('auth.email')) ?></label>
                    <input class="form-control" id="auth-reg-email" type="email" name="email" required placeholder="<?= $e($t('auth.email_placeholder')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="auth-reg-password"><?= $e($t('auth.password')) ?> <small style="font-weight:500;color:var(--ink-400);">(<?= $e($t('auth.password_hint')) ?>)</small></label>
                    <input class="form-control" id="auth-reg-password" type="password" name="password" required minlength="6">
                </div>

                <label class="checkbox-row">
                    <input type="checkbox" name="seed_demo" value="1" checked>
                    <span><?= $e($t('auth.seed_demo')) ?></span>
                </label>

                <button type="submit" class="btn btn-primary" style="width:100%;"><?= $e($t('auth.register_btn')) ?></button>
            </form>

            <p style="margin:18px 0 0;font-size:0.9rem;color:var(--ink-600);text-align:center;">
                <?= $e($t('auth.has_account')) ?>
                <a href="<?= $e($url('/login')) ?>" style="font-weight:700;"><?= $e($t('auth.login_btn')) ?></a>
            </p>
        </div>
    </div>
</section>
