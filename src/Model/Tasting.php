<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Oferta degustacji razem z winami, które się w niej pojawiają, i najbliższymi terminami.
 * Cena jest gotowym napisem do wyświetlenia (jak w Wine), np. "90 zł".
 */
final readonly class Tasting
{
    /**
     * @param list<TastedWine>     $wines
     * @param list<TastingSession> $sessions
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public int $durationMinutes,
        public int $maxGroupSize,
        public string $pricePerPerson,
        public ?string $badge = null,
        public array $wines = [],
        public array $sessions = [],
    ) {
    }

    /** Czas trwania po polsku: "45 minut", "1,5 godziny", "2 godziny", "5 godzin". */
    public function durationLabel(): string
    {
        if ($this->durationMinutes < 60) {
            return $this->durationMinutes . ' min';
        }

        if ($this->durationMinutes % 60 !== 0) {
            return number_format($this->durationMinutes / 60, 1, ',', '') . ' godziny';
        }

        $whole = intdiv($this->durationMinutes, 60);
        $lastDigit = $whole % 10;
        $lastTwoDigits = $whole % 100;

        $word = match (true) {
            $whole === 1 => 'godzina',
            $lastDigit >= 2 && $lastDigit <= 4 && ($lastTwoDigits < 12 || $lastTwoDigits > 14) => 'godziny',
            default => 'godzin',
        };

        return $whole . ' ' . $word;
    }
}
