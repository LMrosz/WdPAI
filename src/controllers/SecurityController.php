<?php

declare(strict_types=1);

namespace App\Controller;

require_once __DIR__ . '/AppController.php';

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
}
