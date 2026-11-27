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

        // Elk POST-formulier moet een geldige CSRF-token hebben ($csrfField() in het formulier)
        if ($request->isPost()) {
            $token = $request->post('_csrf');
            if (!$app->csrf->verify(is_string($token) ? $token : null)) {
                $app->session->flash('error', 'Je sessie is verlopen. Probeer het opnieuw.');
                Response::redirect($paginaUrl)->send();
                exit;
            }

            // Hier komen de acties (verborgen veld "actie")

            Response::redirect($paginaUrl)->send();
            exit;
        }

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
 * @var callable $e
 * @var callable $url
 * @var callable $csrfField
 */
?>
<link rel="stylesheet" href="<?= $e($asset('style.css')) ?>">

<div class="wesley1-kop">
    <h1><?= $e('Feestelijke menukaart') ?></h1>
    <p><?= $e('Stel een feestmenu samen op basis van je assortiment en druk het af voor je klanten.') ?></p>
</div>

<script src="<?= $e($asset('app.js')) ?>"></script>
