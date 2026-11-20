<?php

declare(strict_types=1);

namespace App\Model;

/** Rola użytkownika; wartość to kod z tabeli roles. */
enum Role: string
{
    case Guest = 'GUEST';
    case CellarMaster = 'CELLAR_MASTER';
    case WineryOwner = 'WINERY_OWNER';
}
