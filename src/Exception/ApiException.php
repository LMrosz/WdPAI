<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Błąd żądania do API (/api/...) z kodem HTTP i krótkim powodem, np. "self_action".
 * Powód jest kluczem komunikatu w config/content.php (api_errors.<powód>), więc odpowiedź JSON
 * niesie tekst po polsku bez żadnych napisów w kodzie.
 */
final class ApiException extends HttpException
{
    public function __construct(int $statusCode, private readonly string $reason)
    {
        parent::__construct($statusCode, $reason);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
