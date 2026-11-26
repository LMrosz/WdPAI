<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database;
use App\Dto\WineData;
use App\Exception\DuplicateWineException;
use App\Exception\NotFoundException;
use App\Model\Permission;
use App\Model\Wine;
use App\Repository\PostgresWineRepository;
use App\Repository\WineRepositoryInterface;
use App\Repository\WineWriterInterface;
use App\Service\Validator;

/**
 * Dodawanie, edycja i usuwanie win. Każda akcja zaczyna się od sprawdzenia uprawnienia
 * (401 bez logowania, 403 przy niewłaściwej roli), a akcje POST także od tokenu CSRF.
 */
final class WineManagementController extends AppController
{
    private const NAME_MAX = 150;
    private const TYPE_MAX = 60;
    private const GRAPE_MAX = 80;
    private const AWARD_MAX = 100;
    private const SERVING_MAX = 150;
    private const CELLARING_MAX = 200;
    private const TEXT_MAX = 2000;
    private const DISH_MAX = 150;
    private const PAIRINGS_MAX = 10;

    /** Pola formularza tekstowe i opcjonalne: nazwa pola => limit znaków. */
    private const OPTIONAL_TEXT = [
        'grape' => self::GRAPE_MAX,
        'award' => self::AWARD_MAX,
        'story' => self::TEXT_MAX,
        'aroma' => self::TEXT_MAX,
        'palate' => self::TEXT_MAX,
        'finish' => self::TEXT_MAX,
        'serving' => self::SERVING_MAX,
        'cellaring' => self::CELLARING_MAX,
    ];

    private readonly WineRepositoryInterface&WineWriterInterface $wines;

    public function __construct((WineRepositoryInterface&WineWriterInterface)|null $wines = null)
    {
        $this->wines = $wines ?? new PostgresWineRepository((new Database())->connect());
    }

    /** GET /wines/new – pusty formularz nowego wina (piwniczy, właściciel). */
    public function create(): void
    {
        $this->requirePermission(Permission::ManageWines);

        $this->renderForm(null, [], ['category' => Wine::CATEGORY_RED]);
    }

    /** POST /wines – zapis nowego wina; błędy wracają do formularza (422), sukces kończy się przekierowaniem. */
    public function store(): void
    {
        $this->requirePermission(Permission::ManageWines);
        $this->requireValidCsrf();

        [$data, $errors, $old] = $this->readForm();

        if ($errors === []) {
            try {
                $wine = $this->wines->create($data);
                $this->flash('flash.wine_created');
                $this->redirect('/wines/' . $wine->id);
            } catch (DuplicateWineException) {
                $errors['name'] = $this->text('wines.form.duplicate');
            }
        }

        http_response_code(422);
        $this->renderForm(null, $errors, $old);
    }

    /** GET /wines/{id}/edit – formularz wypełniony danymi wina. */
    public function edit(string $id): void
    {
        $this->requirePermission(Permission::ManageWines);

        $wineId = $this->idFromPath($id);
        $data = $this->wines->findDataById($wineId) ?? throw new NotFoundException('Wine ' . $id . ' not found');

        $this->renderForm($wineId, [], $this->formValues($data));
    }

    /** POST /wines/{id}/edit – zapis zmian. */
    public function update(string $id): void
    {
        $this->requirePermission(Permission::ManageWines);
        $this->requireValidCsrf();

        $wineId = $this->idFromPath($id);
        [$data, $errors, $old] = $this->readForm();

        if ($errors === []) {
            try {
                if (!$this->wines->update($wineId, $data)) {
                    throw new NotFoundException('Wine ' . $id . ' not found');
                }

                $this->flash('flash.wine_updated');
                $this->redirect('/wines/' . $wineId);
            } catch (DuplicateWineException) {
                $errors['name'] = $this->text('wines.form.duplicate');
            }
        }

        http_response_code(422);
        $this->renderForm($wineId, $errors, $old);
    }

    /** POST /wines/{id}/delete – usunięcie wina; tylko właściciel winnicy (WINERY_OWNER). */
    public function destroy(string $id): void
    {
        $this->requirePermission(Permission::DeleteWines);
        $this->requireValidCsrf();

        if (!$this->wines->delete($this->idFromPath($id))) {
            throw new NotFoundException('Wine ' . $id . ' not found');
        }

        $this->flash('flash.wine_deleted');
        $this->redirect('/wines');
    }

