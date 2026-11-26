<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ProductRepository;
use App\Services\AuthService;

/**
 * Manages the caterer's festive dishes and platters catalog.
 */
final class ProductController extends BaseController
{
    private ProductRepository $products;

    public function __construct(App $app, AuthService $auth, ProductRepository $products)
    {
        parent::__construct($app, $auth);
        $this->products = $products;
    }

    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $items = $this->products->allForUser($user->id);

        $html = $this->app->view->render('app/products', [
            'pageTitle' => $this->app->i18n->t('products.title'),
            'user' => $user,
            'products' => $items,
        ]);

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        if (($csrfError = $this->verifyCsrf($request, '/app/products')) !== null) {
            return $csrfError;
        }

        $name = trim((string) $request->post('name', ''));
        $category = trim((string) $request->post('category', ''));
        $unit = trim((string) $request->post('unit', 'stuk'));
        $price = (float) str_replace(',', '.', (string) $request->post('price', '0'));

        if ($name === '') {
            $this->app->session->flash('error', $this->app->i18n->t('error.required_fields'));
            return Response::redirect($request->url('/app/products'));
        }

        $this->products->create($user->id, $name, $category, $unit, max(0.0, $price), true, false);
        $this->app->session->flash('success', $this->app->i18n->t('products.saved'));

        return Response::redirect($request->url('/app/products'));
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        if (($csrfError = $this->verifyCsrf($request, '/app/products')) !== null) {
            return $csrfError;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($this->products->findForUser($id, $user->id) === null) {
            $this->app->session->flash('error', $this->app->i18n->t('error.not_found'));
            return Response::redirect($request->url('/app/products'));
        }

        $name = trim((string) $request->post('name', ''));
        $category = trim((string) $request->post('category', ''));
        $unit = trim((string) $request->post('unit', 'stuk'));
        $price = (float) str_replace(',', '.', (string) $request->post('price', '0'));
        $isActive = $request->post('is_active') === '1';

        if ($name === '') {
            $this->app->session->flash('error', $this->app->i18n->t('error.required_fields'));
            return Response::redirect($request->url('/app/products'));
        }

        $this->products->update($id, $user->id, $name, $category, $unit, max(0.0, $price), $isActive);
        $this->app->session->flash('success', $this->app->i18n->t('products.saved'));

        return Response::redirect($request->url('/app/products'));
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        if (($csrfError = $this->verifyCsrf($request, '/app/products')) !== null) {
            return $csrfError;
        }

        $id = (int) ($params['id'] ?? 0);
        $this->products->deleteForUser($id, $user->id);
        $this->app->session->flash('success', $this->app->i18n->t('products.deleted'));

        return Response::redirect($request->url('/app/products'));
    }
}
