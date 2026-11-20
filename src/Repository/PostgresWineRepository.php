<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Wine;
use PDO;

/**
 * Wina z PostgreSQL (tabele wines i wine_pairings). Cały SQL dotyczący win jest tutaj,
 * a dane opuszczają repozytorium jako obiekty Wine.
 *
 * Wine przechowuje ceny i moc jako gotowe napisy do wyświetlenia („165 zł”, „13,5%”),
 * więc repozytorium składa je z liczb z bazy przy odczycie i rozkłada z powrotem przy zapisie.
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
     * Zapisuje nowe wino (INSERT ... RETURNING id) razem z potrawami i zwraca kopię z nadanym id.
     * Id przekazanego obiektu jest ignorowane.
     */
    public function save(Wine $wine): Wine
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
            $statement->execute($this->toParameters($wine));
            $saved = $wine->withId((int) $statement->fetchColumn());

            $this->replacePairings($saved);
            $this->database->commit();

            return $saved;
        } catch (\Throwable $exception) {
            $this->database->rollBack();

            throw $exception;
        }
    }

    /** Aktualizuje wino o id z obiektu; zwraca false, gdy takiego wina nie ma. */
    public function update(Wine $wine): bool
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
            $statement->execute($this->toParameters($wine) + ['id' => $wine->id]);

            $found = $statement->rowCount() > 0;

            if ($found) {
                $this->replacePairings($wine);
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
                'price' => $this->formatPrice((string) $row['price']),
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

    /** Zastępuje potrawy wina listą z obiektu (kasuje stare, wstawia nowe w podanej kolejności). */
    private function replacePairings(Wine $wine): void
    {
        $delete = $this->database->prepare('DELETE FROM wine_pairings WHERE wine_id = :wine_id');
        $delete->execute(['wine_id' => $wine->id]);

        $insert = $this->database->prepare(
            'INSERT INTO wine_pairings (wine_id, sort_order, dish) VALUES (:wine_id, :sort_order, :dish)'
        );

        foreach (array_values($wine->pairings) as $index => $dish) {
            $insert->execute(['wine_id' => $wine->id, 'sort_order' => $index + 1, 'dish' => $dish]);
        }
    }

    /** @return array<string, string|int|null> */
    private function toParameters(Wine $wine): array
    {
        return [
            'name' => $wine->name,
            'category' => $wine->category,
            'type' => $wine->type,
            'grape' => $wine->grape,
            'vintage' => $wine->vintage === null ? null : (int) $wine->vintage,
            'abv' => $wine->abv === null ? null : $this->parseNumber($wine->abv),
            'award' => $wine->award,
            'notes' => $wine->notes,
            'story' => $wine->story,
            'aroma' => $wine->aroma,
            'palate' => $wine->palate,
            'finish' => $wine->finish,
            'serving' => $wine->serving,
            'cellaring' => $wine->cellaring,
            'price' => $this->parseNumber($wine->price),
        ];
    }

    /** "165.00" z bazy na "165 zł", "72.50" na "72,50 zł". */
    private function formatPrice(string $amount): string
    {
        $value = (float) $amount;
        $decimals = floor($value) === $value ? 0 : 2;

        return number_format($value, $decimals, ',', ' ') . ' zł';
    }

    /** "13.5" z bazy na "13,5%". */
    private function formatAbv(string $value): string
    {
        return number_format((float) $value, 1, ',', '') . '%';
    }

    /** Wyciąga liczbę z napisu typu "165 zł" lub "13,5%" i zwraca ją w zapisie z kropką. */
    private function parseNumber(string $label): string
    {
        $number = str_replace(',', '.', preg_replace('/[^\d,.]/', '', $label) ?? '');

        if ($number === '' || !is_numeric($number)) {
            throw new \InvalidArgumentException('Brak liczby w wartości "' . $label . '"');
        }

        return $number;
    }
}
