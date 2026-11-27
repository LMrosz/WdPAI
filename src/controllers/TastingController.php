<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database;
use App\Repository\PostgresTastingRepository;
use App\Repository\TastingRepositoryInterface;

final class TastingController extends AppController
{
    private readonly TastingRepositoryInterface $tastings;

    public function __construct(?TastingRepositoryInterface $tastings = null)
    {
        $this->tastings = $tastings ?? new PostgresTastingRepository((new Database())->connect());
    }

    /** GET /tastings – oferta degustacji z winami i najbliższymi terminami (z bazy). */
    public function index(): void
    {
        $this->render('tastings', [
            'pageTitle' => $this->text('tastings.page_title'),
            'activePage' => 'tastings',
            'tastings' => $this->tastings->findAll(),
            'steps' => $this->content()->get('tastings.steps') ?? [],
            'pageScripts' => ['/js/reservations.js'],
        ], true);
    }
}
