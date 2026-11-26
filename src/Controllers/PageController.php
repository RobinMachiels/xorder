<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Static legal pages.
 */
final class PageController extends BaseController
{
    public function privacy(Request $request): Response
    {
        return $this->page('pages/privacy', 'privacy.title');
    }

    public function cookies(Request $request): Response
    {
        return $this->page('pages/cookies', 'cookies.title');
    }

    private function page(string $template, string $titleKey): Response
    {
        $html = $this->app->view->render($template, [
            'pageTitle' => $this->app->i18n->t($titleKey),
            'user' => $this->auth->currentUser(),
        ]);

        return Response::html($html);
    }
}
