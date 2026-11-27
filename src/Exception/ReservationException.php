<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Rezerwacji nie da się wykonać albo anulować. Powód ($reason) to krótki kod, który kontroler API
 * zamienia na kod HTTP i komunikat z config/content.php (api_errors.<powód>):
 * session_not_found, session_past, group_too_large, not_enough_seats, already_booked, reservation_not_found.
 */
final class ReservationException extends \RuntimeException
{
    public function __construct(private readonly string $reason)
    {
        parent::__construct($reason);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
