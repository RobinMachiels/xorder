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
 */
?>
<!DOCTYPE html>
<html lang="<?= $e($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($pageTitle) ?> | <?= $e($t('brand.full')) ?></title>
    <link rel="stylesheet" href="<?= $e($url('/assets/css/app.css')) ?>">
</head>
<body>
<header class="site-header no-print">
    <div class="container header-inner">
        <a href="<?= $e($url($user !== null ? '/app' : '/')) ?>" class="brand-mark">
            <span class="brand-icon">X</span>
            <span><?= $e($t('brand.name')) ?> <span class="brand-sub"><?= $e($t('brand.by')) ?></span></span>
        </a>

        <nav class="main-nav">
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
                <a class="nav-link" href="<?= $e($url('/widget-preview')) ?>"><?= $e($t('nav.widget_preview')) ?></a>
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
        <div>
            <strong><?= $e($t('brand.full')) ?></strong> · <?= $e($t('footer.tagline')) ?>
        </div>
        <div style="display:flex;gap:16px;align-items:center;">
            <a href="<?= $e($url('/widget-preview')) ?>"><?= $e($t('nav.widget_preview')) ?></a>
            <a href="<?= $e($url('/')) ?>"><?= $e($t('nav.back_to_site')) ?></a>
            <span><?= $e($t('footer.rights')) ?></span>
        </div>
    </div>
</footer>

<script src="<?= $e($url('/assets/js/app.js')) ?>"></script>
</body>
</html>
