<?php

declare(strict_types=1);

namespace App\Model;

/** Uprawnienie do akcji w aplikacji; które role je mają, mówi Role::permissions(). */
enum Permission: string
{
    /** Dodawanie nowych win i edycja istniejących. */
    case ManageWines = 'manage_wines';

    /** Usuwanie win z katalogu (nieodwracalne, więc tylko dla administratorów i właściciela). */
    case DeleteWines = 'delete_wines';

    /** Panel kont: lista użytkowników, blokowanie i usuwanie kont zwykłych użytkowników. */
    case ManageUsers = 'manage_users';

    /** Zarządzanie administratorami (nadawanie i odbieranie roli, blokowanie, usuwanie): tylko właściciel. */
    case ManageAdmins = 'manage_admins';
}
