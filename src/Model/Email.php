<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Value Object: adres e-mail. Nie ma tożsamości (liczy się wartość), jest niezmienny
 * i waliduje się w konstruktorze, więc każdy obiekt Email jest poprawny.
 * Adres zapisujemy małymi literami, tak jak wymaga tego ograniczenie users_email_check.
 */
final readonly class Email
{
    private const MAX_LENGTH = 254;

    private string $value;

    public function __construct(string $address)
    {
        $normalized = mb_strtolower(trim($address));

        if ($normalized === '' || mb_strlen($normalized) > self::MAX_LENGTH
            || filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Niepoprawny adres e-mail.');
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
