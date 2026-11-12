<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Validator;

final class SecurityController extends AppController
{
    /** Limity długości pól (ochrona przed zbyt długimi danymi). */
    private const EMAIL_MAX_LENGTH = 254;
    private const PASSWORD_MIN_LENGTH = 8;
    private const PASSWORD_MAX_LENGTH = 64;

    /** GET /login – formularz logowania (GUEST, CELLAR_MASTER, WINERY_OWNER). */
    public function login(): void
    {
        $this->renderLogin();
    }

    /**
     * POST /login – obsługa formularza.
     * Błędne dane wracają do formularza z kodem 422 (e-mail zostaje wpisany, hasło nie).
     * Poprawny formularz przekierowuje na stronę główną (303); uwierzytelnianie dojdzie z bazą i sesją.
     */
    public function authenticate(): void
    {
        $email = trim($this->postString('email'));

        $validator = (new Validator([
            'email' => $email,
            'password' => $this->postString('password'),
        ]))
            ->required('email')
            ->email('email')
            ->maxLength('email', self::EMAIL_MAX_LENGTH)
            ->required('password')
            ->maxLength('password', self::PASSWORD_MAX_LENGTH);

        if ($validator->fails()) {
            http_response_code(422);
            $this->renderLogin($this->validationMessages($validator), ['email' => $email]);

            return;
        }

        $this->redirect('/');
    }

    /** GET /register – formularz rejestracji. */
    public function register(): void
    {
        $this->renderRegister();
    }

    /**
     * POST /register – obsługa formularza.
     * Błędy wracają do formularza z kodem 422. Poprawne dane kończą się przekierowaniem (303)
     * na stronę logowania z komunikatem; konto nie jest jeszcze zapisywane, bo nie ma bazy danych.
     */
    public function createAccount(): void
    {
        $email = trim($this->postString('email'));

        $validator = (new Validator([
            'email' => $email,
            'password' => $this->postString('password'),
            'password_confirmation' => $this->postString('password_confirmation'),
        ]))
            ->required('email')
            ->email('email')
            ->maxLength('email', self::EMAIL_MAX_LENGTH)
            ->required('password')
            ->minLength('password', self::PASSWORD_MIN_LENGTH)
            ->maxLength('password', self::PASSWORD_MAX_LENGTH)
            ->letterAndDigit('password')
            ->specialCharacter('password')
            ->required('password_confirmation')
            ->matches('password_confirmation', 'password');

        if ($validator->fails()) {
            http_response_code(422);
            $this->renderRegister($this->validationMessages($validator), ['email' => $email]);

            return;
        }

        $this->redirect('/login?registered=1');
    }

    /**
     * @param array<string, string> $errors nazwa pola => komunikat
     * @param array<string, string> $old wcześniej wpisane wartości (bez hasła)
     */
    private function renderLogin(array $errors = [], array $old = []): void
    {
        $this->render('login', [
            'pageTitle' => $this->text('login.page_title'),
            'activePage' => 'login',
            'formAction' => '/login',
            'errors' => $errors,
            'old' => $old,
            'registered' => ($_GET['registered'] ?? null) === '1',
        ], true);
    }

    /**
     * @param array<string, string> $errors nazwa pola => komunikat
     * @param array<string, string> $old wcześniej wpisane wartości (bez haseł)
     */
    private function renderRegister(array $errors = [], array $old = []): void
    {
        $this->render('register', [
            'pageTitle' => $this->text('register.page_title'),
            'activePage' => 'login',
            'formAction' => '/register',
            'errors' => $errors,
            'old' => $old,
            'passwordMinLength' => self::PASSWORD_MIN_LENGTH,
        ], true);
    }
}
