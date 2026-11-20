<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\WineData;
use App\Model\Wine;
use App\Service\PriceFormatter;
use PDO;

/**
 * Wina z PostgreSQL (tabele wines i wine_pairings). Cały SQL dotyczący win jest tutaj,
 * a dane opuszczają repozytorium jako obiekty Wine.
 *
 * Wine przechowuje ceny i moc jako gotowe napisy do wyświetlenia („165 zł”, „13,5%”),
 * więc repozytorium składa je z liczb z bazy przy odczycie. Zapis przyjmuje DTO WineData
 * z wartościami liczbowymi, bez id; zwracana jest encja Wine odczytana z bazy.
 */
final class PostgresWineRepository implements WineRepositoryInterface
{
    private const COLUMNS = 'id, name, category, type, grape, vintage, abv, award, notes, story,
        aroma, palate, finish, serving, cellaring, price';

    public function __construct(private readonly PDO $database)
    {
    }

    public function findAll(?string $category = null): array
    {
        if ($category === null) {
            $statement = $this->database->query('SELECT ' . self::COLUMNS . ' FROM wines ORDER BY id');
        } else {
            $statement = $this->database->prepare(
                'SELECT ' . self::COLUMNS . ' FROM wines WHERE category = :category ORDER BY id'
            );
            $statement->execute(['category' => $category]);
        }

        return $this->mapRows($statement->fetchAll());
    }

    public function findById(int $id): ?Wine
    {
        $statement = $this->database->prepare('SELECT ' . self::COLUMNS . ' FROM wines WHERE id = :id');
        $statement->execute(['id' => $id]);

        $wines = $this->mapRows($statement->fetchAll());

        return $wines[0] ?? null;
    }

    public function findRelated(Wine $wine, int $limit = 3): array
    {
        // najpierw wina z tej samej kategorii, potem pozostałe, w kolejności z katalogu
        $statement = $this->database->prepare(
            'SELECT ' . self::COLUMNS . ' FROM wines
             WHERE id <> :id
             ORDER BY (category = :category) DESC, id
             LIMIT :limit'
        );
        $statement->bindValue('id', $wine->id, PDO::PARAM_INT);
        $statement->bindValue('category', $wine->category);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $this->mapRows($statement->fetchAll());
    }

    /**
     * Dodaje wino razem z potrawami (INSERT ... RETURNING id, w jednej transakcji)
     * i zwraca encję z nadanym id.
     */
    public function create(WineData $data): Wine
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'INSERT INTO wines (name, category, type, grape, vintage, abv, award, notes, story,
                                    aroma, palate, finish, serving, cellaring, price)
                 VALUES (:name, :category, :type, :grape, :vintage, :abv, :award, :notes, :story,
                         :aroma, :palate, :finish, :serving, :cellaring, :price)
                 RETURNING id'
            );
            $statement->execute($this->toParameters($data));
            $id = (int) $statement->fetchColumn();

            $this->replacePairings($id, $data->pairings);
            $this->database->commit();
        } catch (\Throwable $exception) {
            $this->database->rollBack();

            throw $exception;
        }

        return $this->findById($id) ?? throw new \LogicException('Wino zniknęło tuż po zapisie.');
    }

    /** Zmienia wszystkie pola wina o podanym id; zwraca false, gdy takiego wina nie ma. */
    public function update(int $id, WineData $data): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'UPDATE wines
                 SET name = :name, category = :category, type = :type, grape = :grape, vintage = :vintage,
                     abv = :abv, award = :award, notes = :notes, story = :story, aroma = :aroma,
                     palate = :palate, finish = :finish, serving = :serving, cellaring = :cellaring,
                     price = :price, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $statement->execute($this->toParameters($data) + ['id' => $id]);

            $found = $statement->rowCount() > 0;

            if ($found) {
                $this->replacePairings($id, $data->pairings);
            }

            $this->database->commit();

            return $found;
        } catch (\Throwable $exception) {
            $this->database->rollBack();

            throw $exception;
        }
    }

    /** Usuwa wino (potrawy znikają razem z nim dzięki ON DELETE CASCADE); false, gdy nie istniało. */
    public function delete(int $id): bool
    {
        $statement = $this->database->prepare('DELETE FROM wines WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<Wine>
     */
    private function mapRows(array $rows): array
    {
        $pairings = $this->loadPairings(array_map(static fn (array $row): int => (int) $row['id'], $rows));
        $wines = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $wines[] = Wine::fromArray($id, [
                'name' => $row['name'],
                'category' => $row['category'],
                'type' => $row['type'],
                'notes' => $row['notes'],
                'price' => PriceFormatter::format((string) $row['price']),
                'grape' => $row['grape'],
                'vintage' => $row['vintage'],
                'abv' => $row['abv'] === null ? null : $this->formatAbv((string) $row['abv']),
                'award' => $row['award'],
                'story' => $row['story'],
                'aroma' => $row['aroma'],
                'palate' => $row['palate'],
                'finish' => $row['finish'],
                'serving' => $row['serving'],
                'cellaring' => $row['cellaring'],
                'pairings' => $pairings[$id] ?? [],
            ]);
        }

        return $wines;
    }

    /**
     * Potrawy dla wielu win jednym zapytaniem (bez zapytania na każde wino).
     *
     * @param list<int> $wineIds
     * @return array<int, list<string>>
     */
    private function loadPairings(array $wineIds): array
    {
        if ($wineIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($wineIds), '?'));
        $statement = $this->database->prepare(
            'SELECT wine_id, dish FROM wine_pairings WHERE wine_id IN (' . $placeholders . ')
             ORDER BY wine_id, sort_order'
        );
        $statement->execute($wineIds);

        $pairings = [];

        foreach ($statement->fetchAll() as $row) {
            $pairings[(int) $row['wine_id']][] = (string) $row['dish'];
        }

        return $pairings;
    }

    /**
     * Zastępuje potrawy wina podaną listą (kasuje stare, wstawia nowe w podanej kolejności).
     *
     * @param list<string> $pairings
     */
    private function replacePairings(int $wineId, array $pairings): void
    {
        $delete = $this->database->prepare('DELETE FROM wine_pairings WHERE wine_id = :wine_id');
        $delete->execute(['wine_id' => $wineId]);

        $insert = $this->database->prepare(
            'INSERT INTO wine_pairings (wine_id, sort_order, dish) VALUES (:wine_id, :sort_order, :dish)'
        );

        foreach (array_values($pairings) as $index => $dish) {
            $insert->execute(['wine_id' => $wineId, 'sort_order' => $index + 1, 'dish' => $dish]);
        }
    }

    /** @return array<string, string|int|float|null> */
    private function toParameters(WineData $data): array
    {
        return [
            'name' => $data->name,
            'category' => $data->category,
            'type' => $data->type,
            'grape' => $data->grape,
            'vintage' => $data->vintage,
            'abv' => $data->abv,
            'award' => $data->award,
            'notes' => $data->notes,
            'story' => $data->story,
            'aroma' => $data->aroma,
            'palate' => $data->palate,
            'finish' => $data->finish,
            'serving' => $data->serving,
            'cellaring' => $data->cellaring,
            'price' => $data->price,
        ];
    }

    /** "13.5" z bazy na "13,5%". */
    private function formatAbv(string $value): string
    {
        return number_format((float) $value, 1, ',', '') . '%';
    }
}
