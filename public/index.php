<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ExportController;
use App\Controllers\FeatureController;
use App\Controllers\KitchenController;
use App\Controllers\LandingController;
use App\Controllers\OrderController;
use App\Controllers\PageController;
use App\Controllers\PasswordController;
use App\Controllers\TrackController;
use App\Services\PasswordResetService;
use App\Controllers\ProductController;
use App\Controllers\SettingsController;
use App\Core\App;
use App\Core\Response;
use App\Core\Router;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\DemoSeederService;
use App\Services\LoginThrottle;
use App\Services\XlsxExporter;

// Standaard staat de app-code naast public/. Op een server waar enkel public/ in de webroot
// staat (bv. assurio.be/xorder), wijst public/xorder-path.php naar de map met de app-code.
$rootDir = dirname(__DIR__);
if (is_file(__DIR__ . '/xorder-path.php')) {
    $rootDir = (string) require __DIR__ . '/xorder-path.php';
}

require_once $rootDir . '/src/autoload.php';

try {
    $app = new App($rootDir);

    $userRepo = new UserRepository($app->db);
    $productRepo = new ProductRepository($app->db);
    $orderRepo = new OrderRepository($app->db);

    $seeder = new DemoSeederService($productRepo, $orderRepo, $userRepo, $app->i18n);
    $authService = new AuthService($userRepo, $app->session, $app->i18n, $seeder);
    $xlsxExporter = new XlsxExporter($app->i18n);

    $landing = new LandingController($app, $authService);
    $auth = new AuthController($app, $authService);
    $dashboard = new DashboardController($app, $authService, $orderRepo, $productRepo, $seeder);
    $products = new ProductController($app, $authService, $productRepo);
    $orders = new OrderController($app, $authService, $orderRepo, $productRepo);
    $kitchen = new KitchenController($app, $authService, $orderRepo);
    $export = new ExportController($app, $authService, $orderRepo, $userRepo, $xlsxExporter);
    $settings = new SettingsController($app, $authService, $userRepo);
    $resetService = new PasswordResetService($app->db, $userRepo, $app->i18n, $app->config);
    $throttle = new LoginThrottle($app->db);
    $auth->setThrottle($throttle);
    $password = new PasswordController($app, $authService, $resetService, $throttle);
    $pages = new PageController($app, $authService);
    $tracker = new TrackController($app, $authService);
    $features = new FeatureController($app, $authService);

    $router = new Router();

    // Public routes
    $router->get('/', static fn ($req): Response => $landing->index($req));

    // Legal pages, password reset and widget analytics
    $router->get('/privacy', static fn ($req): Response => $pages->privacy($req));
    $router->get('/cookies', static fn ($req): Response => $pages->cookies($req));
    $router->get('/forgot', static fn ($req): Response => $password->showForgot($req));
    $router->post('/forgot', static fn ($req): Response => $password->forgot($req));
    $router->get('/reset/:token', static fn ($req, $params): Response => $password->showReset($req, $params));
    $router->post('/reset/:token', static fn ($req, $params): Response => $password->reset($req, $params));
    $router->post('/track', static fn ($req): Response => $tracker->track($req));

    // Auth routes
    $router->get('/login', static fn ($req): Response => $auth->showLogin($req));
    $router->post('/login', static fn ($req): Response => $auth->login($req));
    $router->get('/register', static fn ($req): Response => $auth->showRegister($req));
    $router->post('/register', static fn ($req): Response => $auth->register($req));
    $router->post('/logout', static fn ($req): Response => $auth->logout($req));

    // Authenticated merchant workspace
    $router->get('/app', static fn ($req): Response => $dashboard->index($req));
    $router->post('/app/demo/clear', static fn ($req): Response => $dashboard->clearDemo($req));

    $router->get('/app/products', static fn ($req): Response => $products->index($req));
    $router->post('/app/products', static fn ($req): Response => $products->store($req));
    $router->post('/app/products/:id/update', static fn ($req, $params): Response => $products->update($req, $params));
    $router->post('/app/products/:id/delete', static fn ($req, $params): Response => $products->delete($req, $params));

    $router->get('/app/orders', static fn ($req): Response => $orders->index($req));
    $router->get('/app/orders/new', static fn ($req): Response => $orders->create($req));
    $router->post('/app/orders', static fn ($req): Response => $orders->store($req));
    $router->get('/app/orders/:id/edit', static fn ($req, $params): Response => $orders->edit($req, $params));
    $router->post('/app/orders/:id', static fn ($req, $params): Response => $orders->update($req, $params));
    $router->post('/app/orders/:id/status', static fn ($req, $params): Response => $orders->updateStatus($req, $params));
    $router->post('/app/orders/:id/delete', static fn ($req, $params): Response => $orders->delete($req, $params));
    $router->get('/app/orders/:id/print', static fn ($req, $params): Response => $orders->printSlip($req, $params));

    $router->get('/app/kitchen', static fn ($req): Response => $kitchen->index($req));
    $router->get('/app/export/xlsx', static fn ($req): Response => $export->exportXlsx($req));

    $router->get('/app/settings', static fn ($req): Response => $settings->index($req));
    $router->post('/app/settings', static fn ($req): Response => $settings->update($req));

    // Cursistfeatures: elk bestand features/<voornaam>.php wordt automatisch een pagina
    $router->get('/features', static fn ($req): Response => $features->index($req));
    $router->get('/features/:name', static fn ($req, $params): Response => $features->show($req, $params));
    $router->post('/features/:name', static fn ($req, $params): Response => $features->show($req, $params));

    $response = $router->dispatch($app->request);
    $response->send();
} catch (Throwable $e) {
    http_response_code(500);
    // Fouten altijd loggen (buiten de webroot), nooit tonen in productie
    $logDir = $rootDir . '/storage/logs';
    if (is_dir($logDir) || @mkdir($logDir, 0775, true)) {
        @error_log(
            '[' . date('Y-m-d H:i:s') . '] ' . get_class($e) . ': ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n",
            3,
            $logDir . '/app.log'
        );
    }
    $isLocal = isset($app) && $app->config->isDebug();
    if ($isLocal) {
        echo '<pre style="font-family:monospace;padding:24px;">'
            . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8')
            . '</pre>';
    } else {
        echo '<h1>Er ging iets mis</h1><p>Probeer het later opnieuw.</p>';
    }
}
