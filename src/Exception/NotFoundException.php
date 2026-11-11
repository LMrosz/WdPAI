<?php

declare(strict_types=1);

namespace App\Exception;

/** 404 – nie ma takiej trasy albo takiego zasobu. */
final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not found')
    {
        parent::__construct(404, $message);
    }
}
