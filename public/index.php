<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ExportController;
use App\Controllers\KitchenController;
use App\Controllers\LandingController;
use App\Controllers\OrderController;
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
use App\Services\XlsxExporter;

$rootDir = dirname(__DIR__);

// Serve widget static files cleanly when running with `php -S 127.0.0.1:8787 -t public`
$rawPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (is_string($rawPath) && str_starts_with($rawPath, '/widget/')) {
    $widgetFile = realpath($rootDir . $rawPath);
    $widgetBase = realpath($rootDir . '/widget');
    if ($widgetFile !== false && $widgetBase !== false && str_starts_with($widgetFile, $widgetBase) && is_file($widgetFile)) {
        $ext = pathinfo($widgetFile, PATHINFO_EXTENSION);
        $mime = match ($ext) {
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'html' => 'text/html; charset=UTF-8',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        readfile($widgetFile);
        exit;
    }
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

    $router = new Router();

    // Public routes
    $router->get('/', static fn ($req): Response => $landing->index($req));
    $router->get('/widget-preview', static fn ($req): Response => $landing->widgetPreview($req));

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

    $response = $router->dispatch($app->request);
    $response->send();
} catch (Throwable $e) {
    http_response_code(500);
    $isLocal = isset($app) && $app->config->isDebug();
    if ($isLocal) {
        echo '<pre style="font-family:monospace;padding:24px;">'
            . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8')
            . '</pre>';
    } else {
        echo '<h1>Er ging iets mis</h1><p>Probeer het later opnieuw.</p>';
    }
}