    /**
     * Czyta i waliduje formularz.
     *
     * @return array{0: WineData, 1: array<string, string>, 2: array<string, string>}
     *         dane do zapisu, błędy (pole => komunikat) i wpisane wartości do ponownego pokazania
     */
    private function readForm(): array
    {
        $old = [
            'name' => trim($this->postString('name')),
            'category' => $this->postString('category'),
            'type' => trim($this->postString('type')),
            'notes' => trim($this->postString('notes')),
            'price' => trim($this->postString('price')),
            'vintage' => trim($this->postString('vintage')),
            'abv' => trim($this->postString('abv')),
            'pairings' => trim($this->postString('pairings')),
        ];

        foreach (array_keys(self::OPTIONAL_TEXT) as $field) {
            $old[$field] = trim($this->postString($field));
        }

        $validator = Validator::withRules($old, self::fieldRules());

        $errors = $this->validationMessages($validator);
        $pairings = $this->parsePairings($old['pairings']);

        if (count($pairings) > self::PAIRINGS_MAX || $this->hasTooLongDish($pairings)) {
            $errors['pairings'] = $this->text('wines.form.pairings_error', [
                'max' => self::PAIRINGS_MAX,
                'length' => self::DISH_MAX,
            ]);
        }

        $data = new WineData(
            name: $old['name'],
            category: $old['category'],
            type: $old['type'],
            notes: $old['notes'],
            price: $this->toFloat($old['price']) ?? 0.0,
            grape: $this->nullIfEmpty($old['grape']),
            vintage: $old['vintage'] === '' ? null : (int) $old['vintage'],
            abv: $this->toFloat($old['abv']),
            award: $this->nullIfEmpty($old['award']),
            story: $this->nullIfEmpty($old['story']),
            aroma: $this->nullIfEmpty($old['aroma']),
            palate: $this->nullIfEmpty($old['palate']),
            finish: $this->nullIfEmpty($old['finish']),
            serving: $this->nullIfEmpty($old['serving']),
            cellaring: $this->nullIfEmpty($old['cellaring']),
            pairings: $pairings,
        );

        return [$data, $errors, $old];
    }

    /** Wartości z bazy jako teksty do pól formularza (liczby z przecinkiem, potrawy po jednej w wierszu). */
    private function formValues(WineData $data): array
    {
        return [
            'name' => $data->name,
            'category' => $data->category,
            'type' => $data->type,
            'notes' => $data->notes,
            'price' => $this->numberToInput($data->price),
            'vintage' => $data->vintage === null ? '' : (string) $data->vintage,
            'abv' => $data->abv === null ? '' : $this->numberToInput($data->abv),
            'grape' => $data->grape ?? '',
            'award' => $data->award ?? '',
            'story' => $data->story ?? '',
            'aroma' => $data->aroma ?? '',
            'palate' => $data->palate ?? '',
            'finish' => $data->finish ?? '',
            'serving' => $data->serving ?? '',
            'cellaring' => $data->cellaring ?? '',
            'pairings' => implode("\n", $data->pairings),
        ];
    }

    /**
     * Reguły pól formularza wina. Ten sam zapis sprawdza serwer (Validator::rules) i przeglądarka
     * (widok wypisuje go w atrybucie data-rules). Pole "pairings" ma osobną kontrolę po stronie serwera.
     *
     * @return array<string, list<string>>
     */
    private static function fieldRules(): array
    {
        $optionalText = array_map(
            static fn (int $limit): array => ['max_length:' . $limit],
            self::OPTIONAL_TEXT,
        );

        return [
            'name' => ['required', 'max_length:' . self::NAME_MAX],
            'category' => ['required', 'in_list:' . implode(',', Wine::CATEGORIES)],
            'type' => ['required', 'max_length:' . self::TYPE_MAX],
            'notes' => ['required', 'max_length:' . self::TEXT_MAX],
            'price' => ['required', 'decimal_between:0,999999,2'],
            'vintage' => ['integer_between:1900,2100'],
            'abv' => ['decimal_between:0,25,1'],
        ] + $optionalText;
    }

    /**
     * @param int|null $wineId id edytowanego wina albo null przy dodawaniu
     * @param array<string, string> $errors pole => komunikat
     * @param array<string, string> $old wartości pól
     */
    private function renderForm(?int $wineId, array $errors, array $old): void
    {
        $editing = $wineId !== null;
        $categories = $this->content()->get('wines.filters') ?? [];
        unset($categories['all']);

        $this->render('wine-form', [
            'pageTitle' => $this->text($editing ? 'wines.form.edit_page_title' : 'wines.form.create_page_title'),
            'activePage' => 'wines',
            'editing' => $editing,
            'formAction' => $editing ? '/wines/' . $wineId . '/edit' : '/wines',
            'cancelUrl' => $editing ? '/wines/' . $wineId : '/wines',
            'categories' => $categories,
            'rules' => self::fieldRules(),
            'errors' => $errors,
            'old' => $old,
        ], true);
    }

    /**
     * Potrawy z pola tekstowego: jedna w wierszu, bez pustych wierszy.
     *
     * @return list<string>
     */
    private function parsePairings(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $dishes = array_map('trim', $lines);

        return array_values(array_filter($dishes, static fn (string $dish): bool => $dish !== ''));
    }

    /** @param list<string> $pairings */
    private function hasTooLongDish(array $pairings): bool
    {
        foreach ($pairings as $dish) {
            if (mb_strlen($dish) > self::DISH_MAX) {
                return true;
            }
        }

        return false;
    }

    private function toFloat(string $input): ?float
    {
        return $input === '' ? null : (float) str_replace(',', '.', $input);
    }

    private function nullIfEmpty(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    /** 165.0 daje "165", 72.5 daje "72,5". */
    private function numberToInput(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
    }
}
