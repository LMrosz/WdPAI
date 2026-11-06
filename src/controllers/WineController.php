<?php

declare(strict_types=1);

namespace App\Controller;

final class WineController extends AppController
{
    /** GET /wines – katalog win winnicy (dane na razie z config/content.php, później z bazy). */
    public function index(): void
    {
        $this->render('wines', [
            'pageTitle' => $this->text('wines.page_title'),
            'activePage' => 'wines',
            'wines' => $this->content()->get('wines.items') ?? [],
        ], true);
    }
}
