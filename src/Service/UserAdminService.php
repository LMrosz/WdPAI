<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ApiException;
use App\Model\Permission;
use App\Model\Role;
use App\Model\User;
use App\Repository\UserRepositoryInterface;

/**
 * Reguły zarządzania kontami z panelu administratora. Serwis nie zna HTTP poza kodami błędów:
 * dostaje osobę wykonującą akcję i id konta, a przy złamaniu reguły rzuca ApiException.
 *
 * Reguły (jedno miejsce, z którego korzysta i lista kont, i każda akcja):
 *  - nie można blokować, usuwać ani zmieniać roli własnego konta (422),
 *  - konto właściciela jest nietykalne (403), więc właściciel zawsze zostaje jeden,
 *  - kontami administratorów zarządza tylko właściciel (403),
 *  - rolę (GUEST, CELLAR_MASTER, ADMIN) nadaje wyłącznie właściciel; roli właściciela nadać się nie da (422).
 */
final class UserAdminService
{
    /** Role, które właściciel może nadać innym kontom. */
    public const ASSIGNABLE_ROLES = [Role::Guest, Role::CellarMaster, Role::Admin];

    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /** @return list<User> */
    public function list(): array
    {
        return $this->users->findAll();
    }

    /**
     * Co wolno osobie $actor zrobić z kontem $target; lista kont przekazuje to przeglądarce,
     * żeby nie pokazywała przycisków, które i tak skończyłyby się błędem. Serwer sprawdza reguły
     * przy każdej akcji osobno, więc ukryty przycisk nie jest zabezpieczeniem.
     *
     * @return array{canBlock: bool, canDelete: bool, canChangeRole: bool}
     */
    public function capabilities(User $actor, User $target): array
    {
        $canManage = $this->manageDenial($actor, $target) === null;

        return [
            'canBlock' => $canManage,
            'canDelete' => $canManage,
            'canChangeRole' => $this->roleDenial($actor, $target) === null,
        ];
    }

    /** @throws ApiException 404, 422 albo 403 według reguł z opisu klasy */
    public function delete(User $actor, int $id): void
    {
        $target = $this->target($id);
        $this->assertCanManage($actor, $target);

        $this->users->delete($target->id);
    }

    /** @throws ApiException */
    public function setBlocked(User $actor, int $id, bool $blocked): User
    {
        $target = $this->target($id);
        $this->assertCanManage($actor, $target);

        $this->users->setBlocked($target->id, $blocked);

        return $this->target($id);
    }

    /** @throws ApiException */
    public function changeRole(User $actor, int $id, Role $role): User
    {
        $target = $this->target($id);

        $denial = $this->roleDenial($actor, $target);

        if ($denial !== null) {
            throw $denial;
        }

        if (!in_array($role, self::ASSIGNABLE_ROLES, true)) {
            throw new ApiException(422, 'role_not_assignable');
        }

        $this->users->changeRole($target->id, $role);

        return $this->target($id);
    }

    private function target(int $id): User
    {
        return $this->users->findById($id) ?? throw new ApiException(404, 'user_not_found');
    }

    private function assertCanManage(User $actor, User $target): void
    {
        $denial = $this->manageDenial($actor, $target);

        if ($denial !== null) {
            throw $denial;
        }
    }

    /** Blokowanie i usuwanie: null, gdy wolno, a w przeciwnym razie błąd, który to wyjaśnia. */
    private function manageDenial(User $actor, User $target): ?ApiException
    {
        if ($actor->id === $target->id) {
            return new ApiException(422, 'self_action');
        }

        if ($target->role === Role::WineryOwner) {
            return new ApiException(403, 'owner_protected');
        }

        if ($target->role === Role::Admin && !$actor->role->can(Permission::ManageAdmins)) {
            return new ApiException(403, 'admin_protected');
        }

        return $actor->role->can(Permission::ManageUsers) ? null : new ApiException(403, 'forbidden');
    }

    /** Zmiana roli: tylko właściciel i tylko cudzego konta (konto właściciela jest nietykalne). */
    private function roleDenial(User $actor, User $target): ?ApiException
    {
        if (!$actor->role->can(Permission::ManageAdmins)) {
            return new ApiException(403, 'owner_only');
        }

        if ($actor->id === $target->id) {
            return new ApiException(422, 'self_action');
        }

        return $target->role === Role::WineryOwner ? new ApiException(403, 'owner_protected') : null;
    }
}
