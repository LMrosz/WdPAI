<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Termin degustacji z liczbą miejsc; zajęte miejsca to potwierdzone rezerwacje (widok
 * tasting_session_availability). $maxSeats to największa liczba osób w jednej rezerwacji.
 */
final readonly class TastingSession
{
    private const TIMEZONE = 'Europe/Warsaw';

    private const MONTHS = [
        1 => 'stycznia', 2 => 'lutego', 3 => 'marca', 4 => 'kwietnia', 5 => 'maja', 6 => 'czerwca',
        7 => 'lipca', 8 => 'sierpnia', 9 => 'września', 10 => 'października', 11 => 'listopada', 12 => 'grudnia',
    ];

    public function __construct(
        public int $id,
        public \DateTimeImmutable $startsAt,
        public int $seatsTotal,
        public int $seatsBooked,
        public int $tastingId = 0,
        public string $tastingName = '',
        public int $maxSeats = 12,
    ) {
    }

    public function seatsFree(): int
    {
        return max(0, $this->seatsTotal - $this->seatsBooked);
    }

    /** Data i godzina w czasie lokalnym winnicy, np. "5 grudnia 2026, 14:00". */
    public function startsAtLabel(): string
    {
        $local = $this->startsAt->setTimezone(new \DateTimeZone(self::TIMEZONE));

        return sprintf(
            '%d %s %d, %s',
            (int) $local->format('j'),
            self::MONTHS[(int) $local->format('n')],
            (int) $local->format('Y'),
            $local->format('H:i'),
        );
    }
}
