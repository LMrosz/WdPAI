<?php

declare(strict_types=1);

namespace App\Model;

/** Potwierdzona rezerwacja, o której trzeba przypomnieć gościowi (adresat, termin i liczba miejsc). */
final readonly class TastingReminder
{
    public function __construct(
        public int $reservationId,
        public string $email,
        public string $userName,
        public int $seats,
        public TastingSession $session,
    ) {
    }
}
