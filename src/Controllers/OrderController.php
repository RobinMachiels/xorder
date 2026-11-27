<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\AuthService;

/**
 * Full CRUD, quick status updates, filtering, and pickup slip printing for orders.
 */
final class OrderController extends BaseController
{
    private OrderRepository $orders;
    private ProductRepository $products;

    public function __construct(
        App $app,
        AuthService $auth,
        OrderRepository $orders,
        ProductRepository $products
    ) {
        parent::__construct($app, $auth);
        $this->orders = $orders;
        $this->products = $products;
    }

    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $filters = [
            'search' => (string) $request->query('search', ''),
            'pickup_date' => (string) $request->query('pickup_date', ''),
            'status' => (string) $request->query('status', ''),
            'payment_status' => (string) $request->query('payment_status', ''),
        ];

        $orders = $this->orders->listForUser($user->id, $filters);
        $availableDates = array_values(array_unique(array_merge(
            $user->pickupDates,
            $this->orders->distinctPickupDates($user->id)
        )));
        sort($availableDates);

        $html = $this->app->view->render('app/orders', [
            'pageTitle' => $this->app->i18n->t('orders.title'),
            'user' => $user,
            'orders' => $orders,
            'filters' => $filters,
            'availableDates' => $availableDates,
        ]);

        return Response::html($html);
    }

    public function create(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $catalog = $this->products->activeForUser($user->id);

        $html = $this->app->view->render('app/order-form', [
            'pageTitle' => $this->app->i18n->t('orders.new'),
            'user' => $user,
            'order' => null,
            'catalog' => $catalog,
        ]);

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        if (($csrfError = $this->verifyCsrf($request, '/app/order-new')) !== null) {
            return $csrfError;
        }

        $payload = $this->extractOrderPayload($request);
        $items = $this->extractOrderItems($request, $user->id);

        if ($payload['customer_name'] === '' || $payload['pickup_date'] === '') {
            $this->app->session->flash('error', $this->app->i18n->t('error.required_fields'));
            return Response::redirect($request->url('/app/order-new'));
        }

        if ($items === []) {
            $this->app->session->flash('error', $this->app->i18n->t('orders.empty_items_error'));
            return Response::redirect($request->url('/app/order-new'));
        }

        $orderId = $this->orders->createWithItems($user->id, $payload, $items);
        $saved = $this->orders->findForUserWithItems($orderId, $user->id);
        $orderNo = $saved !== null ? $saved->orderNumber : '#' . $orderId;

        $this->app->session->flash('success', $this->app->i18n->t('orders.saved', ['number' => $orderNo]));
        return Response::redirect($request->url('/app/orders'));
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $orderId = (int) ($params['id'] ?? 0);
        $order = $this->orders->findForUserWithItems($orderId, $user->id);
        if ($order === null) {
            $this->app->session->flash('error', $this->app->i18n->t('error.not_found'));
            return Response::redirect($request->url('/app/orders'));
        }

        $catalog = $this->products->activeForUser($user->id);

        $html = $this->app->view->render('app/order-form', [
            'pageTitle' => $this->app->i18n->t('orders.edit') . ' ' . $order->orderNumber,
            'user' => $user,
            'order' => $order,
            'catalog' => $catalog,
        ]);

        return Response::html($html);
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

        $orderId = (int) ($params['id'] ?? 0);
        $editPath = '/app/order-edit';
        $editQuery = ['id' => $orderId];

        if (($csrfError = $this->verifyCsrf($request, $editPath, $editQuery)) !== null) {
            return $csrfError;
        }

        $existing = $this->orders->findForUserWithItems($orderId, $user->id);
        if ($existing === null) {
            $this->app->session->flash('error', $this->app->i18n->t('error.not_found'));
            return Response::redirect($request->url('/app/orders'));
        }

        $payload = $this->extractOrderPayload($request);
        $items = $this->extractOrderItems($request, $user->id);

        if ($payload['customer_name'] === '' || $payload['pickup_date'] === '') {
            $this->app->session->flash('error', $this->app->i18n->t('error.required_fields'));
            return Response::redirect($request->url($editPath, $editQuery));
        }

        if ($items === []) {
            $this->app->session->flash('error', $this->app->i18n->t('orders.empty_items_error'));
            return Response::redirect($request->url($editPath, $editQuery));
        }

        $this->orders->updateWithItems($orderId, $user->id, $payload, $items);
        $this->app->session->flash('success', $this->app->i18n->t('orders.saved', ['number' => $existing->orderNumber]));

        return Response::redirect($request->url('/app/orders'));
    }

    /**
     * @param array<string, string> $params
     */
    public function updateStatus(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        if (($csrfError = $this->verifyCsrf($request, '/app/orders')) !== null) {
            return $csrfError;
        }

        $orderId = (int) ($params['id'] ?? 0);
        $status = (string) $request->post('status', 'confirmed');
        $paymentStatus = (string) $request->post('payment_status', 'unpaid');

        if (!in_array($status, Order::STATUSES, true)) {
            $status = 'confirmed';
        }
        if (!in_array($paymentStatus, Order::PAYMENT_STATUSES, true)) {
            $paymentStatus = 'unpaid';
        }

        $this->orders->updateQuickStatus($orderId, $user->id, $status, $paymentStatus);
        $this->app->session->flash('success', $this->app->i18n->t('orders.status_updated'));

        return Response::redirect($request->url('/app/orders'));
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

        if (($csrfError = $this->verifyCsrf($request, '/app/orders')) !== null) {
            return $csrfError;
        }

        $orderId = (int) ($params['id'] ?? 0);
        $this->orders->deleteForUser($orderId, $user->id);
        $this->app->session->flash('success', $this->app->i18n->t('orders.deleted'));

        return Response::redirect($request->url('/app/orders'));
    }

    /**
     * @param array<string, string> $params
     */
    public function printSlip(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $orderId = (int) ($params['id'] ?? 0);
        $order = $this->orders->findForUserWithItems($orderId, $user->id);
        if ($order === null) {
            $this->app->session->flash('error', $this->app->i18n->t('error.not_found'));
            return Response::redirect($request->url('/app/orders'));
        }

        $html = $this->app->view->render('app/order-print', [
            'pageTitle' => $this->app->i18n->t('orders.print_title') . ' ' . $order->orderNumber,
            'user' => $user,
            'order' => $order,
        ], null);

        return Response::html($html);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractOrderPayload(Request $request): array
    {
        $status = (string) $request->post('status', 'confirmed');
        if (!in_array($status, Order::STATUSES, true)) {
            $status = 'confirmed';
        }

        $paymentStatus = (string) $request->post('payment_status', 'unpaid');
        if (!in_array($paymentStatus, Order::PAYMENT_STATUSES, true)) {
            $paymentStatus = 'unpaid';
        }

        $deposit = (float) str_replace(',', '.', (string) $request->post('deposit_amount', '0'));

        return [
            'customer_name' => trim((string) $request->post('customer_name', '')),
            'customer_phone' => trim((string) $request->post('customer_phone', '')),
            'customer_email' => trim((string) $request->post('customer_email', '')),
            'pickup_date' => trim((string) $request->post('pickup_date', '')),
            'pickup_slot' => trim((string) $request->post('pickup_slot', '')),
            'status' => $status,
            'payment_status' => $paymentStatus,
            'deposit_amount' => max(0.0, $deposit),
            'notes' => trim((string) $request->post('notes', '')),
        ];
    }

    /**
     * @return array<int, array{product_id: ?int, product_name: string, unit: string, quantity: float, unit_price: float}>
     */
    private function extractOrderItems(Request $request, int $userId): array
    {
        $productIds = $request->post('item_product_id', []);
        $names = $request->post('item_name', []);
        $units = $request->post('item_unit', []);
        $quantities = $request->post('item_qty', []);
        $prices = $request->post('item_price', []);

        if (!is_array($names)) {
            return [];
        }

        $items = [];
        foreach ($names as $index => $rawName) {
            $name = trim((string) $rawName);
            $qty = (float) str_replace(',', '.', (string) ($quantities[$index] ?? 0));
            $price = (float) str_replace(',', '.', (string) ($prices[$index] ?? 0));
            $unit = trim((string) ($units[$index] ?? 'stuk'));

            if ($name === '' || $qty <= 0.0) {
                continue;
            }

            $rawPid = $productIds[$index] ?? '';
            $productId = null;
            if (is_scalar($rawPid) && ctype_digit((string) $rawPid)) {
                $candidateId = (int) $rawPid;
                if ($this->products->findForUser($candidateId, $userId) !== null) {
                    $productId = $candidateId;
                }
            }

            $items[] = [
                'product_id' => $productId,
                'product_name' => $name,
                'unit' => $unit !== '' ? $unit : 'stuk',
                'quantity' => round($qty, 2),
                'unit_price' => max(0.0, round($price, 2)),
            ];
        }

        return $items;
    }
}
