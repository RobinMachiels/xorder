<?php

declare(strict_types=1);

/**
 * @var string $pageTitle
 * @var string $content
 * @var \App\Models\User|null $user
 * @var string $locale
 * @var array<int, string> $supportedLocales
 * @var array<int, array{type: string, message: string}> $flashes
 * @var string $currentPath
 * @var callable $e
 * @var callable $t
 * @var callable $url
 * @var callable $csrfField
 * @var array<int, string> $featureNames
 */
?>
<!DOCTYPE html>
<html lang="<?= $e($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($pageTitle) ?> | <?= $e($t('brand.full')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= $e($url('/assets/img/xorder-mark.svg')) ?>">
    <link rel="stylesheet" href="<?= $e($url('/assets/css/app.css')) ?>">
</head>
<body>
<header class="site-header no-print">
    <div class="container header-inner">
        <a href="<?= $e($url($user !== null ? '/app' : '/')) ?>" class="brand-mark">
            <svg class="brand-logo-svg" width="46" height="46" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                <defs>
                    <linearGradient id="xo-hdr-pine" x1="6" y1="4" x2="42" y2="44" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#1f4d41"/>
                        <stop offset="54%" stop-color="#14332b"/>
                        <stop offset="100%" stop-color="#0a1c17"/>
                    </linearGradient>
                    <radialGradient id="xo-hdr-glow" cx="50%" cy="42%" r="52%">
                        <stop offset="0%" stop-color="#e3ad4e" stop-opacity="0.18"/>
                        <stop offset="100%" stop-color="#14332b" stop-opacity="0"/>
                    </radialGradient>
                    <linearGradient id="xo-hdr-gold" x1="13" y1="37" x2="35" y2="11" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#b4761e"/>
                        <stop offset="48%" stop-color="#dfa13d"/>
                        <stop offset="100%" stop-color="#f7d68a"/>
                    </linearGradient>
                    <linearGradient id="xo-hdr-rim" x1="6" y1="4" x2="42" y2="44" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#f5ce7c" stop-opacity="0.72"/>
                        <stop offset="50%" stop-color="#c88a2e" stop-opacity="0.34"/>
                        <stop offset="100%" stop-color="#f5ce7c" stop-opacity="0.58"/>
                    </linearGradient>
                    <linearGradient id="xo-hdr-ivory" x1="14" y1="16" x2="34" y2="37" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#ffffff"/>
                        <stop offset="100%" stop-color="#e9e2d3"/>
                    </linearGradient>
                    <mask id="xo-hdr-weave" maskUnits="userSpaceOnUse" x="0" y="0" width="48" height="48">
                        <rect width="48" height="48" fill="#ffffff"/>
                        <path d="M 11.96 40.5 L 36.04 12.5" stroke="#000000" stroke-width="6.4" stroke-linecap="butt"/>
                    </mask>
                </defs>
                <rect x="2" y="2" width="44" height="44" rx="12" fill="url(#xo-hdr-pine)"/>
                <rect x="2" y="2" width="44" height="44" rx="12" fill="url(#xo-hdr-glow)"/>
                <rect x="2.5" y="2.5" width="43" height="43" rx="11.5" fill="none" stroke="#091814" stroke-opacity="0.35"/>
                <rect x="4.75" y="4.75" width="38.5" height="38.5" rx="9.25" fill="none" stroke="url(#xo-hdr-rim)" stroke-width="0.85"/>
                <g mask="url(#xo-hdr-weave)">
                    <path d="M13.4 16.5 H17.4 L34.6 36.5 H30.6 Z" fill="url(#xo-hdr-ivory)" stroke="url(#xo-hdr-ivory)" stroke-width="1.2" stroke-linejoin="round"/>
                </g>
                <path d="M13.4 36.5 H17.4 L34.6 16.5 H30.6 Z" fill="url(#xo-hdr-gold)" stroke="url(#xo-hdr-gold)" stroke-width="1.2" stroke-linejoin="round"/>
                <path d="M24 8.0 Q24.34 10.86 27.2 11.2 Q24.34 11.54 24 14.4 Q23.66 11.54 20.8 11.2 Q23.66 10.86 24 8.0 Z" fill="#f8d88e"/>
            </svg>
            <span class="brand-wordmark">
                <span class="brand-name"><?= $e($t('brand.name')) ?></span>
                <span class="brand-sub"><?= $e($t('brand.by')) ?></span>
            </span>
        </a>

        <nav class="main-nav">
            <?php if ($featureNames !== []): ?>
                <a class="nav-link nav-link-features <?= str_starts_with($currentPath, '/features') ? 'is-active' : '' ?>" href="<?= $e($url('/features')) ?>">Features</a>
            <?php endif; ?>
            <?php if ($user !== null): ?>
                <a class="nav-link <?= $currentPath === '/app' ? 'is-active' : '' ?>" href="<?= $e($url('/app')) ?>"><?= $e($t('nav.dashboard')) ?></a>
                <a class="nav-link <?= str_starts_with($currentPath, '/app/orders') ? 'is-active' : '' ?>" href="<?= $e($url('/app/orders')) ?>"><?= $e($t('nav.orders')) ?></a>
                <a class="nav-link <?= $currentPath === '/app/kitchen' ? 'is-active' : '' ?>" href="<?= $e($url('/app/kitchen')) ?>"><?= $e($t('nav.kitchen')) ?></a>
                <a class="nav-link <?= $currentPath === '/app/products' ? 'is-active' : '' ?>" href="<?= $e($url('/app/products')) ?>"><?= $e($t('nav.products')) ?></a>
                <a class="nav-link <?= $currentPath === '/app/settings' ? 'is-active' : '' ?>" href="<?= $e($url('/app/settings')) ?>"><?= $e($t('nav.settings')) ?></a>
            <?php else: ?>
                <a class="nav-link" href="<?= $e($url('/#voordelen')) ?>"><?= $e($t('nav.features')) ?></a>
                <a class="nav-link" href="<?= $e($url('/#excel-backup')) ?>"><?= $e($t('nav.excel')) ?></a>
                <a class="nav-link" href="<?= $e($url('/#waarom-gratis')) ?>"><?= $e($t('nav.why_free')) ?></a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <div class="lang-switch" aria-label="Language">
                <?php foreach ($supportedLocales as $loc): ?>
                    <a href="<?= $e($url($currentPath)) ?>?lang=<?= $e($loc) ?>" class="<?= $locale === $loc ? 'is-active' : '' ?>"><?= $e(strtoupper($loc)) ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($user !== null): ?>
                <a href="<?= $e($url('/app/export/xlsx')) ?>" class="btn btn-gold btn-sm"><?= $e($t('nav.backup_now')) ?></a>
                <form method="post" action="<?= $e($url('/logout')) ?>" style="margin:0;">
                    <?= $csrfField() ?>
                    <button type="submit" class="btn btn-outline btn-sm"><?= $e($t('nav.logout')) ?></button>
                </form>
            <?php else: ?>
                <a href="<?= $e($url('/login')) ?>" class="btn btn-outline btn-sm"><?= $e($t('nav.login')) ?></a>
                <a href="<?= $e($url('/#registreren')) ?>" class="btn btn-primary btn-sm"><?= $e($t('nav.register')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($flashes !== []): ?>
    <div class="container flash-wrap no-print">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= $e($flash['type'] === 'error' ? 'error' : ($flash['type'] === 'info' ? 'info' : 'success')) ?>">
                <?= $e($flash['message']) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main>
    <?= $content ?>
</main>

<footer class="site-footer no-print">
    <div class="container footer-inner">
        <div class="footer-brand">
            <img src="<?= $e($url('/assets/img/xorder-mark.svg')) ?>" alt="" width="26" height="26" class="footer-logo-mark">
            <span><strong><?= $e($t('brand.full')) ?></strong> · <?= $e($t('footer.tagline')) ?></span>
        </div>
        <div class="footer-links">
            <a href="<?= $e($url('/privacy')) ?>"><?= $e($t('footer.privacy')) ?></a>
            <a href="<?= $e($url('/cookies')) ?>"><?= $e($t('footer.cookies')) ?></a>
            <a href="<?= $e($url('/')) ?>"><?= $e($t('nav.back_to_site')) ?></a>
            <span><?= $e($t('footer.rights')) ?></span>
        </div>
    </div>
</footer>

<script src="<?= $e($url('/assets/js/app.js')) ?>"></script>
</body>
</html>
