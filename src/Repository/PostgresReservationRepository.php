<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\ReservationException;
use App\Model\Reservation;
use PDO;

/**
 * Rezerwacje z PostgreSQL. Cały SQL dotyczący tabeli reservations jest tutaj.
 *
 * Rezerwacja to transakcja. Limit miejsc nie da się zapisać w CHECK (zależy od innych wierszy),
 * więc pilnuje go blokada: SELECT ... FOR UPDATE na wierszu terminu ustawia równoległe rezerwacje
 * tego samego terminu w kolejce, a każda z nich liczy zajęte miejsca dopiero po zdobyciu blokady.
 * Bez blokady dwie osoby mogłyby jednocześnie zobaczyć „zostało 2 miejsca” i obie je zająć.
 */
final class PostgresReservationRepository implements ReservationRepositoryInterface
{
    private const COLUMNS = 'id, user_id, session_id, seats, status, created_at';

    public function __construct(private readonly PDO $database)
    {
    }

    public function book(int $userId, int $sessionId, int $seats): Reservation
    {
        $this->database->beginTransaction();

        try {
            $reservation = $this->bookInTransaction($userId, $sessionId, $seats);
            $this->database->commit();

            return $reservation;
        } catch (\Throwable $exception) {
            $this->database->rollBack();

            throw $exception;
        }
    }

    public function cancel(int $reservationId, int $userId): Reservation
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                "UPDATE reservations SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND user_id = :user AND status = 'confirmed'
                 RETURNING " . self::COLUMNS
            );
            $statement->execute(['id' => $reservationId, 'user' => $userId]);
            $row = $statement->fetch();

            if ($row === false) {
                throw new ReservationException('reservation_not_found');
            }

            $this->database->commit();

            return $this->mapRow($row);
        } catch (\Throwable $exception) {
            $this->database->rollBack();

            throw $exception;
        }
    }

    public function findConfirmedByUser(int $userId): array
    {
        $statement = $this->database->prepare(
            "SELECT r.id, r.user_id, r.session_id, r.seats, r.status, r.created_at
             FROM reservations r
             JOIN tasting_sessions s ON s.id = r.session_id
             WHERE r.user_id = :user AND r.status = 'confirmed' AND s.starts_at >= CURRENT_TIMESTAMP"
        );
        $statement->execute(['user' => $userId]);

        $reservations = [];

        foreach ($statement->fetchAll() as $row) {
            $reservation = $this->mapRow($row);
            $reservations[$reservation->sessionId] = $reservation;
        }

        return $reservations;
    }

    /** Kroki rezerwacji; wywoływane wewnątrz transakcji z book(). */
    private function bookInTransaction(int $userId, int $sessionId, int $seats): Reservation
    {
        // blokada wiersza terminu: kolejne rezerwacje tego terminu czekają tu na zakończenie tej
        $statement = $this->database->prepare(
            'SELECT s.seats_total, s.starts_at > CURRENT_TIMESTAMP AS is_upcoming, LEAST(t.max_group_size, 12) AS max_seats
             FROM tasting_sessions s
             JOIN tastings t ON t.id = s.tasting_id
             WHERE s.id = :session
             FOR UPDATE OF s'
        );
        $statement->execute(['session' => $sessionId]);
        $session = $statement->fetch();

        if ($session === false) {
            throw new ReservationException('session_not_found');
        }

        if (!filter_var($session['is_upcoming'], FILTER_VALIDATE_BOOLEAN)) {
            throw new ReservationException('session_past');
        }

        if ($seats > (int) $session['max_seats']) {
            throw new ReservationException('group_too_large');
        }

        $statement = $this->database->prepare(
            'SELECT id, status FROM reservations WHERE user_id = :user AND session_id = :session'
        );
        $statement->execute(['user' => $userId, 'session' => $sessionId]);
        $existing = $statement->fetch();

        if ($existing !== false && $existing['status'] === 'confirmed') {
            throw new ReservationException('already_booked');
        }

        $statement = $this->database->prepare(
            "SELECT COALESCE(SUM(seats), 0) FROM reservations WHERE session_id = :session AND status = 'confirmed'"
        );
        $statement->execute(['session' => $sessionId]);

        if ((int) $statement->fetchColumn() + $seats > (int) $session['seats_total']) {
            throw new ReservationException('not_enough_seats');
        }

        if ($existing !== false) {
            // użytkownik anulował wcześniej tę rezerwację, a UNIQUE (user_id, session_id) pozwala na jeden wiersz;
            // to nowa rezerwacja, więc przypomnienie o terminie ma znów być wysłane (reminder_sent_at = NULL)
            $statement = $this->database->prepare(
                "UPDATE reservations SET seats = :seats, status = 'confirmed', created_at = CURRENT_TIMESTAMP,
                        updated_at = CURRENT_TIMESTAMP, reminder_sent_at = NULL
                 WHERE id = :id
                 RETURNING " . self::COLUMNS
            );
            $statement->execute(['seats' => $seats, 'id' => $existing['id']]);
        } else {
            $statement = $this->database->prepare(
                'INSERT INTO reservations (user_id, session_id, seats) VALUES (:user, :session, :seats)
                 RETURNING ' . self::COLUMNS
            );
            $statement->execute(['user' => $userId, 'session' => $sessionId, 'seats' => $seats]);
        }

        return $this->mapRow($statement->fetch());
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Reservation
    {
        return new Reservation(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            sessionId: (int) $row['session_id'],
            seats: (int) $row['seats'],
            status: (string) $row['status'],
            createdAt: new \DateTimeImmutable((string) $row['created_at']),
        );
    }
}
