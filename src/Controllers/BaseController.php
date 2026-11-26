<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\AuthService;

/**
 * Base controller with common auth, CSRF, and redirect helpers.
 */
abstract class BaseController
{
    protected App $app;
    protected AuthService $auth;

    public function __construct(App $app, AuthService $auth)
    {
        $this->app = $app;
        $this->auth = $auth;
    }

    protected function requireUser(Request $request): User|Response
    {
        $user = $this->auth->currentUser();
        if ($user === null) {
            return Response::redirect($request->url('/login'));
        }

        return $user;
    }

    protected function verifyCsrf(Request $request, string $fallbackPath): ?Response
    {
        $token = $request->post('_csrf');
        if (!$this->app->csrf->verify(is_string($token) ? $token : null)) {
            $this->app->session->flash('error', $this->app->i18n->t('error.csrf'));
            return Response::redirect($request->url($fallbackPath));
        }

        return null;
    }
}
