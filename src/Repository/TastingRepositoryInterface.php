<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Tasting;

/** Oferta degustacji; kontroler zależy tylko od tego interfejsu. */
interface TastingRepositoryInterface
{
    /**
     * Wszystkie degustacje w kolejności z katalogu, każda z winami i przyszłymi terminami.
     *
     * @return list<Tasting>
     */
    public function findAll(): array;
}
