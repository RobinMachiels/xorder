<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * PHP template renderer with built-in escaping, translation, and URL helpers.
 */
final class View
{
    private string $viewsDir;
    private I18n $i18n;
    private Request $request;
    private Session $session;
    private Csrf $csrf;

    /** @var array<string, mixed> */
    private array $shared = [];

    /** @var array<int, array{type: string, message: string}>|null */
    private ?array $flashCache = null;

    public function __construct(
        string $viewsDir,
        I18n $i18n,
        Request $request,
        Session $session,
        Csrf $csrf
    ) {
        $this->viewsDir = rtrim($viewsDir, '/');
        $this->i18n = $i18n;
        $this->request = $request;
        $this->session = $session;
        $this->csrf = $csrf;
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?string $layout = 'layouts/base'): string
    {
        $content = $this->renderPartial($template, $data);
        if ($layout === null) {
            return $content;
        }

        return $this->renderPartial($layout, array_merge($data, [
            'content' => $content,
        ]));
    }

    /**
     * Flash messages are consumed once per request. Content partial and layout both need them,
     * so cache after the first read (otherwise the content render swallows them before the layout).
     *
     * @return array<int, array{type: string, message: string}>
     */
    private function flashes(): array
    {
        if ($this->flashCache === null) {
            $this->flashCache = $this->session->getFlashes();
        }

        return $this->flashCache;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderPartial(string $template, array $data = []): string
    {
        $result = $this->renderFile($this->viewsDir . '/' . ltrim($template, '/') . '.php', $data);

        return $result['html'];
    }

    /**
     * Renders any PHP file with the standard helpers. Returns the HTML and the file's return value.
     *
     * @param array<string, mixed> $data
     * @return array{html: string, returned: mixed}
     */
    public function renderFile(string $templatePath, array $data = []): array
    {
        if (!is_file($templatePath)) {
            throw new RuntimeException('Template niet gevonden: ' . $templatePath);
        }

        $e = static function (mixed $value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        $t = function (string $key, array $params = []): string {
            return $this->i18n->t($key, $params);
        };
        // $url('/app/order-edit', ['id' => 5]) geeft '<base>/app/order-edit.php?id=5'
        $url = function (string $path = '/', array $query = []): string {
            return $this->request->url($path, $query);
        };
        $absUrl = function (string $path = '/', array $query = []): string {
            return $this->request->absoluteUrl($path, $query);
        };
        $csrfField = function (): string {
            return $this->csrf->field();
        };
        // Url naar een bestand in public/ met ?v=<wijzigingstijd>, zodat browsers na elke deploy
        // de nieuwe css/js ophalen ondanks een eventuele cache van de webserver.
        $assetUrl = function (string $path): string {
            $publicDir = defined('XORDER_PUBLIC_DIR') ? (string) XORDER_PUBLIC_DIR : dirname($this->viewsDir) . '/public';
            $mtime = @filemtime($publicDir . '/' . ltrim($path, '/'));
            $href = $this->request->url($path);

            return $mtime === false ? $href : $href . '?v=' . $mtime;
        };

        $locale = $this->i18n->locale();
        $supportedLocales = $this->i18n->supportedLocales();
        $flashes = $this->flashes();
        $currentPath = $this->request->path();
        // Zelfde pagina (met dezelfde parameters) in een andere taal
        $langUrl = function (string $locale): string {
            return $this->request->url(
                $this->request->path(),
                array_merge($this->request->allQuery(), ['lang' => $locale])
            );
        };

        extract($data, EXTR_SKIP);
        extract($this->shared, EXTR_SKIP);

        ob_start();
        $returned = require $templatePath;
        $output = ob_get_clean();

        return ['html' => is_string($output) ? $output : '', 'returned' => $returned];
    }
}
