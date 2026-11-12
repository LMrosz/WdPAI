<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Błąd, który ma zostać pokazany użytkownikowi jako odpowiedź HTTP z danym kodem
 * (np. 400, 404). Rzucamy go z dowolnego miejsca w aplikacji, a front controller
 * zamienia go na stronę błędu.
 */
abstract class HttpException extends \RuntimeException
{
    public function __construct(private readonly int $statusCode, string $message = '')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
