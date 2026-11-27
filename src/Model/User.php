<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Encja: konto użytkownika (wiersz tabeli users z rolą z tabeli roles).
 * Hasło istnieje tylko jako skrót z password_hash(); jawnego hasła nigdzie nie przechowujemy.
 */
final readonly class User
{
    public function __construct(
        public int $id,
        public Email $email,
        public string $passwordHash,
        public Role $role,
        public \DateTimeImmutable $createdAt,
        public ?string $displayName = null,
        public bool $isBlocked = false,
    ) {
    }

    /** Czy hasło jawne pasuje do zapisanego skrótu. */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /** Nazwa do wyświetlenia: imię z konta albo część adresu przed @. */
    public function label(): string
    {
        return $this->displayName ?? strstr($this->email->value(), '@', true);
    }
}
