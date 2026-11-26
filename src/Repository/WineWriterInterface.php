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

    /** @return bool false, gdy wina o takim id nie było */
    public function delete(int $id): bool;
}
