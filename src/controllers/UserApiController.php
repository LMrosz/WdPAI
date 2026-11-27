<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ApiException;
use App\Model\Permission;
use App\Model\Role;
use App\Model\User;
use App\Service\UserAdminService;

/**
 * API kont użytkowników dla panelu /admin/users (public/js/users.js). Odpowiedzi to JSON,
 * a błędy (także z routera i front controllera) mają kształt {"error": {status, reason, message}}.
 *
 * Każda akcja zaczyna się tak samo: 401 bez sesji, 403 bez uprawnienia, a zmieniające dane
 * dodatkowo wymagają tokenu CSRF w nagłówku X-CSRF-Token. Reguły (kto kogo może blokować,
 * usuwać i awansować) są w UserAdminService.
 */
final class UserApiController extends AppController
{
    private ?UserAdminService $admin = null;

    /** GET /api/users – lista kont z tym, co wolno z nimi zrobić zalogowanej osobie. */
    public function index(): void
    {
        $actor = $this->requirePermission(Permission::ManageUsers);

        $this->json([
            'users' => array_map(
                fn (User $user): array => $this->present($user, $actor),
                $this->admin()->list(),
            ),
        ]);
    }

    /** DELETE /api/users/{id} – usuwa konto (204). Własnego konta nie da się usunąć (422). */
    public function delete(string $id): void
    {
        $actor = $this->requirePermission(Permission::ManageUsers);
        $this->requireValidCsrfHeader();

        $this->admin()->delete($actor, $this->idFromPath($id));

        $this->noContent();
    }

    /**
     * PATCH /api/users/{id}/block – blokuje albo odblokowuje konto. Ciało: {"blocked": true}.
     * Zwraca zaktualizowane konto. Własnego konta nie da się zablokować (422).
     */
    public function block(string $id): void
    {
        $actor = $this->requirePermission(Permission::ManageUsers);
        $this->requireValidCsrfHeader();

        $targetId = $this->idFromPath($id);
        $blocked = $this->jsonBody()['blocked'] ?? null;

        if (!is_bool($blocked)) {
            throw new ApiException(422, 'invalid_body');
        }

        $this->json(['user' => $this->present($this->admin()->setBlocked($actor, $targetId, $blocked), $actor)]);
    }

    /**
     * PATCH /api/users/{id}/role – zmienia rolę konta. Ciało: {"role": "ADMIN"}.
     * Tylko właściciel winnicy (uprawnienie ManageAdmins); w ten sposób deleguje administratorów.
     */
    public function role(string $id): void
    {
        $actor = $this->requirePermission(Permission::ManageAdmins);
        $this->requireValidCsrfHeader();

        $targetId = $this->idFromPath($id);
        $code = $this->jsonBody()['role'] ?? null;
        $role = is_string($code) ? Role::tryFrom($code) : null;

        if ($role === null) {
            throw new ApiException(422, 'invalid_body');
        }

        $this->json(['user' => $this->present($this->admin()->changeRole($actor, $targetId, $role), $actor)]);
    }

    /**
     * Konto w odpowiedzi API. Zawiera tylko to, co panel pokazuje (bez skrótu hasła),
     * oraz flagi "co mi wolno", żeby przeglądarka nie rysowała przycisków kończących się błędem.
     *
     * @return array<string, mixed>
     */
    private function present(User $user, User $actor): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email->value(),
            'name' => $user->label(),
            'role' => $user->role->value,
            'blocked' => $user->isBlocked,
            'createdAt' => $user->createdAt->format(\DATE_ATOM),
            'isSelf' => $user->id === $actor->id,
        ] + $this->admin()->capabilities($actor, $user);
    }

    private function admin(): UserAdminService
    {
        return $this->admin ??= new UserAdminService($this->users());
    }
}
