<?php

declare(strict_types=1);

namespace App\Model;

/** Rola użytkownika; wartość to kod z tabeli roles. */
enum Role: string
{
    case Guest = 'GUEST';
    case CellarMaster = 'CELLAR_MASTER';
    case Admin = 'ADMIN';
    case WineryOwner = 'WINERY_OWNER';

    /**
     * Uprawnienia roli. Gość ma tylko to, co mają wszyscy (przeglądanie win i degustacji),
     * piwniczy zarządza katalogiem win, a administrator dodatkowo usuwa wina i zarządza kontami.
     * Właściciel winnicy (jedna osoba) ma wszystko, co administrator, a do tego zarządza samymi
     * administratorami: nadaje im rolę, odbiera ją, blokuje i usuwa ich konta.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Guest => [],
            self::CellarMaster => [Permission::ManageWines],
            self::Admin => [Permission::ManageWines, Permission::DeleteWines, Permission::ManageUsers],
            self::WineryOwner => [
                Permission::ManageWines,
                Permission::DeleteWines,
                Permission::ManageUsers,
                Permission::ManageAdmins,
            ],
        };
    }

    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
