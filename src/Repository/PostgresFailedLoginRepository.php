<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/** Dziennik nieudanych logowań w PostgreSQL. Cały SQL tej tabeli jest tutaj, zawsze z parametrami. */
final class PostgresFailedLoginRepository implements FailedLoginRepositoryInterface
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function record(string $email, string $ipAddress, string $reason, ?string $userAgent): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO failed_logins (email, ip_address, reason, user_agent)
             VALUES (:email, :ip, :reason, :agent)'
        );
        $statement->execute([
            'email' => $email,
            'ip' => $ipAddress,
            'reason' => $reason,
            'agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 255),
        ]);
    }

    public function recentForAccount(string $email, string $ipAddress, int $windowSeconds): array
    {
        $statement = $this->database->prepare(
            "SELECT EXTRACT(EPOCH FROM attempted_at)::bigint
             FROM failed_logins
             WHERE email = :email AND ip_address = :ip
               AND attempted_at > CURRENT_TIMESTAMP - make_interval(secs => :window)
             ORDER BY attempted_at"
        );
        $statement->bindValue('email', $email);
        $statement->bindValue('ip', $ipAddress);
        $statement->bindValue('window', $windowSeconds, PDO::PARAM_INT);
        $statement->execute();

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function recentForIp(string $ipAddress, int $windowSeconds): array
    {
        $statement = $this->database->prepare(
            "SELECT EXTRACT(EPOCH FROM attempted_at)::bigint
             FROM failed_logins
             WHERE ip_address = :ip
               AND attempted_at > CURRENT_TIMESTAMP - make_interval(secs => :window)
             ORDER BY attempted_at"
        );
        $statement->bindValue('ip', $ipAddress);
        $statement->bindValue('window', $windowSeconds, PDO::PARAM_INT);
        $statement->execute();

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }
}
