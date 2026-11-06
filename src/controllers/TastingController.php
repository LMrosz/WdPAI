<?php

declare(strict_types=1);

namespace App\Controller;

final class TastingController extends AppController
{
    /** GET /tastings – oferta degustacji (dane na razie z config/content.php, później z bazy). */
    public function index(): void
    {
        $this->render('tastings', [
            'pageTitle' => $this->text('tastings.page_title'),
            'activePage' => 'tastings',
            'offers' => $this->content()->get('tastings.offers') ?? [],
            'steps' => $this->content()->get('tastings.steps') ?? [],
        ]);
    }
}
