<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Wine;

/** Źródło win, treść z pliku — kontrolery zależą tylko od tego interfejsu. */
interface WineRepositoryInterface
{
    /**
     * @param string|null $category jedna z Wine::CATEGORIES albo null (wszystkie wina)
     * @return list<Wine>
     */
    public function findAll(?string $category = null): array;

    public function findById(int $id): ?Wine;

    /**
     * Wina do sekcji "Zobacz także": najpierw z tej samej kategorii, potem pozostałe, bez samego $wine.
     *
     * @return list<Wine>
     */
    public function findRelated(Wine $wine, int $limit = 3): array;
}
