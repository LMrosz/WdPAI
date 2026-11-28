<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\WineData;
use App\Exception\DuplicateWineException;
use App\Model\Wine;

/** Zapis win (dodawanie, edycja, usuwanie); odczyt jest w WineRepositoryInterface. */
interface WineWriterInterface
{
    /**
     * Wino w postaci wartości liczbowych, takiej jak w formularzu edycji (Wine ma gotowe napisy do wyświetlenia).
     */
    public function findDataById(int $id): ?WineData;

    /** @throws DuplicateWineException gdy wino o tej nazwie i roczniku już istnieje */
    public function create(WineData $data): Wine;

    /**
     * @return bool false, gdy wina o takim id nie ma
     * @throws DuplicateWineException gdy nowa nazwa i rocznik zajmują już inne wino
     */
    public function update(int $id, WineData $data): bool;

    /** Ścieżka zdjęcia wina (względem public/) albo null, gdy wino nie ma zdjęcia lub nie istnieje. */
    public function findImagePath(int $id): ?string;

    /**
     * Ustawia albo usuwa (null) zdjęcie wina. W bazie jest tylko ścieżka względna, plik leży na dysku.
     *
     * @return bool false, gdy wina o takim id nie ma
     */
    public function changeImage(int $id, ?string $path): bool;

    /** @return bool false, gdy wina o takim id nie było */
    public function delete(int $id): bool;
}
