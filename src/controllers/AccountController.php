<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Permission;

final class AccountController extends AppController
{
    /**
     * GET /account – dane konta i uprawnienia roli. Wymaga zalogowania (bez sesji: 401).
     * Konto jest czytane z bazy, więc rola jest aktualna nawet wtedy, gdy zmieniono ją po zalogowaniu.
     */
    public function show(): void
    {
        $user = $this->requireUser();

        // każdy użytkownik może przeglądać wina i degustacje; reszta wynika z uprawnień roli
        $permissions = [$this->text('permissions.base')];

        foreach ($user->role->permissions() as $permission) {
            $permissions[] = $this->text('permissions.' . $permission->value);
        }

        $this->render('account', [
            'pageTitle' => $this->text('account.page_title'),
            'activePage' => 'account',
            'user' => $user,
            'roleLabel' => $this->text('roles.' . $user->role->value),
            'since' => $user->createdAt->setTimezone(new \DateTimeZone('Europe/Warsaw'))->format('d.m.Y'),
            'permissions' => $permissions,
            'canManageWines' => $user->role->can(Permission::ManageWines),
        ], true);
    }
}
