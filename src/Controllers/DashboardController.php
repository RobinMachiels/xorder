<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\AuthService;
use App\Services\DemoSeederService;

/**
 * Merchant dashboard with KPIs, backup status, and demo-data controls.
 */
final class DashboardController extends BaseController
{
    private OrderRepository $orders;
    private ProductRepository $products;
    private DemoSeederService $seeder;

    public function __construct(
        App $app,
        AuthService $auth,
        OrderRepository $orders,
        ProductRepository $products,
        DemoSeederService $seeder
    ) {
        parent::__construct($app, $auth);
        $this->orders = $orders;
        $this->products = $products;
        $this->seeder = $seeder;
    }

    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $stats = $this->orders->getDashboardStats($user->id);
        $productCount = $this->products->countActiveForUser($user->id);
        $recentOrders = $this->orders->recentForUser($user->id, 6);

        $html = $this->app->view->render('app/dashboard', [
            'pageTitle' => $this->app->i18n->t('dash.title'),
            'user' => $user,
            'stats' => $stats,
            'productCount' => $productCount,
            'recentOrders' => $recentOrders,
        ]);

        return Response::html($html);
    }

    public function clearDemo(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        if (($csrfError = $this->verifyCsrf($request, '/app')) !== null) {
            return $csrfError;
        }

        $this->seeder->clearForUser($user->id);
        $this->app->session->flash('success', $this->app->i18n->t('dash.demo_cleared'));

        return Response::redirect($request->url('/app'));
    }
}
