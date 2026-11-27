<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Walidacja danych z formularza po stronie serwera.
 *
 * Reguły dopisuje się łańcuchowo, np. (new Validator($_POST))->required('email')->email('email').
 * Dla każdego pola zapamiętywany jest pierwszy błąd: nazwa reguły i jej parametry. Tekst
 * komunikatu dobiera widok z config/content.php (klucz "validation.<reguła>"), dzięki czemu
 * walidator nie zawiera napisów i nadaje się do tłumaczenia.
 * Reguły inne niż required pomijają puste pole (o tym, czy pole jest wymagane, decyduje required).
 */
final class Validator
{
    /** @var array<string, array{rule: string, params: array<string, int>}> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * Tworzy walidator i od razu stosuje reguły zapisane tekstem (pole => lista reguł).
     *
     * @param array<string, mixed> $data
     * @param array<string, list<string>> $fieldRules
     */
    public static function withRules(array $data, array $fieldRules): self
    {
        $validator = new self($data);

        foreach ($fieldRules as $field => $rules) {
            $validator->rules($field, $rules);
        }

        return $validator;
    }

    /**
     * Stosuje reguły zapisane tekstem, np. ['required', 'max_length:64', 'integer_between:1900,2100'].
     * Nazwa reguły jest taka sama jak klucz komunikatu "validation.<reguła>", a ten sam zapis widok
     * wypisuje w atrybucie data-rules pola, więc przeglądarka (public/js) sprawdza dokładnie te
     * same reguły co serwer i nie trzeba ich utrzymywać w dwóch miejscach.
     *
     * @param list<string> $rules
     */
    public function rules(string $field, array $rules): self
    {
        foreach ($rules as $rule) {
            [$name, $arguments] = array_pad(explode(':', $rule, 2), 2, '');
            $args = $arguments === '' ? [] : explode(',', $arguments);

            match ($name) {
                'required' => $this->required($field),
                'email' => $this->email($field),
                'min_length' => $this->minLength($field, (int) $args[0]),
                'max_length' => $this->maxLength($field, (int) $args[0]),
                'max_bytes' => $this->maxBytes($field, (int) $args[0]),
                'matches' => $this->matches($field, $args[0]),
                'letter_and_digit' => $this->letterAndDigit($field),
                'special_character' => $this->specialCharacter($field),
                'in_list' => $this->inList($field, $args),
                'integer_between' => $this->integerBetween($field, (int) $args[0], (int) $args[1]),
                'decimal_between' => $this->decimalBetween($field, (int) $args[0], (int) $args[1], (int) $args[2]),
                default => throw new \InvalidArgumentException('Unknown validation rule: ' . $name),
            };
        }

        return $this;
    }

    public function required(string $field): self
    {
        return $this->check($field, 'required', static fn (string $value): bool => $value !== '');
    }

    public function email(string $field): self
    {
        return $this->check(
            $field,
            'email',
            static fn (string $value): bool => $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
        );
    }

    public function minLength(string $field, int $min): self
    {
        return $this->check(
            $field,
            'min_length',
            static fn (string $value): bool => $value === '' || mb_strlen($value) >= $min,
            ['min' => $min],
        );
    }

    public function maxLength(string $field, int $max): self
    {
        return $this->check(
            $field,
            'max_length',
            static fn (string $value): bool => mb_strlen($value) <= $max,
            ['max' => $max],
        );
    }

    /** Maksymalna długość w bajtach (np. bcrypt uwzględnia tylko pierwsze 72 bajty hasła). */
    public function maxBytes(string $field, int $max): self
    {
        return $this->check(
            $field,
            'max_bytes',
            static fn (string $value): bool => strlen($value) <= $max,
            ['max' => $max],
        );
    }

    /** Wartość pola musi być taka sama jak wartość pola $otherField (np. powtórzone hasło). */
    public function matches(string $field, string $otherField): self
    {
        return $this->check(
            $field,
            'matches',
            fn (string $value): bool => $value === '' || $value === $this->value($otherField),
        );
    }

    /** Wartość musi zawierać co najmniej jedną literę i jedną cyfrę (np. hasło). */
    public function letterAndDigit(string $field): self
    {
        return $this->check(
            $field,
            'letter_and_digit',
            static fn (string $value): bool => $value === ''
                || (preg_match('/\p{L}/u', $value) === 1 && preg_match('/\d/', $value) === 1),
        );
    }

    /** Wartość musi zawierać co najmniej jeden znak specjalny (nie literę, cyfrę ani spację), np. ! # ?. */
    public function specialCharacter(string $field): self
    {
        return $this->check(
            $field,
            'special_character',
            static fn (string $value): bool => $value === '' || preg_match('/[^\p{L}\d\s]/u', $value) === 1,
        );
    }

    /** Wartość musi być jedną z dozwolonych (np. kategoria wina z listy). */
    public function inList(string $field, array $allowed): self
    {
        return $this->check(
            $field,
            'in_list',
            static fn (string $value): bool => $value === '' || in_array($value, $allowed, true),
        );
    }

    /** Liczba całkowita z przedziału (np. rocznik); same cyfry, bez znaku i spacji. */
    public function integerBetween(string $field, int $min, int $max): self
    {
        return $this->check(
            $field,
            'integer_between',
            static fn (string $value): bool => $value === ''
                || (preg_match('/^\d{1,9}$/', $value) === 1 && (int) $value >= $min && (int) $value <= $max),
            ['min' => $min, 'max' => $max],
        );
    }

    /**
     * Liczba dziesiętna z przedziału, z przecinkiem lub kropką i najwyżej $decimals miejscami
     * po przecinku (np. cena 165,50 albo moc 13,5).
     */
    public function decimalBetween(string $field, int $min, int $max, int $decimals): self
    {
        return $this->check(
            $field,
            'decimal_between',
            static function (string $value) use ($min, $max, $decimals): bool {
                if ($value === '') {
                    return true;
                }

                $pattern = '/^\d{1,9}([.,]\d{1,' . $decimals . '})?$/';

                if (preg_match($pattern, $value) !== 1) {
                    return false;
                }

                $number = (float) str_replace(',', '.', $value);

                return $number >= $min && $number <= $max;
            },
            ['min' => $min, 'max' => $max],
        );
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, array{rule: string, params: array<string, int>}> */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @param callable(string): bool $passes
     * @param array<string, int> $params
     */
    private function check(string $field, string $rule, callable $passes, array $params = []): self
    {
        if (!isset($this->errors[$field]) && !$passes($this->value($field))) {
            $this->errors[$field] = ['rule' => $rule, 'params' => $params];
        }

        return $this;
    }

    private function value(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_string($value) ? $value : '';
    }
}
