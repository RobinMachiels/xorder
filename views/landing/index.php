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
$facebookHref = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareLink);
$linkedinHref = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($shareLink);
$xHref = 'https://x.com/intent/post?text=' . rawurlencode($t('landing.share_whatsapp_text')) . '&url=' . rawurlencode($shareLink);
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
        <div class="section-header section-header--wide">
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

            <div class="share-column">
                <div class="card share-card">
                    <h3 style="margin-top:0;"><?= $e($t('landing.share_title')) ?></h3>
                    <p style="color:var(--ink-600);"><?= $e($t('landing.share_desc')) ?></p>

                    <div class="share-actions">
                        <button
                            type="button"
                            class="btn btn-outline btn-sm"
                            data-copy-url="<?= $e($shareLink) ?>"
                            data-copied-label="<?= $e($t('landing.share_copied')) ?>"
                        >
                            <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 14a4.5 4.5 0 0 0 6.4 0l3.2-3.2a4.5 4.5 0 0 0-6.4-6.4l-1 1" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3.2 3.2a4.5 4.5 0 0 0 6.4 6.4l1-1" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span data-copy-text><?= $e($t('landing.share_copy_btn')) ?></span>
                        </button>
                        <a class="btn btn-primary btn-sm" href="<?= $e($whatsappHref) ?>" target="_blank" rel="noopener">
                            <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.04 21.5h-.01a9.45 9.45 0 0 1-4.82-1.32l-.35-.21-3.58.94.96-3.49-.23-.36a9.43 9.43 0 0 1-1.45-5.03c0-5.22 4.25-9.46 9.48-9.46 2.53 0 4.9.99 6.69 2.78a9.4 9.4 0 0 1 2.77 6.69c0 5.22-4.25 9.46-9.46 9.46zm8.05-17.52A11.32 11.32 0 0 0 12.04.65C5.77.65.66 5.75.66 12.03c0 2 .52 3.96 1.52 5.69L.57 23.6l6.02-1.58a11.36 11.36 0 0 0 5.44 1.39h.01c6.27 0 11.38-5.11 11.38-11.38 0-3.04-1.18-5.9-3.33-8.05z"/></svg>
                            <span><?= $e($t('landing.share_whatsapp')) ?></span>
                        </a>
                        <a class="btn btn-outline btn-sm" href="<?= $e($mailHref) ?>">
                            <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.9"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span><?= $e($t('landing.share_email')) ?></span>
                        </a>
                    </div>

                    <details class="share-socials" data-native-share data-share-title="<?= $e($t('brand.full')) ?>" data-share-text="<?= $e($t('landing.share_whatsapp_text')) ?>" data-share-url="<?= $e($shareLink) ?>">
                        <summary class="btn btn-gold share-socials-toggle">
                            <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="18" cy="5.5" r="2.6" fill="none" stroke="currentColor" stroke-width="1.9"/><circle cx="6" cy="12" r="2.6" fill="none" stroke="currentColor" stroke-width="1.9"/><circle cx="18" cy="18.5" r="2.6" fill="none" stroke="currentColor" stroke-width="1.9"/><path d="m8.3 10.8 7.4-4M8.3 13.2l7.4 4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                            <span><?= $e($t('landing.share_socials')) ?></span>
                        </summary>
                        <div class="share-socials-list">
                            <a class="share-social" href="<?= $e($facebookHref) ?>" target="_blank" rel="noopener" aria-label="<?= $e($t('landing.share_on', ['network' => 'Facebook'])) ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 21.5v-7.8h2.6l.4-3.1h-3v-2c0-.9.25-1.5 1.5-1.5h1.6V4.3a21 21 0 0 0-2.3-.12c-2.3 0-3.9 1.4-3.9 4v2.3H7.8v3.1h2.6v7.8z"/></svg>
                                <span>Facebook</span>
                            </a>
                            <a class="share-social" href="<?= $e($linkedinHref) ?>" target="_blank" rel="noopener" aria-label="<?= $e($t('landing.share_on', ['network' => 'LinkedIn'])) ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5.3 3.5a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM3.6 9h3.4v11.5H3.6zm5.6 0h3.3v1.6h.05c.46-.87 1.58-1.8 3.26-1.8 3.5 0 4.14 2.3 4.14 5.3v6.4h-3.4v-5.7c0-1.36-.03-3.1-1.9-3.1-1.9 0-2.18 1.48-2.18 3v5.8H9.2z"/></svg>
                                <span>LinkedIn</span>
                            </a>
                            <a class="share-social" href="<?= $e($xHref) ?>" target="_blank" rel="noopener" aria-label="<?= $e($t('landing.share_on', ['network' => 'X'])) ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.2 3.5h2.9l-6.4 7.3 7.5 9.7h-5.9l-4.6-6-5.3 6H2.5l6.8-7.8-7.2-9.2h6l4.2 5.5zm-1 15.3h1.6L7.9 5.1H6.2z"/></svg>
                                <span>X</span>
                            </a>
                        </div>
                    </details>
                </div>

                <figure class="mock-figure">
                    <div class="mock anim" data-observe>
                        <div class="mock__bar">
                            <span class="dots"><i></i><i></i><i></i></span>
                            <span class="mock__url">assurio.be/xorder/app/bestellingen</span>
                        </div>
                        <div class="mock__screen">
                            <svg viewBox="0 0 400 200" preserveAspectRatio="xMidYMid meet" role="img" aria-label="<?= $e($t('landing.mock_alt')) ?>">
                                <!-- statisch decor: zijbalk, titel, knoppen, tabelkop -->
                                <rect x="0" y="0" width="400" height="200" fill="var(--mock-bg)"/>
                                <rect x="0" y="0" width="74" height="200" fill="var(--mock-side)"/>
                                <rect x="12" y="14" width="16" height="16" rx="4" fill="var(--mock-gold)"/>
                                <rect x="34" y="19" width="28" height="6" rx="3" fill="var(--mock-side-ink)" opacity=".9"/>
                                <rect x="12" y="46" width="50" height="14" rx="4" fill="var(--mock-side-ink)" opacity=".16"/>
                                <rect x="18" y="51" width="30" height="4" rx="2" fill="var(--mock-side-ink)" opacity=".9"/>
                                <rect x="18" y="71" width="36" height="4" rx="2" fill="var(--mock-side-ink)" opacity=".45"/>
                                <rect x="18" y="87" width="26" height="4" rx="2" fill="var(--mock-side-ink)" opacity=".45"/>
                                <rect x="18" y="103" width="32" height="4" rx="2" fill="var(--mock-side-ink)" opacity=".45"/>

                                <rect x="90" y="16" width="96" height="9" rx="4" fill="var(--mock-ink)" opacity=".85"/>
                                <rect x="90" y="30" width="60" height="5" rx="2" fill="var(--mock-muted)" opacity=".7"/>
                                <rect x="270" y="16" width="52" height="20" rx="5" fill="var(--mock-bg)" stroke="var(--mock-line)"/>
                                <rect x="281" y="24" width="30" height="4" rx="2" fill="var(--mock-ok)"/>
                                <rect x="328" y="16" width="60" height="20" rx="5" fill="var(--mock-accent)"/>
                                <rect x="340" y="24" width="36" height="4" rx="2" fill="#fff"/>

                                <rect x="90" y="48" width="298" height="16" rx="4" fill="var(--mock-alt)"/>
                                <rect x="98" y="54" width="34" height="4" rx="2" fill="var(--mock-muted)" opacity=".7"/>
                                <rect x="196" y="54" width="30" height="4" rx="2" fill="var(--mock-muted)" opacity=".7"/>
                                <rect x="300" y="54" width="26" height="4" rx="2" fill="var(--mock-muted)" opacity=".7"/>

                                <!-- bestaande bestellingen, komen na elkaar binnen -->
                                <g data-anim style="--d:.10s">
                                    <rect x="90" y="70" width="298" height="24" rx="4" fill="var(--mock-panel)"/>
                                    <rect x="98" y="80" width="64" height="5" rx="2" fill="var(--mock-ink)" opacity=".6"/>
                                    <rect x="196" y="80" width="40" height="5" rx="2" fill="var(--mock-muted)" opacity=".6"/>
                                    <rect x="300" y="76" width="40" height="12" rx="6" fill="var(--mock-ok)" opacity=".18"/>
                                </g>
                                <g data-anim style="--d:.18s">
                                    <rect x="90" y="98" width="298" height="24" rx="4" fill="var(--mock-panel)"/>
                                    <rect x="98" y="108" width="52" height="5" rx="2" fill="var(--mock-ink)" opacity=".6"/>
                                    <rect x="196" y="108" width="48" height="5" rx="2" fill="var(--mock-muted)" opacity=".6"/>
                                    <rect x="300" y="104" width="40" height="12" rx="6" fill="var(--mock-gold)" opacity=".22"/>
                                </g>
                                <g data-anim style="--d:.26s">
                                    <rect x="90" y="126" width="298" height="24" rx="4" fill="var(--mock-panel)"/>
                                    <rect x="98" y="136" width="70" height="5" rx="2" fill="var(--mock-ink)" opacity=".6"/>
                                    <rect x="196" y="136" width="36" height="5" rx="2" fill="var(--mock-muted)" opacity=".6"/>
                                    <rect x="300" y="132" width="40" height="12" rx="6" fill="var(--mock-ok)" opacity=".18"/>
                                </g>

                                <!-- gevolg van klik 1: nieuwe feestbestelling verschijnt -->
                                <g class="mk-new">
                                    <rect x="90" y="154" width="298" height="24" rx="4" fill="var(--mock-gold)" opacity=".14"/>
                                    <rect x="90" y="154" width="3" height="24" rx="1.5" fill="var(--mock-gold)"/>
                                    <rect x="98" y="164" width="58" height="5" rx="2" fill="var(--mock-ink)" opacity=".75"/>
                                    <rect x="196" y="164" width="44" height="5" rx="2" fill="var(--mock-muted)" opacity=".7"/>
                                    <rect x="300" y="160" width="40" height="12" rx="6" fill="var(--mock-gold)"/>
                                    <rect x="308" y="164" width="24" height="4" rx="2" fill="#fff"/>
                                </g>

                                <!-- gevolg van klik 2: Excel-backup klaar -->
                                <g class="mk-toast">
                                    <rect x="268" y="40" width="120" height="22" rx="6" fill="var(--mock-ink)"/>
                                    <circle cx="280" cy="51" r="5" fill="var(--mock-ok)"/>
                                    <path d="M277.6 51.2 l1.6 1.6 l3.2 -3.4" fill="none" stroke="#fff" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                    <rect x="292" y="49" width="84" height="4" rx="2" fill="#fff" opacity=".85"/>
                                </g>

                                <!-- de cursor, altijd als laatste -->
                                <g class="cur">
                                    <g class="press">
                                        <circle class="rip" r="12"/>
                                        <path class="ptr" d="M0 0 L0 15 L4 11.6 L6.9 18 L10 16.6 L7.1 10.4 L11.8 9.6 Z"/>
                                    </g>
                                </g>
                            </svg>
                        </div>
                    </div>
                    <figcaption class="mock-caption"><?= $e($t('landing.mock_caption')) ?></figcaption>
                </figure>
            </div>
        </div>
    </div>
</section>
