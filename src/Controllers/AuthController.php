<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Login, registration, and logout handlers.
 */
final class AuthController extends BaseController
{
    public function showLogin(Request $request): Response
    {
        if ($this->auth->currentUser() !== null) {
            return Response::redirect($request->url('/app'));
        }

        $html = $this->app->view->render('auth/login', [
            'pageTitle' => $this->app->i18n->t('auth.login_title'),
            'user' => null,
        ]);

        return Response::html($html);
    }

    public function login(Request $request): Response
    {
        if (($csrfError = $this->verifyCsrf($request, '/login')) !== null) {
            return $csrfError;
        }

        $email = (string) $request->post('email', '');
        $password = (string) $request->post('password', '');

        if ($this->auth->attemptLogin($email, $password)) {
            return Response::redirect($request->url('/app'));
        }

        $this->app->session->flash('error', $this->app->i18n->t('auth.login_failed'));
        return Response::redirect($request->url('/login'));
    }

    public function showRegister(Request $request): Response
    {
        if ($this->auth->currentUser() !== null) {
            return Response::redirect($request->url('/app'));
        }

        $html = $this->app->view->render('auth/register', [
            'pageTitle' => $this->app->i18n->t('auth.register_title'),
            'user' => null,
        ]);

        return Response::html($html);
    }

    public function register(Request $request): Response
    {
        $source = (string) $request->post('_source', '/register');
        $fallback = $source === 'landing' ? '/#registreren' : '/register';

        if (($csrfError = $this->verifyCsrf($request, $fallback)) !== null) {
            return $csrfError;
        }

        $businessName = (string) $request->post('business_name', '');
        $sector = (string) $request->post('sector', 'traiteur');
        $email = (string) $request->post('email', '');
        $password = (string) $request->post('password', '');
        $seedDemo = $request->post('seed_demo') === '1';

        $result = $this->auth->register($businessName, $sector, $email, $password, $seedDemo);
        if (!$result['ok']) {
            $this->app->session->flash('error', (string) ($result['error'] ?? $this->app->i18n->t('error.required_fields')));
            return Response::redirect($request->url($fallback));
        }

        $this->app->session->flash(
            'success',
            $this->app->i18n->t('auth.welcome_created', ['business' => trim($businessName)])
        );

        return Response::redirect($request->url('/app'));
    }

    public function logout(Request $request): Response
    {
        if (($csrfError = $this->verifyCsrf($request, '/app')) !== null) {
            return $csrfError;
        }

        $this->auth->logout();
        $this->app->session->flash('success', $this->app->i18n->t('auth.logged_out'));

        return Response::redirect($request->url('/'));
    }
}
