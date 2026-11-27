<?php

declare(strict_types=1);

/**
 * Feature van wesley1: feestelijke menukaart voor de feestdagen.
 * Url: /wesley1.php   Assets: public/features/wesley1/ (style.css, app.js)
 *
 * Dit bestand heeft twee delen:
 *  1. OPSTART EN ACTIES: bouwt de app op (zoals public/boot.php, maar zonder de router),
 *     controleert de login en verwerkt POST-formulieren.
 *  2. SJABLOON: de HTML. Die wordt via $app->view->renderFile() getoond, zodat de standaard
 *     helpers beschikbaar zijn: $e(...), $url(...), $csrfField(), en de XOrder-layout errond komt.
 *
 * Regels: eigen tabellen beginnen met wesley1_, CSS-klassen met .wesley1-,
 * alles gefilterd op $user->id, de tabel products wordt enkel gelezen.
 */

use App\Core\App;
use App\Core\Response;
use App\Repositories\UserRepository;

// ============================================================================
// 1. OPSTART EN ACTIES (enkel bij het eerste laden, niet tijdens het renderen)
// ============================================================================
if (!isset($wesley1Sjabloon)) {
    // Webroot en app-map bepalen, net als public/boot.php
    define('XORDER_PUBLIC_DIR', __DIR__);
    $rootDir = dirname(__DIR__);
    if (is_file(__DIR__ . '/xorder-path.php')) {
        $rootDir = (string) require __DIR__ . '/xorder-path.php';
    }
    require_once $rootDir . '/src/autoload.php';

    try {
        $app = new App($rootDir);
        $request = $app->request;

        // $url('/login') geeft '<base>/login.php', $url('/wesley1') geeft '<base>/wesley1.php'
        $url = static fn (string $path = '/', array $query = []): string => $request->url($path, $query);
        $paginaUrl = $url('/wesley1');

        // Enkel voor een ingelogde handelaar
        $userId = $app->session->userId();
        $user = $userId !== null ? (new UserRepository($app->db))->findById($userId) : null;
        if ($user === null) {
            Response::redirect($url('/login'))->send();
            exit;
        }

        // Eigen tabellen (worden maar 1 keer aangemaakt). Prijs zoals products.price: REAL in euro.
        $app->db->pdo()->exec(
            "CREATE TABLE IF NOT EXISTS wesley1_menu (
                user_id    INTEGER PRIMARY KEY,
                titel      TEXT NOT NULL DEFAULT 'Feestmenu',
                ondertitel TEXT NOT NULL DEFAULT '',
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
        $app->db->pdo()->exec(
            "CREATE TABLE IF NOT EXISTS wesley1_menu_items (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id      INTEGER NOT NULL,
                product_id   INTEGER NULL,
                naam         TEXT NOT NULL,
                omschrijving TEXT NOT NULL DEFAULT '',
                prijs        REAL NOT NULL DEFAULT 0,
                volgorde     INTEGER NOT NULL DEFAULT 0,
                created_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );

        // Producten van deze handelaar: enkel LEZEN uit products, altijd gefilterd op user_id, enkel actieve.
        $actieveProducten = static function () use ($app, $user): array {
            return $app->db->fetchAll(
                'SELECT id, name, category, unit, price FROM products
                 WHERE user_id = ? AND is_active = 1 ORDER BY category ASC, name ASC',
                [$user->id]
            );
        };
        // Omschrijving voor op de kaart: products heeft geen omschrijving, dus categorie + eenheid (bv. "Hoofdgerecht · per pers.")
        $omschrijvingVoor = static function (array $product): string {
            $delen = array_filter([
                trim((string) $product['category']),
                trim((string) $product['unit']) !== '' ? 'per ' . trim((string) $product['unit']) : '',
            ], static fn (string $deel): bool => $deel !== '');

            return implode(' · ', $delen);
        };
        // Volgende plaats onderaan de kaart
        $volgendeVolgorde = static function () use ($app, $user): int {
            $rij = $app->db->fetchOne(
                'SELECT COALESCE(MAX(volgorde), 0) AS hoogste FROM wesley1_menu_items WHERE user_id = ?',
                [$user->id]
            );

            return (int) ($rij['hoogste'] ?? 0) + 1;
        };
        // Een product uit het assortiment als nieuw item op de kaart zetten (enkel in wesley1_menu_items)
        $zetOpKaart = static function (array $product) use ($app, $user, $omschrijvingVoor, $volgendeVolgorde): void {
            $app->db->execute(
                'INSERT INTO wesley1_menu_items (user_id, product_id, naam, omschrijving, prijs, volgorde, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $user->id,
                    (int) $product['id'],
                    (string) $product['name'],
                    $omschrijvingVoor($product),
                    round((float) $product['price'], 2),
                    $volgendeVolgorde(),
                    date('Y-m-d H:i:s'),
                ]
            );
        };
        // product_id's die al op de kaart van deze handelaar staan
        $productIdsOpKaart = static function () use ($app, $user): array {
            $rijen = $app->db->fetchAll(
                'SELECT product_id FROM wesley1_menu_items WHERE user_id = ? AND product_id IS NOT NULL',
                [$user->id]
            );

            return array_map(static fn (array $rij): int => (int) $rij['product_id'], $rijen);
        };

        // Elk POST-formulier moet een geldige CSRF-token hebben ($csrfField() in het formulier)
        if ($request->isPost()) {
            $token = $request->post('_csrf');
            if (!$app->csrf->verify(is_string($token) ? $token : null)) {
                $app->session->flash('error', 'Je sessie is verlopen. Probeer het opnieuw.');
                Response::redirect($paginaUrl)->send();
                exit;
            }

            // Melding tonen en terug naar de pagina (zo wordt een formulier niet dubbel verstuurd)
            $terug = static function (string $type, string $melding) use ($app, $paginaUrl): void {
                $app->session->flash($type, $melding);
                Response::redirect($paginaUrl)->send();
                exit;
            };
            // Tekstveld uit het formulier, zonder spaties rond en ingekort tot $max tekens
            $tekst = static function (string $veld, int $max) use ($request): string {
                return mb_substr(trim((string) $request->post($veld, '')), 0, $max);
            };

            $actie = (string) $request->post('actie', '');

            if ($actie === 'instellingen') {
                $titel = $tekst('titel', 120);
                $ondertitel = $tekst('ondertitel', 200);
                if ($titel === '') {
                    $terug('error', 'Geef de kaart een titel.');
                }

                $bestaat = $app->db->fetchOne('SELECT user_id FROM wesley1_menu WHERE user_id = ?', [$user->id]);
                if ($bestaat === null) {
                    $app->db->execute(
                        'INSERT INTO wesley1_menu (user_id, titel, ondertitel, updated_at) VALUES (?, ?, ?, ?)',
                        [$user->id, $titel, $ondertitel, date('Y-m-d H:i:s')]
                    );
                } else {
                    $app->db->execute(
                        'UPDATE wesley1_menu SET titel = ?, ondertitel = ?, updated_at = ? WHERE user_id = ?',
                        [$titel, $ondertitel, date('Y-m-d H:i:s'), $user->id]
                    );
                }
                $terug('success', 'Titel en ondertitel bewaard.');
            }

            if ($actie === 'overnemen') {
                // Enkel producten van deze handelaar (WHERE user_id) die nog niet op de kaart staan
                $alOpKaart = $productIdsOpKaart();
                $toegevoegd = 0;
                foreach ($actieveProducten() as $product) {
                    if (in_array((int) $product['id'], $alOpKaart, true)) {
                        continue;
                    }
                    $zetOpKaart($product);
                    $toegevoegd++;
                }

                if ($toegevoegd === 0) {
                    $terug('success', 'Alle producten uit je assortiment staan al op de kaart.');
                }
                $terug('success', $toegevoegd === 1
                    ? '1 product van je assortiment op de kaart gezet.'
                    : $toegevoegd . ' producten van je assortiment op de kaart gezet.');
            }

            $terug('error', 'Onbekende actie.');
        }

        // Instellingen van de kaart van deze handelaar (of de standaardwaarden)
        $menu = $app->db->fetchOne('SELECT titel, ondertitel FROM wesley1_menu WHERE user_id = ?', [$user->id])
            ?? ['titel' => 'Feestmenu', 'ondertitel' => ''];

        // Items op de kaart, in de ingestelde volgorde
        $items = $app->db->fetchAll(
            'SELECT id, product_id, naam, omschrijving, prijs FROM wesley1_menu_items
             WHERE user_id = ? ORDER BY volgorde ASC, id ASC',
            [$user->id]
        );

        // Actieve producten die nog niet op de kaart staan
        $alOpKaart = $productIdsOpKaart();
        $producten = $actieveProducten();
        $nietOpKaart = array_values(array_filter(
            $producten,
            static fn (array $product): bool => !in_array((int) $product['id'], $alOpKaart, true)
        ));

        // Url naar een bestand in public/features/wesley1/, met ?v=<wijzigingstijd> tegen oude cache
        $asset = static function (string $bestand) use ($url): string {
            $pad = '/features/wesley1/' . ltrim($bestand, '/');
            $mtime = @filemtime(__DIR__ . $pad);

            return $url($pad) . ($mtime === false ? '' : '?v=' . $mtime);
        };

        // Sjabloon (deel 2 van dit bestand) renderen en in de XOrder-layout zetten
        $resultaat = $app->view->renderFile(__FILE__, [
            'wesley1Sjabloon' => true,
            'app' => $app,
            'user' => $user,
            'paginaUrl' => $paginaUrl,
            'asset' => $asset,
            'menu' => $menu,
            'items' => $items,
            'producten' => $producten,
            'nietOpKaart' => $nietOpKaart,
        ]);

        $html = $app->view->renderPartial('layouts/base', [
            'pageTitle' => 'Feestmenu',
            'user' => $user,
            'content' => '<section class="section wesley1-pagina"><div class="container">'
                . $resultaat['html'] . '</div></section>',
        ]);
        Response::html($html)->send();
    } catch (Throwable $fout) {
        // Fouten loggen (buiten de webroot), enkel tonen in debugmodus, zoals src/bootstrap.php
        http_response_code(500);
        $logDir = $rootDir . '/storage/logs';
        if (is_dir($logDir) || @mkdir($logDir, 0775, true)) {
            @error_log(
                '[' . date('Y-m-d H:i:s') . '] wesley1: ' . get_class($fout) . ': ' . $fout->getMessage()
                . ' in ' . $fout->getFile() . ':' . $fout->getLine() . "\n",
                3,
                $logDir . '/app.log'
            );
        }
        if (isset($app) && $app->config->isDebug()) {
            echo '<pre>' . htmlspecialchars($fout->getMessage() . "\n\n" . $fout->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        } else {
            echo '<h1>Er ging iets mis</h1><p>Probeer het later opnieuw.</p>';
        }
    }

    return;
}

// ============================================================================
// 2. SJABLOON (wordt gerenderd met $e, $url en $csrfField)
// ============================================================================
/**
 * @var \App\Models\User $user
 * @var string $paginaUrl
 * @var callable $asset
 * @var array{titel: string, ondertitel: string} $menu
 * @var array<int, array<string, mixed>> $items        items op de kaart, in volgorde
 * @var array<int, array<string, mixed>> $producten    actieve producten van de handelaar
 * @var array<int, array<string, mixed>> $nietOpKaart  actieve producten die nog niet op de kaart staan
 * @var callable $e
 * @var callable $url
 * @var callable $csrfField
 */

// Prijs in euro zoals op een menukaart: € 12,50
$prijs = static fn (mixed $bedrag): string => '€ ' . number_format((float) $bedrag, 2, ',', '.');
?>
<link rel="stylesheet" href="<?= $e($asset('style.css')) ?>">

<div class="wesley1-kop">
    <h1><?= $e('Feestelijke menukaart') ?></h1>
    <p><?= $e('Stel een feestmenu samen op basis van je assortiment en druk het af voor je klanten.') ?></p>
</div>

<div class="wesley1-beheer">
    <?php if ($nietOpKaart !== []): ?>
        <form method="post" action="<?= $e($paginaUrl) ?>" class="card wesley1-blok wesley1-overnemen">
            <?= $csrfField() ?>
            <input type="hidden" name="actie" value="overnemen">
            <h2><?= $e('Assortiment overnemen') ?></h2>
            <p><?= $e(count($nietOpKaart) === 1
                ? '1 actief product uit je assortiment staat nog niet op de kaart.'
                : count($nietOpKaart) . ' actieve producten uit je assortiment staan nog niet op de kaart.') ?></p>
            <button type="submit" class="btn btn-primary"><?= $e('Assortiment overnemen') ?></button>
        </form>
    <?php endif; ?>

    <form method="post" action="<?= $e($paginaUrl) ?>" class="card wesley1-blok wesley1-instellingen">
        <?= $csrfField() ?>
        <input type="hidden" name="actie" value="instellingen">
        <h2><?= $e('Instellingen van de kaart') ?></h2>
        <label class="form-label" for="wesley1-titel"><?= $e('Titel') ?></label>
        <input id="wesley1-titel" class="form-control" type="text" name="titel" maxlength="120" required
               value="<?= $e($menu['titel']) ?>" placeholder="<?= $e('Feestmenu Kerst & Nieuwjaar') ?>">
        <label class="form-label" for="wesley1-ondertitel"><?= $e('Ondertitel') ?></label>
        <input id="wesley1-ondertitel" class="form-control" type="text" name="ondertitel" maxlength="200"
               value="<?= $e($menu['ondertitel']) ?>" placeholder="<?= $e('Bestel tijdig voor de feestdagen') ?>">
        <button type="submit" class="btn btn-primary"><?= $e('Bewaar') ?></button>
    </form>

    <div class="card wesley1-blok wesley1-items">
        <h2><?= $e('Op de kaart') ?></h2>
        <?php if ($items === []): ?>
            <p><?= $e('Nog niets op de kaart.') ?></p>
        <?php else: ?>
            <ul class="wesley1-itemlijst">
                <?php foreach ($items as $item): ?>
                    <li class="wesley1-item">
                        <span class="wesley1-item-naam"><?= $e($item['naam']) ?></span>
                        <span class="wesley1-item-omschrijving"><?= $e($item['omschrijving']) ?></span>
                        <span class="wesley1-item-prijs"><?= $e($prijs($item['prijs'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script src="<?= $e($asset('app.js')) ?>"></script>
