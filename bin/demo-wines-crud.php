<?php

declare(strict_types=1);

/**
 * Pokaz operacji CRUD na repozytorium win (Lab 7).
 *
 *   docker compose exec php php bin/demo-wines-crud.php            dodaje trzy wina w różnych kategoriach
 *   docker compose exec php php bin/demo-wines-crud.php update     zmienia cenę pierwszego wina demo
 *   docker compose exec php php bin/demo-wines-crud.php cleanup    usuwa wszystkie wina demo
 *
 * Wina demo mają nazwę zaczynającą się od "Demo " i od razu widać je na /wines.
 */

use App\Database;
use App\Model\Wine;
use App\Repository\PostgresWineRepository;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$repository = new PostgresWineRepository((new Database())->connect());
$command = $argv[1] ?? 'add';

/** @return list<Wine> */
$demoWines = static fn (): array => array_values(array_filter(
    $repository->findAll(),
    static fn (Wine $wine): bool => str_starts_with($wine->name, 'Demo '),
));

switch ($command) {
    case 'add':
        $new = [
            new Wine(0, 'Demo Rosé Ogrodowe', Wine::CATEGORY_ROSE, 'Różowe wytrawne', 'Lekkie wino z demo.', '69 zł',
                vintage: '2024', abv: '12,0%', pairings: ['Sałatki', 'Grillowane warzywa']),
            new Wine(0, 'Demo Bąbelki Brut', Wine::CATEGORY_SPARKLING, 'Musujące brut', 'Musujące wino z demo.', '119,50 zł',
                vintage: '2022', abv: '12,5%', pairings: ['Ostrygi']),
            new Wine(0, 'Demo Biały Półsłodki', Wine::CATEGORY_WHITE, 'Białe półsłodkie', 'Białe wino z demo.', '84 zł'),
        ];

        foreach ($new as $wine) {
            $saved = $repository->save($wine);
            echo 'Dodano: #' . $saved->id . ' ' . $saved->name . ' (' . $saved->category . ', ' . $saved->price . ")\n";
        }
        break;

    case 'update':
        $wine = $demoWines()[0] ?? null;

        if ($wine === null) {
            echo "Brak win demo. Najpierw uruchom skrypt bez argumentu.\n";
            exit(1);
        }

        $changed = new Wine($wine->id, $wine->name, $wine->category, $wine->type, $wine->notes, '99 zł',
            $wine->grape, $wine->vintage, $wine->abv, $wine->award, pairings: $wine->pairings);
        $repository->update($changed);
        echo 'Zaktualizowano: #' . $wine->id . ' ' . $wine->name . ': ' . $wine->price . ' -> ' . $changed->price . "\n";
        break;

    case 'cleanup':
        foreach ($demoWines() as $wine) {
            $repository->delete($wine->id);
            echo 'Usunięto: #' . $wine->id . ' ' . $wine->name . "\n";
        }
        break;

    default:
        fwrite(STDERR, "Nieznane polecenie. Użyj: add, update albo cleanup.\n");
        exit(1);
}

echo 'Win w bazie: ' . count($repository->findAll()) . "\n";
