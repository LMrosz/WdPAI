<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Permission;
use App\Model\Role;
use App\Service\UserAdminService;

final class AdminController extends AppController
{
    /**
     * GET /admin/users – panel kont. Sama strona nie zawiera listy: pobiera ją public/js/users.js
     * z GET /api/users. Wymaga zalogowania (401) i uprawnienia ManageUsers (403).
     * Token CSRF dla żądań z JavaScriptu trafia do atrybutu data-csrf-token kontenera panelu.
     */
    public function index(): void
    {
        $user = $this->requirePermission(Permission::ManageUsers);

        $this->render('admin-users', [
            'pageTitle' => $this->text('admin.page_title'),
            'activePage' => 'admin',
            'canManageAdmins' => $user->role->can(Permission::ManageAdmins),
            'assignableRoles' => array_map(static fn (Role $role): string => $role->value, UserAdminService::ASSIGNABLE_ROLES),
            'pageScripts' => ['/js/users.js'],
        ], true);
    }
}
