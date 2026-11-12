<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SiteContent;
use App\Service\Validator;

abstract class AppController
{
    private static ?SiteContent $content = null;

    /**
     * Renderuje widok. Przy $withLayout widok zawiera tylko <main>, a resztę strony
     * (head, nagłówek, stopkę, skrypty) dokłada views/layout.html.php.
     */
    protected function render(string $template, array $variables = [], bool $withLayout = false): void
    {
        $viewsPath = dirname(__DIR__, 2) . '/public/views/';
        $templatePath = $viewsPath . $template . '.html.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        $content = $this->content();

        // $content, $t (tekst z escapowaniem) i $e (escapowanie) są dostępne w każdym widoku
        $variables += [
            'content' => $content,
            't' => static fn (string $key, array $replace = []): string => self::escape($content->text($key, $replace)),
            'e' => static fn (mixed $value): string => self::escape($value),
        ];

        extract($variables, EXTR_SKIP);

        if (!$withLayout) {
            require $templatePath;

            return;
        }

        ob_start();
        require $templatePath;
        $main = (string) ob_get_clean();

        require $viewsPath . 'layout.html.php';
    }

    /** Teksty i dane marki winnicy (config/content.php). */
    protected function content(): SiteContent
    {
        return self::$content ??= SiteContent::fromFile(dirname(__DIR__, 2) . '/config/content.php');
    }

    /** Tekst z treści (bez escapowania) — np. do tytułu strony przekazywanego do widoku. */
    protected function text(string $key, array $replace = []): string
    {
        return $this->content()->text($key, $replace);
    }

    private static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Wartość pola z POST jako tekst; brak pola albo tablica (np. email[]=x) daje pusty tekst. */
    protected function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * Zamienia błędy walidatora na komunikaty z config/content.php (validation.<reguła>).
     *
     * @return array<string, string> nazwa pola => komunikat
     */
    protected function validationMessages(Validator $validator): array
    {
        $messages = [];

        foreach ($validator->errors() as $field => $error) {
            $messages[$field] = $this->text('validation.' . $error['rule'], $error['params']);
        }

        return $messages;
    }

    /** Przekierowanie po udanym żądaniu POST (303 See Other → przeglądarka wykona GET). */
    protected function redirect(string $path): void
    {
        header('Location: ' . $path, true, 303);
    }
}