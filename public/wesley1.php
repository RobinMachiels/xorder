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

            // Prijs uit het formulier: "12,50" of "12.50", een geldig getal van 0 of meer. Anders null.
            $prijsUitFormulier = static function () use ($request): ?float {
                $invoer = str_replace(',', '.', trim((string) $request->post('prijs', '')));
                if ($invoer === '' || !is_numeric($invoer) || (float) $invoer < 0 || (float) $invoer > 99999) {
                    return null;
                }

                return round((float) $invoer, 2);
            };
            // Id van een item of product uit het formulier (enkel cijfers), anders 0
            $idUitFormulier = static function (string $veld) use ($request): int {
                $waarde = (string) $request->post($veld, '');

                return ctype_digit($waarde) ? (int) $waarde : 0;
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

            if ($actie === 'toevoegen_product') {
                // Het product moet echt van deze handelaar zijn (en actief)
                $product = $app->db->fetchOne(
                    'SELECT id, name, category, unit, price FROM products WHERE id = ? AND user_id = ? AND is_active = 1',
                    [$idUitFormulier('product_id'), $user->id]
                );
                if ($product === null) {
                    $terug('error', 'Kies een product uit je assortiment.');
                }
                if (in_array((int) $product['id'], $productIdsOpKaart(), true)) {
                    $terug('error', 'Dit product staat al op de kaart.');
                }
                $zetOpKaart($product);
                $terug('success', 'Product op de kaart gezet.');
            }

            if ($actie === 'toevoegen_eigen' || $actie === 'aanpassen') {
                $naam = $tekst('naam', 160);
                $omschrijving = $tekst('omschrijving', 300);
                $prijsWaarde = $prijsUitFormulier();
                if ($naam === '') {
                    $terug('error', 'Geef een naam op.');
                }
                if ($prijsWaarde === null) {
                    $terug('error', 'Geef een geldige prijs op (0 of meer, bv. 12,50).');
                }

                if ($actie === 'toevoegen_eigen') {
                    // Eigen gerecht: product_id blijft leeg
                    $app->db->execute(
                        'INSERT INTO wesley1_menu_items (user_id, product_id, naam, omschrijving, prijs, volgorde, created_at)
                         VALUES (?, NULL, ?, ?, ?, ?, ?)',
                        [$user->id, $naam, $omschrijving, $prijsWaarde, $volgendeVolgorde(), date('Y-m-d H:i:s')]
                    );
                    $terug('success', 'Eigen gerecht op de kaart gezet.');
                }

                // Aanpassen: enkel in wesley1_menu_items en enkel een item van deze handelaar
                $aangepast = $app->db->execute(
                    'UPDATE wesley1_menu_items SET naam = ?, omschrijving = ?, prijs = ? WHERE id = ? AND user_id = ?',
                    [$naam, $omschrijving, $prijsWaarde, $idUitFormulier('item_id'), $user->id]
                );
                if ($aangepast === 0) {
                    $terug('error', 'Dit item staat niet op je kaart.');
                }
                $terug('success', 'Item aangepast.');
            }

            if ($actie === 'omhoog' || $actie === 'omlaag') {
                // Huidige volgorde van de kaart van deze handelaar
                $ids = array_map(
                    static fn (array $rij): int => (int) $rij['id'],
                    $app->db->fetchAll(
                        'SELECT id FROM wesley1_menu_items WHERE user_id = ? ORDER BY volgorde ASC, id ASC',
                        [$user->id]
                    )
                );
                $plaats = array_search($idUitFormulier('item_id'), $ids, true);
                if ($plaats === false) {
                    $terug('error', 'Dit item staat niet op je kaart.');
                }

                $buur = $actie === 'omhoog' ? $plaats - 1 : $plaats + 1;
                if (!isset($ids[$buur])) {
                    $terug('success', $actie === 'omhoog' ? 'Dit item staat al bovenaan.' : 'Dit item staat al onderaan.');
                }

                // Wisselen met de buur en alles opnieuw nummeren (1, 2, 3, ...)
                [$ids[$plaats], $ids[$buur]] = [$ids[$buur], $ids[$plaats]];
                $app->db->beginTransaction();
                foreach ($ids as $index => $id) {
                    $app->db->execute(
                        'UPDATE wesley1_menu_items SET volgorde = ? WHERE id = ? AND user_id = ?',
                        [$index + 1, $id, $user->id]
                    );
                }
                $app->db->commit();
                $terug('success', 'Volgorde aangepast.');
            }

            if ($actie === 'verwijderen') {
                // Enkel van de kaart (wesley1_menu_items), nooit uit het assortiment
                $verwijderd = $app->db->execute(
                    'DELETE FROM wesley1_menu_items WHERE id = ? AND user_id = ?',
                    [$idUitFormulier('item_id'), $user->id]
                );
                if ($verwijderd === 0) {
                    $terug('error', 'Dit item staat niet op je kaart.');
                }
                $terug('success', 'Item van de kaart verwijderd.');
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
<?php
// Kleine hulpjes voor het sjabloon
$aantalItems = count($items);
$aantalNieuw = count($nietOpKaart);
$geenProducten = $producten === [];
// Standaard tabblad bij "Toevoegen": uit assortiment als er nog producten over zijn, anders eigen gerecht
$standaardTab = $aantalNieuw > 0 ? 'assortiment' : 'eigen';
?>
<link rel="stylesheet" href="<?= $e($asset('style.css')) ?>">

<div class="wesley1-app">

    <!-- Kopbalk -->
    <header class="wesley1-kop no-print">
        <div class="wesley1-kop-tekst">
            <span class="wesley1-eyebrow"><?= $e('Feestdagen') ?></span>
            <h1><?= $e('Feestelijke menukaart') ?></h1>
            <p><?= $e('Stel een feestmenu samen op basis van je assortiment en druk het af voor je klanten.') ?></p>
        </div>
        <div class="wesley1-kop-acties">
            <div class="wesley1-cijfers">
                <div class="wesley1-cijfer">
                    <strong><?= $e($aantalItems) ?></strong>
                    <span><?= $e($aantalItems === 1 ? 'item op de kaart' : 'items op de kaart') ?></span>
                </div>
                <div class="wesley1-cijfer">
                    <strong><?= $e($aantalNieuw) ?></strong>
                    <span><?= $e('nog niet op de kaart') ?></span>
                </div>
            </div>
            <?php if ($items !== []): ?>
                <button type="button" class="btn btn-gold wesley1-knop" id="wesley1-afdrukken">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v7H7z"/></svg>
                    <?= $e('Afdrukken') ?>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="wesley1-werkblad">

        <!-- Linkerkolom: de kaart beheren -->
        <div class="wesley1-hoofdkolom">
        <section class="wesley1-paneel wesley1-lijstpaneel no-print" aria-labelledby="wesley1-lijst-titel">
            <div class="wesley1-paneel-kop">
                <div>
                    <h2 id="wesley1-lijst-titel"><?= $e('Op de kaart') ?></h2>
                    <p><?= $e('Pas aan wat je klanten zien. Je echte assortiment blijft altijd ongewijzigd.') ?></p>
                </div>
                <?php if ($items !== [] && $aantalNieuw > 0): ?>
                    <form method="post" action="<?= $e($paginaUrl) ?>">
                        <?= $csrfField() ?>
                        <input type="hidden" name="actie" value="overnemen">
                        <button type="submit" class="btn btn-outline wesley1-knop">
                            <?= $e('Assortiment overnemen (' . $aantalNieuw . ')') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($items === [] && $geenProducten): ?>
                <!-- Lege toestand: geen producten en niets op de kaart -->
                <div class="wesley1-leeg">
                    <div class="wesley1-leeg-icoon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M3 7l9-4 9 4-9 4zM3 7v10l9 4 9-4V7M12 11v10"/></svg>
                    </div>
                    <h3><?= $e('Voeg eerst producten toe') ?></h3>
                    <p><?= $e('Je hebt nog geen actieve producten in je assortiment. Voeg ze toe op de productpagina, dan zet je ze hier met één klik op de kaart. Een eigen gerecht toevoegen kan nu al, via het paneel rechts.') ?></p>
                    <a class="btn btn-primary wesley1-knop" href="<?= $e($url('/app/products')) ?>"><?= $e('Naar mijn producten') ?></a>
                </div>
            <?php elseif ($items === []): ?>
                <!-- Lege toestand: wel producten, maar de kaart is nog leeg -->
                <form method="post" action="<?= $e($paginaUrl) ?>" class="wesley1-leeg wesley1-leeg-start">
                    <?= $csrfField() ?>
                    <input type="hidden" name="actie" value="overnemen">
                    <div class="wesley1-leeg-icoon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z"/></svg>
                    </div>
                    <h3><?= $e('Begin met je assortiment') ?></h3>
                    <p><?= $e($aantalNieuw === 1
                        ? 'Zet je actieve product in één klik op de kaart. Daarna pas je naam, omschrijving, prijs en volgorde aan.'
                        : 'Zet je ' . $aantalNieuw . ' actieve producten in één klik op de kaart. Daarna pas je namen, omschrijvingen, prijzen en volgorde aan.') ?></p>
                    <button type="submit" class="btn btn-primary wesley1-knop"><?= $e('Assortiment overnemen') ?></button>
                </form>
            <?php else: ?>
                <ol class="wesley1-rijen">
                    <?php foreach ($items as $positie => $item): ?>
                        <?php
                        $itemId = (int) $item['id'];
                        $isEerste = $positie === 0;
                        $isLaatste = $positie === $aantalItems - 1;
                        ?>
                        <li class="wesley1-rij">
                            <div class="wesley1-rij-hoofd">
                                <div class="wesley1-volgorde">
                                    <?php foreach (['omhoog' => ['Omhoog', $isEerste, 'M6 15l6-6 6 6'], 'omlaag' => ['Omlaag', $isLaatste, 'M6 9l6 6 6-6']] as $richting => [$label, $kanNiet, $pad]): ?>
                                        <form method="post" action="<?= $e($paginaUrl) ?>">
                                            <?= $csrfField() ?>
                                            <input type="hidden" name="actie" value="<?= $e($richting) ?>">
                                            <input type="hidden" name="item_id" value="<?= $e($itemId) ?>">
                                            <button type="submit" class="wesley1-icoonknop" title="<?= $e($label) ?>"
                                                    aria-label="<?= $e($label . ': ' . $item['naam']) ?>" <?= $e($kanNiet ? 'disabled' : '') ?>>
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?= $e($pad) ?>"/></svg>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>

                                <span class="wesley1-nummer"><?= $e($positie + 1) ?></span>

                                <div class="wesley1-rij-tekst">
                                    <span class="wesley1-rij-naam"><?= $e($item['naam']) ?></span>
                                    <?php if ((string) $item['omschrijving'] !== ''): ?>
                                        <span class="wesley1-rij-omschrijving"><?= $e($item['omschrijving']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <span class="wesley1-label <?= $e($item['product_id'] !== null ? 'wesley1-label-assortiment' : 'wesley1-label-eigen') ?>">
                                    <?= $e($item['product_id'] !== null ? 'Assortiment' : 'Eigen gerecht') ?>
                                </span>

                                <span class="wesley1-rij-prijs"><?= $e($prijs($item['prijs'])) ?></span>

                                <div class="wesley1-rij-acties">
                                    <button type="button" class="wesley1-icoonknop" data-wesley1-bewerk="wesley1-bewerk-<?= $e($itemId) ?>"
                                            aria-expanded="false" aria-controls="wesley1-bewerk-<?= $e($itemId) ?>"
                                            title="<?= $e('Aanpassen') ?>" aria-label="<?= $e('Aanpassen: ' . $item['naam']) ?>">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4"/></svg>
                                    </button>
                                    <form method="post" action="<?= $e($paginaUrl) ?>"
                                          data-wesley1-bevestig="<?= $e('"' . $item['naam'] . '" van de kaart verwijderen? Je assortiment blijft ongewijzigd.') ?>">
                                        <?= $csrfField() ?>
                                        <input type="hidden" name="actie" value="verwijderen">
                                        <input type="hidden" name="item_id" value="<?= $e($itemId) ?>">
                                        <button type="submit" class="wesley1-icoonknop wesley1-icoonknop-gevaar"
                                                title="<?= $e('Verwijderen') ?>" aria-label="<?= $e('Verwijderen: ' . $item['naam']) ?>">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Aanpassen: klapt open via de potloodknop (app.js). Zonder JavaScript blijft het zichtbaar. -->
                            <form method="post" action="<?= $e($paginaUrl) ?>" class="wesley1-bewerk" id="wesley1-bewerk-<?= $e($itemId) ?>">
                                <?= $csrfField() ?>
                                <input type="hidden" name="actie" value="aanpassen">
                                <input type="hidden" name="item_id" value="<?= $e($itemId) ?>">
                                <label class="wesley1-veld">
                                    <span><?= $e('Naam') ?></span>
                                    <input class="form-control" type="text" name="naam" maxlength="160" required value="<?= $e($item['naam']) ?>">
                                </label>
                                <label class="wesley1-veld">
                                    <span><?= $e('Omschrijving') ?></span>
                                    <input class="form-control" type="text" name="omschrijving" maxlength="300" value="<?= $e($item['omschrijving']) ?>">
                                </label>
                                <label class="wesley1-veld">
                                    <span><?= $e('Prijs (€)') ?></span>
                                    <input class="form-control" type="number" name="prijs" min="0" max="99999" step="0.01" required
                                           value="<?= $e(number_format((float) $item['prijs'], 2, '.', '')) ?>">
                                </label>
                                <div class="wesley1-bewerk-knoppen">
                                    <button type="button" class="btn btn-outline wesley1-knop" data-wesley1-sluit="wesley1-bewerk-<?= $e($itemId) ?>"><?= $e('Annuleren') ?></button>
                                    <button type="submit" class="btn btn-primary wesley1-knop"><?= $e('Bewaar') ?></button>
                                </div>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>

        <!-- Voorbeeld van de kaart -->
        <section class="wesley1-podium" aria-labelledby="wesley1-voorbeeld-titel">
            <div class="wesley1-podium-kop no-print">
                <h2 id="wesley1-voorbeeld-titel"><?= $e('Voorbeeld van de kaart') ?></h2>
                <p><?= $e('Zo ziet je klant de kaart. Met "Afdrukken" komt enkel deze kaart op A4.') ?></p>
            </div>

            <article class="wesley1-kaart" aria-label="<?= $e('Menukaart') ?>">
                <div class="wesley1-kaart-binnen">
                    <header class="wesley1-kaart-kop">
                        <p class="wesley1-kaart-ster" aria-hidden="true"><?= $e('✦ ✦ ✦') ?></p>
                        <h3 class="wesley1-kaart-titel"><?= $e($menu['titel']) ?></h3>
                        <?php if ($menu['ondertitel'] !== ''): ?>
                            <p class="wesley1-kaart-ondertitel"><?= $e($menu['ondertitel']) ?></p>
                        <?php endif; ?>
                        <div class="wesley1-kaart-sier" aria-hidden="true"></div>
                    </header>

                    <?php if ($items === []): ?>
                        <p class="wesley1-kaart-leeg"><?= $e('De kaart is nog leeg.') ?></p>
                    <?php else: ?>
                        <ul class="wesley1-kaart-lijst">
                            <?php foreach ($items as $item): ?>
                                <li class="wesley1-kaart-item">
                                    <div class="wesley1-kaart-regel">
                                        <span class="wesley1-kaart-naam"><?= $e($item['naam']) ?></span>
                                        <span class="wesley1-kaart-stippen" aria-hidden="true"></span>
                                        <span class="wesley1-kaart-prijs"><?= $e($prijs($item['prijs'])) ?></span>
                                    </div>
                                    <?php if ((string) $item['omschrijving'] !== ''): ?>
                                        <p class="wesley1-kaart-omschrijving"><?= $e($item['omschrijving']) ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <footer class="wesley1-kaart-voet">
                        <div class="wesley1-kaart-sier" aria-hidden="true"></div>
                        <p><?= $e('Prettige feestdagen!') ?></p>
                    </footer>
                </div>
            </article>
        </section>
        </div>

        <!-- Rechterkolom: instellingen en toevoegen -->
        <aside class="wesley1-zijbalk no-print">
            <form method="post" action="<?= $e($paginaUrl) ?>" class="wesley1-paneel">
                <?= $csrfField() ?>
                <input type="hidden" name="actie" value="instellingen">
                <div class="wesley1-paneel-kop">
                    <div>
                        <h2><?= $e('Kaart') ?></h2>
                        <p><?= $e('Bovenaan de afgedrukte kaart.') ?></p>
                    </div>
                </div>
                <div class="wesley1-velden">
                    <label class="wesley1-veld">
                        <span><?= $e('Titel') ?></span>
                        <input class="form-control" type="text" name="titel" maxlength="120" required
                               value="<?= $e($menu['titel']) ?>" placeholder="<?= $e('Feestmenu Kerst & Nieuwjaar') ?>">
                    </label>
                    <label class="wesley1-veld">
                        <span><?= $e('Ondertitel') ?></span>
                        <input class="form-control" type="text" name="ondertitel" maxlength="200"
                               value="<?= $e($menu['ondertitel']) ?>" placeholder="<?= $e('Bestel tijdig voor de feestdagen') ?>">
                    </label>
                    <button type="submit" class="btn btn-primary wesley1-knop"><?= $e('Bewaar') ?></button>
                </div>
            </form>

            <div class="wesley1-paneel">
                <div class="wesley1-paneel-kop">
                    <div>
                        <h2><?= $e('Toevoegen') ?></h2>
                        <p><?= $e('Komt onderaan de kaart.') ?></p>
                    </div>
                </div>

                <?php if ($aantalNieuw > 0): ?>
                    <div class="wesley1-tabs" role="tablist" aria-label="<?= $e('Soort item') ?>">
                        <button type="button" role="tab" class="wesley1-tab" data-wesley1-tab="assortiment"
                                aria-selected="<?= $e($standaardTab === 'assortiment' ? 'true' : 'false') ?>"><?= $e('Uit assortiment') ?></button>
                        <button type="button" role="tab" class="wesley1-tab" data-wesley1-tab="eigen"
                                aria-selected="<?= $e($standaardTab === 'eigen' ? 'true' : 'false') ?>"><?= $e('Eigen gerecht') ?></button>
                    </div>

                    <form method="post" action="<?= $e($paginaUrl) ?>" class="wesley1-velden wesley1-tabpaneel" data-wesley1-paneel="assortiment" role="tabpanel">
                        <?= $csrfField() ?>
                        <input type="hidden" name="actie" value="toevoegen_product">
                        <label class="wesley1-veld">
                            <span><?= $e('Product') ?></span>
                            <select class="form-control" name="product_id" required>
                                <option value=""><?= $e('Kies een product') ?></option>
                                <?php foreach ($nietOpKaart as $product): ?>
                                    <option value="<?= $e($product['id']) ?>"><?= $e($product['name'] . ' · ' . $prijs($product['price'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" class="btn btn-primary wesley1-knop"><?= $e('Op de kaart zetten') ?></button>
                    </form>
                <?php endif; ?>

                <form method="post" action="<?= $e($paginaUrl) ?>" class="wesley1-velden wesley1-tabpaneel" data-wesley1-paneel="eigen" role="tabpanel">
                    <?= $csrfField() ?>
                    <input type="hidden" name="actie" value="toevoegen_eigen">
                    <?php if ($aantalNieuw === 0): ?>
                        <p class="wesley1-hint"><?= $e($geenProducten
                            ? 'Een eigen gerecht staat los van je assortiment.'
                            : 'Al je actieve producten staan op de kaart. Voeg hier een eigen gerecht toe.') ?></p>
                    <?php endif; ?>
                    <label class="wesley1-veld">
                        <span><?= $e('Naam') ?></span>
                        <input class="form-control" type="text" name="naam" maxlength="160" required placeholder="<?= $e('Bv. Huisgemaakte tiramisu') ?>">
                    </label>
                    <label class="wesley1-veld">
                        <span><?= $e('Omschrijving') ?></span>
                        <input class="form-control" type="text" name="omschrijving" maxlength="300" placeholder="<?= $e('Bv. Dessert · per portie') ?>">
                    </label>
                    <label class="wesley1-veld">
                        <span><?= $e('Prijs (€)') ?></span>
                        <input class="form-control" type="number" name="prijs" min="0" max="99999" step="0.01" value="0.00" required>
                    </label>
                    <button type="submit" class="btn btn-primary wesley1-knop"><?= $e('Op de kaart zetten') ?></button>
                </form>
            </div>
        </aside>
    </div>
</div>

<script src="<?= $e($asset('app.js')) ?>"></script>
