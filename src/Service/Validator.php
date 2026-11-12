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
