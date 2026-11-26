<?php

declare(strict_types=1);

/**
 * @var \App\Models\User|null $user
 * @var callable $e
 * @var callable $t
 * @var callable $url
 * @var callable $absUrl
 * @var callable $csrfField
 */

$shareLink = $absUrl('/');
$whatsappHref = 'https://wa.me/?text=' . rawurlencode($t('landing.share_whatsapp_text') . ' ' . $shareLink);
$mailHref = 'mailto:?subject=' . rawurlencode($t('landing.share_email_subject')) . '&body=' . rawurlencode($t('landing.share_whatsapp_text') . "\n\n" . $shareLink);
?>

<section class="hero-section">
    <div class="container">
        <?php if ($user !== null): ?>
            <div class="alert alert-info" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
                <span><?= $e($t('landing.already_logged_in', ['business' => $user->businessName])) ?></span>
                <a href="<?= $e($url('/app')) ?>" class="btn btn-primary btn-sm"><?= $e($t('landing.go_to_app')) ?></a>
            </div>
        <?php endif; ?>

        <div class="hero-grid">
            <div>
                <span class="badge badge-gold"><?= $e($t('landing.badge')) ?></span>
                <h1 class="hero-title"><?= $e($t('landing.hero_title')) ?></h1>
                <p class="hero-subtitle"><?= $e($t('landing.hero_subtitle')) ?></p>

                <div class="hero-cta-row">
                    <a href="#registreren" class="btn btn-primary"><?= $e($t('landing.hero_cta_primary')) ?></a>
                    <a href="#voordelen" class="btn btn-outline"><?= $e($t('landing.hero_cta_secondary')) ?></a>
                </div>

                <ul class="trust-list">
                    <li><?= $e($t('landing.hero_trust_1')) ?></li>
                    <li><?= $e($t('landing.hero_trust_2')) ?></li>
                    <li><?= $e($t('landing.hero_trust_3')) ?></li>
                    <li><?= $e($t('landing.hero_trust_4')) ?></li>
                </ul>
            </div>

            <div class="mockup-card" aria-label="Product preview">
                <div class="mockup-head">
                    <span><?= $e($t('landing.preview_title')) ?></span>
                    <span class="badge badge-gold"><?= $e($t('landing.preview_orders_count')) ?></span>
                </div>
                <div class="mockup-body">
                    <div class="mockup-row">
                        <div>
                            <strong><?= $e($t('landing.preview_row_1_name')) ?></strong>
                            <small><?= $e($t('landing.preview_row_1_items')) ?></small>
                        </div>
                        <span class="badge badge-success"><?= $e($t('landing.preview_row_1_status')) ?></span>
                    </div>
                    <div class="mockup-row">
                        <div>
                            <strong><?= $e($t('landing.preview_row_2_name')) ?></strong>
                            <small><?= $e($t('landing.preview_row_2_items')) ?></small>
                        </div>
                        <span class="badge badge-info"><?= $e($t('landing.preview_row_2_status')) ?></span>
                    </div>
                    <div class="mockup-row">
                        <div>
                            <strong><?= $e($t('landing.preview_row_3_name')) ?></strong>
                            <small><?= $e($t('landing.preview_row_3_items')) ?></small>
                        </div>
                        <span class="badge badge-warn"><?= $e($t('landing.preview_row_3_status')) ?></span>
                    </div>
                </div>
                <div class="mockup-footer">
                    <small style="color:var(--ink-600);font-weight:600;"><?= $e($t('landing.preview_badge')) ?></small>
                    <a href="#registreren" class="btn btn-gold btn-sm"><?= $e($t('landing.preview_excel_btn')) ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="voordelen" class="section section-alt">
    <div class="container">
        <div class="section-header">
            <div class="eyebrow"><?= $e($t('landing.promises_eyebrow')) ?></div>
            <h2><?= $e($t('landing.promises_title')) ?></h2>
        </div>

        <div class="grid-3">
            <div class="card">
                <span class="badge badge-pine">01</span>
                <h3 style="margin:12px 0 8px;"><?= $e($t('landing.promise_1_title')) ?></h3>
                <p style="margin:0;color:var(--ink-600);"><?= $e($t('landing.promise_1_desc')) ?></p>
            </div>
            <div class="card">
                <span class="badge badge-gold">02</span>
                <h3 style="margin:12px 0 8px;"><?= $e($t('landing.promise_2_title')) ?></h3>
                <p style="margin:0;color:var(--ink-600);"><?= $e($t('landing.promise_2_desc')) ?></p>
            </div>
            <div class="card">
                <span class="badge badge-pine">03</span>
                <h3 style="margin:12px 0 8px;"><?= $e($t('landing.promise_3_title')) ?></h3>
                <p style="margin:0;color:var(--ink-600);"><?= $e($t('landing.promise_3_desc')) ?></p>
            </div>
        </div>
    </div>
</section>

