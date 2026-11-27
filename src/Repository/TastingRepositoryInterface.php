<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Tasting;
use App\Model\TastingSession;

/** Oferta degustacji; kontroler zależy tylko od tego interfejsu. */
interface TastingRepositoryInterface
{
    /**
     * Wszystkie degustacje w kolejności z katalogu, każda z winami i przyszłymi terminami.
     *
     * @return list<Tasting>
     */
    public function findAll(): array;

    /**
     * Przyszłe terminy wszystkich degustacji od najbliższego (dla API, bez reszty oferty).
     *
     * @return list<TastingSession>
     */
    public function findUpcomingSessions(): array;

    /** Jeden termin z aktualną liczbą wolnych miejsc; null, gdy takiego terminu nie ma. */
    public function findSession(int $sessionId): ?TastingSession;
}
