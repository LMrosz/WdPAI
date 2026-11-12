<?php

declare(strict_types=1);

namespace App\Exception;

/** 400 – żądanie jest błędne, np. identyfikator w adresie ma zły format. */
final class BadRequestException extends HttpException
{
    public function __construct(string $message = 'Bad request')
    {
        parent::__construct(400, $message);
    }
}
