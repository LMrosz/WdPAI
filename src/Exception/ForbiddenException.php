<?php

declare(strict_types=1);

namespace App\Exception;

/** 403 – użytkownik jest zalogowany, ale jego rola nie pozwala na tę akcję (albo token CSRF jest błędny). */
final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct(403, $message);
    }
}
