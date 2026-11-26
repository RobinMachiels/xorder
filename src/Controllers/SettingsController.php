<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;
use App\Services\AuthService;

/**
 * Account preferences for pickup dates, time slots, and preferred language.
 */
final class SettingsController extends BaseController
{
    private UserRepository $users;

    public function __construct(App $app, AuthService $auth, UserRepository $users)
    {
        parent::__construct($app, $auth);
        $this->users = $users;
    }

    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $html = $this->app->view->render('app/settings', [
            'pageTitle' => $this->app->i18n->t('settings.title'),
            'user' => $user,
        ]);

        return Response::html($html);
    }

    public function update(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        if (($csrfError = $this->verifyCsrf($request, '/app/settings')) !== null) {
            return $csrfError;
        }

        $businessName = trim((string) $request->post('business_name', $user->businessName));
        $sector = (string) $request->post('sector', $user->sector);
        $locale = (string) $request->post('locale', $user->locale);

        if ($businessName === '') {
            $this->app->session->flash('error', $this->app->i18n->t('error.required_fields'));
            return Response::redirect($request->url('/app/settings'));
        }

        if (!$this->app->i18n->isSupported($locale)) {
            $locale = $user->locale;
        }

        $rawDates = preg_split('/\R/', (string) $request->post('pickup_dates', '')) ?: [];
        $dates = [];
        foreach ($rawDates as $line) {
            $d = trim($line);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1) {
                $dates[] = $d;
            }
        }
        if ($dates === []) {
            $dates = AuthService::DEFAULT_PICKUP_DATES;
        }
        $dates = array_values(array_unique($dates));
        sort($dates);

        $rawSlots = preg_split('/\R/', (string) $request->post('time_slots', '')) ?: [];
        $slots = [];
        foreach ($rawSlots as $line) {
            $s = trim($line);
            if ($s !== '') {
                $slots[] = $s;
            }
        }
        if ($slots === []) {
            $slots = AuthService::DEFAULT_TIME_SLOTS;
        }

        $this->users->updateSettings($user->id, $businessName, $sector, $locale, $dates, array_values(array_unique($slots)));
        $this->app->i18n->setLocale($locale, $this->app->session);
        $this->app->session->flash('success', $this->app->i18n->t('settings.saved'));

        return Response::redirect($request->url('/app/settings'));
    }
}
