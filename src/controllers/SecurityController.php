<?php

declare(strict_types=1);

namespace App\Controller;

final class SecurityController extends AppController
{
    /** GET /login – formularz logowania (GUEST, CELLAR_MASTER, WINERY_OWNER). */
    public function login(): void
    {
        $this->render('login', [
            'pageTitle' => 'VinoKeep — logowanie',
            'formAction' => '/login',
        ]);
    }

    /**
     * POST /login – obsługa formularza.
     * Tymczasowo bez uwierzytelniania (dojdzie z bazą danych i sesją): puste pola wracają
     * do formularza z kodem 422, poprawnie wypełniony formularz przekierowuje na stronę główną.
     */
    public function authenticate(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            http_response_code(422);
            $this->login();

            return;
        }

        $this->redirect('/');
    }
}
