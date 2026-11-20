<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Wino z katalogu winnicy. Obiekt tylko do odczytu; kategoria służy do filtrowania (?type=).
 * Pola od $story do $pairings opisują stronę szczegółów i są opcjonalne.
 */
final readonly class Wine
{
    public const CATEGORY_RED = 'red';
    public const CATEGORY_WHITE = 'white';
    public const CATEGORY_ROSE = 'rose';
    public const CATEGORY_SPARKLING = 'sparkling';

    public const CATEGORIES = [
        self::CATEGORY_RED,
        self::CATEGORY_WHITE,
        self::CATEGORY_ROSE,
        self::CATEGORY_SPARKLING,
    ];

    public function __construct(
        public int $id,
        public string $name,
        public string $category,
        public string $type,
        public string $notes,
        public string $price,
        public ?string $grape = null,
        public ?string $vintage = null,
        public ?string $abv = null,
        public ?string $award = null,
        public ?string $story = null,
        public ?string $aroma = null,
        public ?string $palate = null,
        public ?string $finish = null,
        public ?string $serving = null,
        public ?string $cellaring = null,
        public array $pairings = [],
    ) {
    }

    /** @param array<string, mixed> $row jedna pozycja z listy wines.items */
    public static function fromArray(int $id, array $row): self
    {
        return new self(
            id: $id,
            name: (string) $row['name'],
            category: (string) $row['category'],
            type: (string) $row['type'],
            notes: (string) $row['notes'],
            price: (string) $row['price'],
            grape: isset($row['grape']) ? (string) $row['grape'] : null,
            vintage: isset($row['vintage']) ? (string) $row['vintage'] : null,
            abv: isset($row['abv']) ? (string) $row['abv'] : null,
            award: isset($row['award']) ? (string) $row['award'] : null,
            story: isset($row['story']) ? (string) $row['story'] : null,
            aroma: isset($row['aroma']) ? (string) $row['aroma'] : null,
            palate: isset($row['palate']) ? (string) $row['palate'] : null,
            finish: isset($row['finish']) ? (string) $row['finish'] : null,
            serving: isset($row['serving']) ? (string) $row['serving'] : null,
            cellaring: isset($row['cellaring']) ? (string) $row['cellaring'] : null,
            pairings: array_values(array_map('strval', (array) ($row['pairings'] ?? []))),
        );
    }

    /** Czy wino ma notatki degustacyjne (aromat, smak lub finisz). */
    public function hasTastingNotes(): bool
    {
        return $this->aroma !== null || $this->palate !== null || $this->finish !== null;
    }

    /** Nazwa z rocznikiem, np. "Reserve Chardonnay 2021". */
    public function title(): string
    {
        return trim($this->name . ' ' . ($this->vintage ?? ''));
    }
}
