<?php

declare(strict_types=1);

namespace App\Controller;

final class DefaultController extends AppController
{
    public function index(): void
    {
        $this->render('home', [
            'pageTitle' => $this->text('home.page_title'),
            'activePage' => 'home',
        ]);
    }

    public function notFound(): void
    {
        $this->render('not-found', [
            'pageTitle' => $this->text('errors.not_found.page_title'),
            'activePage' => null,
        ]);
    }
}
