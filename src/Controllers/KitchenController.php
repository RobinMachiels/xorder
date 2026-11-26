<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\OrderRepository;
use App\Services\AuthService;

/**
 * Aggregated kitchen preparation summary grouped by pickup date and dish.
 */
final class KitchenController extends BaseController
{
    private OrderRepository $orders;

    public function __construct(App $app, AuthService $auth, OrderRepository $orders)
    {
        parent::__construct($app, $auth);
        $this->orders = $orders;
    }

    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $pickupDate = trim((string) $request->query('pickup_date', ''));
        $rows = $this->orders->getKitchenSummary($user->id, $pickupDate);

        $availableDates = array_values(array_unique(array_merge(
            $user->pickupDates,
            $this->orders->distinctPickupDates($user->id)
        )));
        sort($availableDates);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['pickup_date']][] = $row;
        }

        $html = $this->app->view->render('app/kitchen', [
            'pageTitle' => $this->app->i18n->t('kitchen.title'),
            'user' => $user,
            'grouped' => $grouped,
            'selectedDate' => $pickupDate,
            'availableDates' => $availableDates,
        ]);

        return Response::html($html);
    }
}
