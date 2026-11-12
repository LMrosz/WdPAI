<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Wine;
use App\Service\SiteContent;

/** Wina z config/content.php (klucz wines.items). Identyfikator to numer pozycji na liście, od 1. */
final class ContentWineRepository implements WineRepositoryInterface
{
    public function __construct(private readonly SiteContent $content)
    {
    }

    public function findAll(?string $category = null): array
    {
        $wines = $this->all();

        if ($category === null) {
            return $wines;
        }

        return array_values(array_filter(
            $wines,
            static fn (Wine $wine): bool => $wine->category === $category,
        ));
    }

    public function findById(int $id): ?Wine
    {
        foreach ($this->all() as $wine) {
            if ($wine->id === $id) {
                return $wine;
            }
        }

        return null;
    }

    public function findRelated(Wine $wine, int $limit = 3): array
    {
        $others = array_filter(
            $this->all(),
            static fn (Wine $other): bool => $other->id !== $wine->id,
        );

        // sortowanie jest stabilne: wina z tej samej kategorii idą pierwsze, reszta zachowuje kolejność z listy
        usort(
            $others,
            static fn (Wine $a, Wine $b): int =>
                ($b->category === $wine->category) <=> ($a->category === $wine->category),
        );

        return array_slice($others, 0, $limit);
    }

    /** @return list<Wine> */
    private function all(): array
    {
        $rows = $this->content->get('wines.items');

        if (!is_array($rows)) {
            return [];
        }

        $wines = [];

        foreach (array_values($rows) as $index => $row) {
            $wines[] = Wine::fromArray($index + 1, $row);
        }

        return $wines;
    }
}