<section id="excel-backup" class="section">
    <div class="container">
        <div class="card card-pine">
            <div class="grid-2" style="align-items:center;">
                <div>
                    <div class="eyebrow" style="color:#f5c571;"><?= $e($t('landing.excel_eyebrow')) ?></div>
                    <h2 style="margin:0 0 12px;font-size:1.85rem;"><?= $e($t('landing.excel_title')) ?></h2>
                    <p style="margin:0;opacity:0.9;"><?= $e($t('landing.excel_desc')) ?></p>
                </div>
                <div>
                    <ul class="sheet-list">
                        <li><?= $e($t('landing.excel_sheet_1')) ?></li>
                        <li><?= $e($t('landing.excel_sheet_2')) ?></li>
                        <li><?= $e($t('landing.excel_sheet_3')) ?></li>
                        <li><?= $e($t('landing.excel_sheet_4')) ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="waarom-gratis" class="section section-alt">
    <div class="container">
        <div class="section-header">
            <div class="eyebrow"><?= $e($t('landing.free_eyebrow')) ?></div>
            <h2><?= $e($t('landing.free_title')) ?></h2>
            <p style="color:var(--ink-600);margin:0;"><?= $e($t('landing.free_desc')) ?></p>
        </div>

        <div class="grid-3">
            <div class="card">
                <h3 style="margin-top:0;"><?= $e($t('landing.free_point_1_title')) ?></h3>
                <p style="margin:0;color:var(--ink-600);"><?= $e($t('landing.free_point_1_desc')) ?></p>
            </div>
            <div class="card">
                <h3 style="margin-top:0;"><?= $e($t('landing.free_point_2_title')) ?></h3>
                <p style="margin:0;color:var(--ink-600);"><?= $e($t('landing.free_point_2_desc')) ?></p>
            </div>
            <div class="card" style="border-color:#e4c791;background:#fffdf8;">
                <h3 style="margin-top:0;color:var(--gold-600);"><?= $e($t('landing.free_point_3_title')) ?></h3>
                <p style="margin:0;color:var(--ink-600);"><?= $e($t('landing.free_point_3_desc')) ?></p>
            </div>
        </div>
    </div>
</section>

<section id="registreren" class="section">
    <div class="container">
        <div class="grid-2" style="align-items:start;">
            <div class="card" style="box-shadow:var(--shadow-md);">
                <span class="badge badge-gold"><?= $e($t('landing.badge')) ?></span>
                <h2 style="margin:12px 0 6px;"><?= $e($t('landing.reg_title')) ?></h2>
                <p style="color:var(--ink-600);margin:0 0 20px;"><?= $e($t('landing.reg_subtitle')) ?></p>

                <form method="post" action="<?= $e($url('/register')) ?>">
                    <?= $csrfField() ?>
                    <input type="hidden" name="_source" value="landing">

                    <div class="form-group">
                        <label class="form-label" for="reg-business"><?= $e($t('auth.business_name')) ?></label>
                        <input class="form-control" id="reg-business" type="text" name="business_name" required placeholder="<?= $e($t('auth.business_placeholder')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-sector"><?= $e($t('auth.sector')) ?></label>
                        <select class="form-select" id="reg-sector" name="sector">
                            <option value="traiteur"><?= $e($t('auth.sector_traiteur')) ?></option>
                            <option value="slager"><?= $e($t('auth.sector_slager')) ?></option>
                            <option value="bakker"><?= $e($t('auth.sector_bakker')) ?></option>
                            <option value="vishandel"><?= $e($t('auth.sector_vishandel')) ?></option>
                            <option value="other"><?= $e($t('auth.sector_other')) ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-email"><?= $e($t('auth.email')) ?></label>
                        <input class="form-control" id="reg-email" type="email" name="email" required placeholder="<?= $e($t('auth.email_placeholder')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-password"><?= $e($t('auth.password')) ?> <small style="font-weight:500;color:var(--ink-400);">(<?= $e($t('auth.password_hint')) ?>)</small></label>
                        <input class="form-control" id="reg-password" type="password" name="password" required minlength="6">
                    </div>

                    <label class="checkbox-row">
                        <input type="checkbox" name="seed_demo" value="1" checked>
                        <span><?= $e($t('auth.seed_demo')) ?></span>
                    </label>

                    <button type="submit" class="btn btn-primary" style="width:100%;"><?= $e($t('landing.reg_submit')) ?></button>
                    <p style="margin:12px 0 0;font-size:0.84rem;color:var(--ink-400);text-align:center;"><?= $e($t('landing.reg_footnote')) ?></p>
                </form>
            </div>

            <div class="card" style="background:var(--cream-100);">
                <h3 style="margin-top:0;"><?= $e($t('landing.share_title')) ?></h3>
                <p style="color:var(--ink-600);"><?= $e($t('landing.share_desc')) ?></p>

                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:16px;">
                    <button
                        type="button"
                        class="btn btn-outline btn-sm"
                        data-copy-url="<?= $e($shareLink) ?>"
                        data-copied-label="<?= $e($t('landing.share_copied')) ?>"
                    ><?= $e($t('landing.share_copy_btn')) ?></button>
                    <a class="btn btn-primary btn-sm" href="<?= $e($whatsappHref) ?>" target="_blank" rel="noopener"><?= $e($t('landing.share_whatsapp')) ?></a>
                    <a class="btn btn-outline btn-sm" href="<?= $e($mailHref) ?>"><?= $e($t('landing.share_email')) ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
