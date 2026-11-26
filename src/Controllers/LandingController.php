<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Public landing page.
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

}
