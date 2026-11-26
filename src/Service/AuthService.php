<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\EmailAlreadyUsedException;
use App\Model\Email;
use App\Model\Role;
use App\Model\User;
use App\Repository\UserRepositoryInterface;

/**
 * Rejestracja i weryfikacja haseł. Serwis nie zna sesji ani HTTP: dostaje teksty z formularza,
 * a zwraca obiekt User, więc da się go uruchomić także ze skryptu (bin/demo-auth.php).
 */
final class AuthService
{
    /** Skrót "na zapas", żeby logowanie na nieistniejący adres trwało tyle samo co na istniejący. */
    private static ?string $dummyHash = null;

    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /**
     * Zakłada konto gościa. Rola nie pochodzi z formularza: każdy nowy użytkownik jest GUEST,
     * a wyższe role nadaje się ręcznie w bazie.
     *
     * @throws \InvalidArgumentException gdy adres e-mail jest niepoprawny
     * @throws EmailAlreadyUsedException gdy adres jest już zajęty (także przy równoległej rejestracji)
     */
    public function register(string $email, string $password): User
    {
        return $this->users->create(
            new Email($email),
            password_hash($password, PASSWORD_DEFAULT),
            Role::Guest,
        );
    }

    /**
     * Zwraca użytkownika, gdy adres i hasło są poprawne, w przeciwnym razie null.
     * Nie ujawnia, czy konto istnieje: zły adres i złe hasło dają ten sam wynik.
     */
    public function authenticate(string $email, string $password): ?User
    {
        try {
            $user = $this->users->findByEmail(new Email($email));
        } catch (\InvalidArgumentException) {
            $user = null;
        }

        if ($user === null) {
            password_verify($password, self::dummyHash());

            return null;
        }

        return $user->verifyPassword($password) ? $user : null;
    }

    private static function dummyHash(): string
    {
        return self::$dummyHash ??= password_hash('dummy-password-for-timing', PASSWORD_DEFAULT);
    }
}
