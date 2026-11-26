<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\FeatureRegistry;
use App\Core\Request;
use App\Core\Response;

/**
 * Renders student feature pages (features/<voornaam>.php) inside the normal layout.
 * Students never touch this file: adding a file in features/ is enough.
 */
final class FeatureController extends BaseController
{
    public function index(Request $request): Response
    {
        if (!$this->enabled()) {
            return $this->notFound();
        }

        $html = $this->app->view->render('features/index', [
            'pageTitle' => 'Features van de klas',
            'user' => $this->auth->currentUser(),
            'names' => FeatureRegistry::names($this->featuresDir()),
        ]);

        return Response::html($html);
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params): Response
    {
        $name = (string) ($params['name'] ?? '');
        $file = $this->featuresDir() . '/' . $name . '.php';

        if (!$this->enabled() || !FeatureRegistry::isValidName($name) || !is_file($file)) {
            return $this->notFound();
        }

        // CSRF wordt hier centraal gecontroleerd: de cursist zet enkel $csrfField() in zijn formulier.
        if ($request->isPost() && ($csrfError = $this->verifyCsrf($request, '/features/' . $name)) !== null) {
            return $csrfError;
        }

        $url = function (string $path = '/') use ($request): string {
            return $request->url($path);
        };

        $result = $this->app->view->renderFile($file, [
            'app' => $this->app,
            'request' => $request,
            'user' => $this->auth->currentUser(),
            'featureName' => $name,
            'featureUrl' => $request->url('/features/' . $name),
            'asset' => static function (string $file) use ($url, $name): string {
                return $url('/features/' . $name . '/' . ltrim($file, '/'));
            },
        ]);

        // Een feature mag zelf een Response teruggeven, bv. return Response::redirect(...);
        if ($result['returned'] instanceof Response) {
            return $result['returned'];
        }

        $html = $this->app->view->renderPartial('layouts/base', [
            'pageTitle' => ucfirst($name),
            'user' => $this->auth->currentUser(),
            'content' => '<section class="section feature-page feature-' . $name . '"><div class="container">'
                . $result['html'] . '</div></section>',
        ]);

        return Response::html($html);
    }

    private function enabled(): bool
    {
        return (bool) $this->app->config->get('features.enabled', false);
    }

    private function featuresDir(): string
    {
        return $this->app->rootDir . '/features';
    }

    private function notFound(): Response
    {
        return Response::html('<h1>404 - Pagina niet gevonden</h1>', 404);
    }
}
