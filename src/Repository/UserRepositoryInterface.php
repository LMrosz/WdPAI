<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\EmailAlreadyUsedException;
use App\Model\Email;
use App\Model\Role;
use App\Model\User;

/** Dostęp do kont użytkowników; kontrolery i serwisy zależą tylko od tego interfejsu. */
interface UserRepositoryInterface
{
    /** Konto po id. Obiekt nie zawiera skrótu hasła (`passwordHash` jest pusty), bo do sprawdzania sesji nie jest potrzebny. */
    public function findById(int $id): ?User;

    /** Konto po adresie e-mail, razem ze skrótem hasła: używane wyłącznie do logowania. */
    public function findByEmail(Email $email): ?User;

    /**
     * Zakłada konto i zwraca zapisany obiekt z nadanym id (jedyny odczyt, poza findByEmail, który zawiera skrót hasła).
     *
     * @throws EmailAlreadyUsedException gdy adres jest już zajęty
     */
    public function create(Email $email, string $passwordHash, Role $role, ?string $displayName = null): User;

    /**
     * Wszystkie konta od najstarszego; jedno zapytanie z JOIN-em roli (bez N+1).
     *
     * @return list<User>
     */
    public function findAll(): array;

    /** Blokuje albo odblokowuje konto; false, gdy takiego konta nie było. */
    public function setBlocked(int $id, bool $blocked): bool;

    /** Zmienia rolę konta; false, gdy takiego konta nie było. */
    public function changeRole(int $id, Role $role): bool;

    /** Usuwa konto (razem z jego rezerwacjami, ON DELETE CASCADE); false, gdy takiego konta nie było. */
    public function delete(int $id): bool;
}
