<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Public landing page and interactive AssurIO sticky widget preview.
 */
final class LandingController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = $this->auth->currentUser();
        $html = $this->app->view->render('landing/index', [
            'pageTitle' => $this->app->i18n->t('brand.full'),
            'user' => $user,
            'isLanding' => true,
        ]);

        return Response::html($html);
    }

    public function widgetPreview(Request $request): Response
    {
        $html = $this->app->view->render('landing/widget-preview', [
            'pageTitle' => $this->app->i18n->t('widget_preview.title'),
            'widgetConfig' => [
                'startDate' => (string) $this->app->config->get('widget.start_date', '2026-10-15'),
                'endDate' => (string) $this->app->config->get('widget.end_date', '2027-01-05'),
                'position' => (string) $this->app->config->get('widget.position', 'bottom-right'),
                'dismissDays' => (int) $this->app->config->get('widget.dismiss_days', 14),
            ],
        ], null);

        return Response::html($html);
    }
}
