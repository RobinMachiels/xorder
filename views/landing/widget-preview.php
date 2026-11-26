<?php

declare(strict_types=1);

/**
 * @var array{startDate: string, endDate: string, position: string, dismissDays: int} $widgetConfig
 * @var string $locale
 * @var callable $e
 * @var callable $t
 * @var callable $url
 */
?>
<!DOCTYPE html>
<html lang="<?= $e($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($t('widget_preview.title')) ?> | AssurIO</title>
    <link rel="stylesheet" href="<?= $e($url('/widget/assurio-xorder-widget.css')) ?>">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #16202a;
            background: #f5f7fa;
            line-height: 1.55;
        }
        .preview-toolbar {
            background: #0c211b;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 2px solid #c88a2e;
            position: sticky;
            top: 0;
            z-index: 9999;
        }
        .preview-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }
        .preview-controls select, .preview-controls button, .preview-controls a {
            padding: 6px 10px;
            border-radius: 7px;
            border: 1px solid rgba(255,255,255,0.25);
            background: #14332b;
            color: #fff;
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .preview-controls a.cta-link {
            background: #c88a2e;
            border-color: #c88a2e;
        }
        .mock-nav {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 18px 24px;
        }
        .mock-container {
            max-width: 1080px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .mock-logo {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f2942;
            letter-spacing: -0.02em;
        }
        .mock-logo span { color: #2563eb; }
        .mock-links {
            display: flex;
            gap: 20px;
            color: #475569;
            font-weight: 600;
            font-size: 0.92rem;
        }
        .mock-hero {
            background: linear-gradient(135deg, #0f2942, #1b436b);
            color: #fff;
            padding: 68px 24px;
            text-align: center;
        }
        .mock-hero h1 {
            margin: 0 0 12px;
            font-size: clamp(1.8rem, 3vw, 2.5rem);
        }
        .mock-hero p {
            max-width: 680px;
            margin: 0 auto;
            opacity: 0.88;
        }
        .mock-section {
            max-width: 1080px;
            margin: 40px auto;
            padding: 0 24px 80px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .mock-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 24px;
        }
        .mock-card h2 {
            margin: 0 0 8px;
            font-size: 1.18rem;
            color: #0f2942;
        }
        .mock-card p {
            margin: 0;
            color: #475569;
            font-size: 0.93rem;
        }
        .mock-status-pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            background: rgba(200, 138, 46, 0.25);
            color: #f8d48b;
        }
        @media (max-width: 820px) {
            .preview-toolbar { position: static; }
            .mock-section { grid-template-columns: 1fr; }
            .mock-links { display: none; }
        }
    </style>
</head>
<body>

<div class="preview-toolbar">
    <div>
        <strong><?= $e($t('widget_preview.bar_title')) ?></strong>
        <span id="preview-status-badge" class="mock-status-pill" style="margin-left:8px;">Actief</span>
    </div>
    <div class="preview-controls">
        <label>
            <?= $e($t('widget_preview.lang_label')) ?>:
            <select id="ctrl-lang">
                <option value="nl" <?= $locale === 'nl' ? 'selected' : '' ?>>NL</option>
                <option value="fr" <?= $locale === 'fr' ? 'selected' : '' ?>>FR</option>
            </select>
        </label>
        <label>
            <?= $e($t('widget_preview.pos_label')) ?>:
            <select id="ctrl-pos">
                <option value="bottom-right"><?= $e($t('widget_preview.pos_right')) ?></option>
                <option value="bottom-left"><?= $e($t('widget_preview.pos_left')) ?></option>
            </select>
        </label>
        <label>
            <?= $e($t('widget_preview.season_label')) ?>:
            <select id="ctrl-season">
                <option value="active"><?= $e($t('widget_preview.season_active')) ?></option>
                <option value="off"><?= $e($t('widget_preview.season_off')) ?></option>
            </select>
        </label>
        <button type="button" id="ctrl-reset"><?= $e($t('widget_preview.reset_dismiss')) ?></button>
        <a href="<?= $e($url('/')) ?>" class="cta-link"><?= $e($t('widget_preview.open_landing')) ?></a>
    </div>
</div>

<div class="mock-nav">
    <div class="mock-container">
        <div class="mock-logo">Assur<span>IO</span>.be <small style="font-weight:500;color:#64748b;font-size:0.78rem;">(Schermpreview)</small></div>
        <div class="mock-links">
            <span>AssurIO Cloud</span>
            <span>AssurIO Pulse</span>
            <span>Maatwerk</span>
            <span>Contact</span>
        </div>
    </div>
</div>

<section class="mock-hero">
    <h1>Slimme Belgische software voor makelaars en kmo's</h1>
    <p>Simulatie van de assurio.be homepage. Rechtsonder (of linksonder) zweeft de tijdelijke seizoensknop voor XOrder zonder de hoofdnavigatie van AssurIO te belasten.</p>
</section>

<section class="mock-section">
    <div class="mock-card">
        <h2>AssurIO Cloud</h2>
        <p>Schadedossiers digitaliseren en beheren voor de zelfstandige verzekeringsmakelaar, inclusief selfservice en WhatsApp-integratie.</p>
    </div>
    <div class="mock-card">
        <h2>AssurIO Pulse</h2>
        <p>Eenvoudige tijdsregistratie voor Belgische kmo's zonder overbodige bureaucratie.</p>
    </div>
    <div class="mock-card">
        <h2>Software op maat</h2>
        <p>Webapplicaties, API-koppelingen en digitale workflows die echte rompslomp uit je dagelijkse werking halen.</p>
    </div>
</section>

<script>
window.AssurioXOrderConfig = {
    startDate: <?= json_encode($widgetConfig['startDate']) ?>,
    endDate: <?= json_encode($widgetConfig['endDate']) ?>,
    targetUrl: <?= json_encode($url('/')) ?>,
    lang: <?= json_encode($locale) ?>,
    position: <?= json_encode($widgetConfig['position']) ?>,
    dismissDays: <?= (int) $widgetConfig['dismissDays'] ?>,
    forceShow: false,
    track: false
};
</script>
<script src="<?= $e($url('/widget/assurio-xorder-widget.js')) ?>"></script>
<script>
(function () {
    var langSel = document.getElementById('ctrl-lang');
    var posSel = document.getElementById('ctrl-pos');
    var seasonSel = document.getElementById('ctrl-season');
    var resetBtn = document.getElementById('ctrl-reset');
    var badge = document.getElementById('preview-status-badge');

    function applyPreview() {
        var outside = seasonSel.value === 'off';
        var mounted = window.AssurioXOrderWidget.mount({
            lang: langSel.value,
            position: posSel.value,
            targetUrl: <?= json_encode($url('/')) ?>,
            forceShow: !outside,
            track: false,
            simulateOutsideSeason: outside
        });
        badge.textContent = mounted ? 'Zichtbaar' : 'Verborgen';
    }

    langSel.addEventListener('change', applyPreview);
    posSel.addEventListener('change', applyPreview);
    seasonSel.addEventListener('change', applyPreview);
    resetBtn.addEventListener('click', function () {
        window.AssurioXOrderWidget.resetDismiss();
        seasonSel.value = 'active';
        applyPreview();
    });
})();
</script>
</body>
</html>
