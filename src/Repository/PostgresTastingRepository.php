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
 * oraz terminy z zajętymi miejscami (LEFT JOIN reservations + GROUP BY).
 */
final class PostgresTastingRepository implements TastingRepositoryInterface
{
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
     * Tylko przyszłe terminy; zajęte miejsca to suma miejsc z potwierdzonych rezerwacji.
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
            "SELECT s.id, s.tasting_id, s.starts_at, s.seats_total, COALESCE(SUM(r.seats), 0) AS seats_booked
             FROM tasting_sessions s
             LEFT JOIN reservations r ON r.session_id = s.id AND r.status = 'confirmed'
             WHERE s.tasting_id IN (" . $this->placeholders($tastingIds) . ')
               AND s.starts_at >= CURRENT_TIMESTAMP
             GROUP BY s.id
             ORDER BY s.starts_at'
        );
        $statement->execute($tastingIds);

        $sessions = [];

        foreach ($statement->fetchAll() as $row) {
            $sessions[(int) $row['tasting_id']][] = new TastingSession(
                id: (int) $row['id'],
                startsAt: new \DateTimeImmutable((string) $row['starts_at']),
                seatsTotal: (int) $row['seats_total'],
                seatsBooked: (int) $row['seats_booked'],
            );
        }

        return $sessions;
    }

    /** @param list<int> $values */
    private function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }
}
