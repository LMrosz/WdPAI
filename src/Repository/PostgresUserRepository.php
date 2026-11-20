<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\EmailAlreadyUsedException;
use App\Model\Email;
use App\Model\Role;
use App\Model\User;
use PDO;
use PDOException;

/** Konta z PostgreSQL (tabele users i roles). Cały SQL dotyczący użytkowników jest tutaj. */
final class PostgresUserRepository implements UserRepositoryInterface
{
    /** Rola jest łączona JOIN-em, bo w users trzymamy tylko jej klucz obcy. */
    private const SELECT = 'SELECT u.id, u.email, u.password_hash, u.display_name, u.created_at, r.code AS role
        FROM users u
        JOIN roles r ON r.id = u.role_id';

    private const UNIQUE_VIOLATION = '23505';

    public function __construct(private readonly PDO $database)
    {
    }

    public function findById(int $id): ?User
    {
        $statement = $this->database->prepare(self::SELECT . ' WHERE u.id = :id');
        $statement->execute(['id' => $id]);

        return $this->mapRow($statement->fetch());
    }

    public function findByEmail(Email $email): ?User
    {
        $statement = $this->database->prepare(self::SELECT . ' WHERE u.email = :email');
        $statement->execute(['email' => $email->value()]);

        return $this->mapRow($statement->fetch());
    }

    public function create(Email $email, string $passwordHash, Role $role, ?string $displayName = null): User
    {
        $statement = $this->database->prepare(
            'INSERT INTO users (email, password_hash, display_name, role_id)
             VALUES (:email, :password_hash, :display_name, (SELECT id FROM roles WHERE code = :role))
             RETURNING id'
        );

        try {
            $statement->execute([
                'email' => $email->value(),
                'password_hash' => $passwordHash,
                'display_name' => $displayName,
                'role' => $role->value,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === self::UNIQUE_VIOLATION) {
                throw new EmailAlreadyUsedException('Ten adres e-mail jest już zajęty.', 0, $exception);
            }

            throw $exception;
        }

        return $this->findById((int) $statement->fetchColumn())
            ?? throw new \LogicException('Konto zniknęło tuż po zapisie.');
    }

    public function delete(int $id): bool
    {
        $statement = $this->database->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    /** @param array<string, mixed>|false $row */
    private function mapRow(array|false $row): ?User
    {
        if ($row === false) {
            return null;
        }

        return new User(
            id: (int) $row['id'],
            email: new Email((string) $row['email']),
            passwordHash: (string) $row['password_hash'],
            role: Role::from((string) $row['role']),
            createdAt: new \DateTimeImmutable((string) $row['created_at']),
            displayName: $row['display_name'],
        );
    }
}
