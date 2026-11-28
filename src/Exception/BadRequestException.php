<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * 400 – żądanie jest błędne, np. identyfikator w adresie ma zły format.
 * Kod pozostaje 400 (kryterium A5), a strona błędu może dodatkowo wskazać poprawną wersję strony.
 */
final class BadRequestException extends HttpException
{
    /** @param string|null $returnTo ścieżka w aplikacji z listy errors.bad_request.return (np. "/wines") */
    public function __construct(string $message = 'Bad request', private readonly ?string $returnTo = null)
    {
        parent::__construct(400, $message);
    }

    public function returnTo(): ?string
    {
        return $this->returnTo;
    }
}
