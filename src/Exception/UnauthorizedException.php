<?php

declare(strict_types=1);

namespace App\Exception;

/** 401 – akcja wymaga zalogowania, a w sesji nie ma użytkownika. */
final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Authentication required')
    {
        parent::__construct(401, $message);
    }
}
