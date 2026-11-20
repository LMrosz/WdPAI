<?php

declare(strict_types=1);

namespace App\Model;

/** Skrót wina na liście degustacji: tylko to, co potrzebne do wyświetlenia (bez pełnej encji Wine). */
final readonly class TastedWine
{
    public function __construct(
        public int $id,
        public string $title,
    ) {
    }
}
