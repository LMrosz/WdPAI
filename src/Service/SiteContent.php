<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Dostęp do tekstów i danych marki winnicy po kluczach z kropką (np. "home.title").
 * Źródłem jest tablica — dziś plik config/content.php, później np. repozytorium z bazy.
 */
final class SiteContent
{
    /** @param array<string, mixed> $data */
    public function __construct(private array $data)
    {
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new \RuntimeException('Content file not found: ' . $path);
        }

        $data = require $path;

        if (!is_array($data)) {
            throw new \RuntimeException('Content file must return an array: ' . $path);
        }

        return new self($data);
    }

    /** Surowa wartość (tekst lub lista, np. "home.stats"); null, gdy klucza nie ma. */
    public function get(string $key): mixed
    {
        $value = $this->data;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Tekst z podstawionymi znacznikami {klucz} (z treści) oraz {nazwa} z $replace.
     * Brakujący klucz zwraca sam klucz, dzięki czemu luka w treści jest widoczna na stronie.
     *
     * @param array<string, scalar> $replace
     */
    public function text(string $key, array $replace = []): string
    {
        $value = $this->get($key);

        return is_string($value) ? $this->fill($value, $replace) : $key;
    }

    /**
     * Podstawia znaczniki {klucz} w dowolnym tekście (np. w opisie pozycji z listy).
     *
     * @param array<string, scalar> $replace
     */
    public function fill(string $template, array $replace = []): string
    {
        return (string) preg_replace_callback(
            '/\{([a-z0-9_.]+)\}/i',
            function (array $match) use ($replace): string {
                $name = $match[1];

                if (array_key_exists($name, $replace)) {
                    return (string) $replace[$name];
                }

                $found = $this->get($name);

                return is_string($found) ? $found : $match[0];
            },
            $template,
        );
    }
}
