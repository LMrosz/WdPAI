<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\ReservationException;
use App\Model\Reservation;

/** Rezerwacje miejsc w terminach degustacji; kontrolery zależą tylko od tego interfejsu. */
interface ReservationRepositoryInterface
{
    /**
     * Rezerwuje miejsca w terminie w jednej transakcji: blokuje wiersz terminu, sprawdza limity
     * i zapisuje rezerwację, więc dwie równoczesne rezerwacje nie sprzedadzą tych samych miejsc.
     * Wcześniej anulowaną rezerwację tego użytkownika na ten termin przywraca z nową liczbą miejsc.
     *
     * @throws ReservationException session_not_found, session_past, group_too_large,
     *                              not_enough_seats albo already_booked
     */
    public function book(int $userId, int $sessionId, int $seats): Reservation;

    /**
     * Anuluje potwierdzoną rezerwację użytkownika (miejsca wracają do puli).
     *
     * @throws ReservationException reservation_not_found, gdy nie ma takiej rezerwacji
     *                              albo należy do kogoś innego
     */
    public function cancel(int $reservationId, int $userId): Reservation;

    /**
     * Potwierdzone rezerwacje użytkownika na przyszłe terminy, kluczem jest id terminu.
     *
     * @return array<int, Reservation>
     */
    public function findConfirmedByUser(int $userId): array;
}
