<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\I18n;
use App\Core\Session;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Handles registration, password authentication, and current merchant lookup.
 */
final class AuthService
{
    public const DEFAULT_PICKUP_DATES = [
        '2026-12-24',
        '2026-12-25',
        '2026-12-31',
        '2027-01-01',
    ];

    public const DEFAULT_TIME_SLOTS = [
        '09:00 - 10:00',
        '10:00 - 11:00',
        '11:00 - 12:00',
        '13:00 - 15:00',
        '15:00 - 17:00',
    ];

    private UserRepository $users;
    private Session $session;
    private I18n $i18n;
    private DemoSeederService $seeder;

    public function __construct(
        UserRepository $users,
        Session $session,
        I18n $i18n,
        DemoSeederService $seeder
    ) {
        $this->users = $users;
        $this->session = $session;
        $this->i18n = $i18n;
        $this->seeder = $seeder;
    }

    public function currentUser(): ?User
    {
        $userId = $this->session->userId();
        if ($userId === null) {
            return null;
        }

        return $this->users->findById($userId);
    }

    /**
     * @return array{ok: bool, error?: string, user_id?: int}
     */
    public function register(
        string $businessName,
        string $sector,
        string $email,
        string $password,
        bool $seedDemo
    ): array {
        $businessName = trim($businessName);
        $email = trim($email);
        $allowedSectors = ['traiteur', 'slager', 'bakker', 'vishandel', 'other'];
        if (!in_array($sector, $allowedSectors, true)) {
            $sector = 'traiteur';
        }

        if ($businessName === '' || $email === '' || $password === '') {
            return ['ok' => false, 'error' => $this->i18n->t('error.required_fields')];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => $this->i18n->t('error.invalid_email')];
        }

        if (strlen($password) < 6) {
            return ['ok' => false, 'error' => $this->i18n->t('error.password_short')];
        }

        if ($this->users->findByEmail($email) !== null) {
            return ['ok' => false, 'error' => $this->i18n->t('auth.email_taken')];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->users->create(
            $businessName,
            $sector,
            $email,
            $hash,
            $this->i18n->locale(),
            self::DEFAULT_PICKUP_DATES,
            self::DEFAULT_TIME_SLOTS,
            $seedDemo
        );

        if ($seedDemo) {
            $this->seeder->seedForUser($userId);
        }

        $this->session->setUserId($userId);
        $this->session->set('locale', $this->i18n->locale());

        return ['ok' => true, 'user_id' => $userId];
    }

    public function attemptLogin(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user->passwordHash)) {
            return false;
        }

        $this->session->setUserId($user->id);
        if ($this->i18n->isSupported($user->locale)) {
            $this->i18n->setLocale($user->locale, $this->session);
        }

        return true;
    }

    public function logout(): void
    {
        $this->session->clearUser();
    }
}
