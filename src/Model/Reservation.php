<?php

declare(strict_types=1);

namespace App\Model;

/** Rezerwacja miejsc w terminie degustacji (wiersz tabeli reservations). */
final readonly class Reservation
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $sessionId,
        public int $seats,
        public string $status,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }
}
