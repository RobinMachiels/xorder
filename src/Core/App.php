<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Application container holding shared services and request lifecycle helpers.
 */
final class App
{
    public string $rootDir;
    public Config $config;
    public Database $db;
    public Session $session;
    public Csrf $csrf;
    public I18n $i18n;
    public Request $request;
    public View $view;

    public function __construct(string $rootDir)
    {
        $this->rootDir = rtrim($rootDir, '/');
        $this->config = Config::load($this->rootDir);
        $this->db = new Database($this->config);

        $migrator = new Migrator($this->db, $this->rootDir);
        $migrator->ensureSchema();

        $this->session = new Session();
        $this->session->start();

        $this->csrf = new Csrf($this->session);
        $this->request = Request::capture((string) $this->config->get('app.base_url', ''));

        /** @var array<int, string> $supportedLocales */
        $supportedLocales = (array) $this->config->get('app.supported_locales', ['nl', 'fr']);
        $defaultLocale = (string) $this->config->get('app.default_locale', 'nl');

        $this->i18n = new I18n($this->rootDir . '/lang', $defaultLocale, $supportedLocales);
        $this->i18n->resolve($this->request, $this->session);

        $this->view = new View(
            $this->rootDir . '/views',
            $this->i18n,
            $this->request,
            $this->session,
            $this->csrf
        );
    }
}
