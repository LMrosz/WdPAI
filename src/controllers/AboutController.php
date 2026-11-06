<?php

declare(strict_types=1);

namespace App\Controller;

final class AboutController extends AppController
{
    /** GET /about – opis posiadłości (treść na razie z config/content.php, później z bazy). */
    public function index(): void
    {
        $this->render('about', [
            'pageTitle' => $this->text('about.page_title'),
            'activePage' => 'about',
            'history' => $this->content()->get('about.history') ?? [],
            'facts' => $this->content()->get('about.facts') ?? [],
            'features' => $this->content()->get('about.features') ?? [],
        ]);
    }
}
