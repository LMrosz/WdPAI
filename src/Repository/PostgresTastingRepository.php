<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\TastedWine;
use App\Model\Tasting;
use App\Model\TastingSession;
use App\Service\PriceFormatter;
use PDO;

/**
 * Degustacje z PostgreSQL. Trzy zapytania zamiast jednego z powielonymi wierszami
 * (wina i terminy mnożyłyby się nawzajem): degustacje, ich wina (JOIN tasting_wines → wines)
 * oraz terminy z widoku tasting_session_availability, który liczy zajęte miejsca
 * (LEFT JOIN reservations + GROUP BY) w jednym miejscu, także dla rezerwacji.
 */
final class PostgresTastingRepository implements TastingRepositoryInterface
{
    private const SESSION_COLUMNS = 'SELECT session_id, tasting_id, tasting_name, starts_at, seats_total, seats_booked, max_seats';

    public function __construct(private readonly PDO $database)
    {
    }

    public function findAll(): array
    {
        $rows = $this->database->query(
            'SELECT id, name, badge, description, duration_minutes, max_group_size, price_per_person
             FROM tastings ORDER BY id'
        )->fetchAll();

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $wines = $this->loadWines($ids);
        $sessions = $this->loadSessions($ids);
        $tastings = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $tastings[] = new Tasting(
                id: $id,
                name: $row['name'],
                description: $row['description'],
                durationMinutes: (int) $row['duration_minutes'],
                maxGroupSize: (int) $row['max_group_size'],
                pricePerPerson: PriceFormatter::format((string) $row['price_per_person']),
                badge: $row['badge'],
                wines: $wines[$id] ?? [],
                sessions: $sessions[$id] ?? [],
            );
        }

        return $tastings;
    }

    /**
     * @param list<int> $tastingIds
     * @return array<int, list<TastedWine>>
     */
    private function loadWines(array $tastingIds): array
    {
        if ($tastingIds === []) {
            return [];
        }

        $statement = $this->database->prepare(
            'SELECT tw.tasting_id, w.id, w.name, w.vintage
             FROM tasting_wines tw
             JOIN wines w ON w.id = tw.wine_id
             WHERE tw.tasting_id IN (' . $this->placeholders($tastingIds) . ')
             ORDER BY tw.tasting_id, w.id'
        );
        $statement->execute($tastingIds);

        $wines = [];

        foreach ($statement->fetchAll() as $row) {
            $title = trim($row['name'] . ' ' . ($row['vintage'] ?? ''));
            $wines[(int) $row['tasting_id']][] = new TastedWine((int) $row['id'], $title);
        }

        return $wines;
    }

    /**
     * Tylko przyszłe terminy, z widoku tasting_session_availability (zajęte miejsca to suma
     * miejsc z potwierdzonych rezerwacji).
     *
     * @param list<int> $tastingIds
     * @return array<int, list<TastingSession>>
     */
    private function loadSessions(array $tastingIds): array
    {
        if ($tastingIds === []) {
            return [];
        }

        $statement = $this->database->prepare(
            self::SESSION_COLUMNS . '
             FROM tasting_session_availability
             WHERE tasting_id IN (' . $this->placeholders($tastingIds) . ')
               AND starts_at >= CURRENT_TIMESTAMP
             ORDER BY starts_at'
        );
        $statement->execute($tastingIds);

        $sessions = [];

        foreach ($statement->fetchAll() as $row) {
            $sessions[(int) $row['tasting_id']][] = $this->mapSession($row);
        }

        return $sessions;
    }

    public function findUpcomingSessions(): array
    {
        $rows = $this->database->query(
            self::SESSION_COLUMNS . '
             FROM tasting_session_availability
             WHERE starts_at >= CURRENT_TIMESTAMP
             ORDER BY starts_at, session_id'
        )->fetchAll();

        return array_map($this->mapSession(...), $rows);
    }

    public function findSession(int $sessionId): ?TastingSession
    {
        $statement = $this->database->prepare(
            self::SESSION_COLUMNS . ' FROM tasting_session_availability WHERE session_id = :id'
        );
        $statement->execute(['id' => $sessionId]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapSession($row);
    }

    /** @param array<string, mixed> $row wiersz widoku tasting_session_availability */
    private function mapSession(array $row): TastingSession
    {
        return new TastingSession(
            id: (int) $row['session_id'],
            startsAt: new \DateTimeImmutable((string) $row['starts_at']),
            seatsTotal: (int) $row['seats_total'],
            seatsBooked: (int) $row['seats_booked'],
            tastingId: (int) $row['tasting_id'],
            tastingName: (string) $row['tasting_name'],
            maxSeats: (int) $row['max_seats'],
        );
    }

    /** @param list<int> $values */
    private function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }
}
