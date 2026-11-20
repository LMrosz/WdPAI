<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * DTO: dane wina przekazywane do zapisu (dodawanie i edycja). To zwykły pojemnik bez logiki
 * i bez id, bo nowe wino nie ma jeszcze tożsamości; id nadaje baza, a repozytorium zwraca
 * gotową encję Wine. Wartości są już w postaci liczbowej, taką jak w kolumnach tabeli wines.
 */
final readonly class WineData
{
    /** @param list<string> $pairings potrawy w kolejności wyświetlania */
    public function __construct(
        public string $name,
        public string $category,
        public string $type,
        public string $notes,
        public float $price,
        public ?string $grape = null,
        public ?int $vintage = null,
        public ?float $abv = null,
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
}
