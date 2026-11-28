<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\TastingReminder;
use App\Model\TastingSession;
use PDO;

/** Rezerwacje do przypomnienia z PostgreSQL (kolumna reservations.reminder_sent_at, patrz database/08). */
final class PostgresReminderRepository implements ReminderRepositoryInterface
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function findDue(\DateTimeImmutable $now, int $withinHours): array
    {
        $statement = $this->database->prepare(
            "SELECT r.id AS reservation_id, r.seats, u.email,
                    COALESCE(u.display_name, split_part(u.email, '@', 1)) AS user_name,
                    s.id AS session_id, s.tasting_id, s.starts_at, s.seats_total, t.name AS tasting_name
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN tasting_sessions s ON s.id = r.session_id
             JOIN tastings t ON t.id = s.tasting_id
             WHERE r.status = 'confirmed'
               AND r.reminder_sent_at IS NULL
               AND s.starts_at > :now
               AND s.starts_at <= :until
             ORDER BY s.starts_at, r.id"
        );
        $statement->bindValue('now', $now->format('Y-m-d H:i:sP'));
        $statement->bindValue('until', $now->modify('+' . $withinHours . ' hours')->format('Y-m-d H:i:sP'));
        $statement->execute();

        return array_map(
            static fn (array $row): TastingReminder => new TastingReminder(
                reservationId: (int) $row['reservation_id'],
                email: (string) $row['email'],
                userName: (string) $row['user_name'],
                seats: (int) $row['seats'],
                session: new TastingSession(
                    id: (int) $row['session_id'],
                    startsAt: new \DateTimeImmutable((string) $row['starts_at']),
                    seatsTotal: (int) $row['seats_total'],
                    seatsBooked: 0,
                    tastingId: (int) $row['tasting_id'],
                    tastingName: (string) $row['tasting_name'],
                ),
            ),
            $statement->fetchAll(),
        );
    }

    public function claim(int $reservationId, \DateTimeImmutable $now): bool
    {
        // jedno zapytanie sprawdza i zapisuje: równoległy proces czeka na blokadę wiersza, a po jej zwolnieniu
        // widzi już reminder_sent_at i nie przejmuje rezerwacji drugi raz
        $statement = $this->database->prepare(
            "UPDATE reservations SET reminder_sent_at = :now
             WHERE id = :id AND status = 'confirmed' AND reminder_sent_at IS NULL"
        );
        $statement->bindValue('now', $now->format('Y-m-d H:i:sP'));
        $statement->bindValue('id', $reservationId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() === 1;
    }

    public function release(int $reservationId): void
    {
        $statement = $this->database->prepare('UPDATE reservations SET reminder_sent_at = NULL WHERE id = :id');
        $statement->bindValue('id', $reservationId, PDO::PARAM_INT);
        $statement->execute();
    }
}
