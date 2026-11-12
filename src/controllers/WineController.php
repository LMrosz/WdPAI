<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Model\Wine;
use App\Repository\ContentWineRepository;
use App\Repository\WineRepositoryInterface;

final class WineController extends AppController
{
    private readonly WineRepositoryInterface $wines;

    public function __construct(?WineRepositoryInterface $wines = null)
    {
        $this->wines = $wines ?? new ContentWineRepository($this->content());
    }

    /**
     * GET /wines[?type=red|white|rose|sparkling] – katalog win, opcjonalnie przefiltrowany.
     * Nieznana wartość type (albo tablica, np. ?type[]=x) to błąd klienta: 400.
     */
    public function index(): void
    {
        $category = $this->categoryFromQuery();

        $this->render('wines', [
            'pageTitle' => $this->text('wines.page_title'),
            'activePage' => 'wines',
            'wines' => $this->wines->findAll($category),
            'filters' => $this->content()->get('wines.filters') ?? [],
            'activeFilter' => $category ?? 'all',
        ], true);
    }

    /**
     * GET /wines/{id} – szczegóły jednego wina.
     * Id, które nie jest dodatnią liczbą całkowitą, daje 400; poprawne id bez wina daje 404.
     */
    public function show(string $id): void
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            throw new BadRequestException('Wine id must be a positive integer, got "' . $id . '"');
        }

        $wine = $this->wines->findById((int) $id);

        if ($wine === null) {
            throw new NotFoundException('Wine ' . $id . ' not found');
        }

        $this->render('wine', [
            'pageTitle' => $this->text('wines.detail.page_title', ['wine' => $wine->title()]),
            'activePage' => 'wines',
            'wine' => $wine,
            'related' => $this->wines->findRelated($wine),
        ], true);
    }

    private function categoryFromQuery(): ?string
    {
        if (!array_key_exists('type', $_GET)) {
            return null;
        }

        $type = $_GET['type'];

        if (!is_string($type) || !in_array($type, Wine::CATEGORIES, true)) {
            throw new BadRequestException('Unknown wine type filter');
        }

        return $type;
    }
}
