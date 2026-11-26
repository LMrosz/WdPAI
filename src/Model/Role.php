<?php

declare(strict_types=1);

namespace App\Model;

/** Rola użytkownika; wartość to kod z tabeli roles. */
enum Role: string
{
    case Guest = 'GUEST';
    case CellarMaster = 'CELLAR_MASTER';
    case WineryOwner = 'WINERY_OWNER';

    /**
     * Uprawnienia roli. Gość ma tylko to, co mają wszyscy (przeglądanie win i degustacji),
     * piwniczy zarządza katalogiem win, a właściciel dodatkowo je usuwa.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Guest => [],
            self::CellarMaster => [Permission::ManageWines],
            self::WineryOwner => [Permission::ManageWines, Permission::DeleteWines],
        };
    }

    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
