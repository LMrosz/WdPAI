<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\TastingReminder;

/** Rezerwacje czekające na przypomnienie o degustacji; używa ich zadanie cykliczne. */
interface ReminderRepositoryInterface
{
    /**
     * Potwierdzone rezerwacje, o których jeszcze nie przypominano, a których termin zaczyna się
     * po $now i najpóźniej $withinHours godzin później. Niczego nie zmienia (tryb próbny).
     *
     * @return list<TastingReminder>
     */
    public function findDue(\DateTimeImmutable $now, int $withinHours): array;

    /**
     * Oznacza rezerwację jako przypomnianą. Zwraca false, gdy ktoś inny zdążył ją przejąć albo
     * rezerwację anulowano, więc przypomnienie wysyła się najwyżej raz, także przy równoległych uruchomieniach.
     */
    public function claim(int $reservationId, \DateTimeImmutable $now): bool;

    /** Cofa przejęcie po nieudanej wysyłce, żeby kolejne uruchomienie zadania spróbowało jeszcze raz. */
    public function release(int $reservationId): void;
}
