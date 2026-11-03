<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SiteContent;

abstract class AppController
{
    private static ?SiteContent $content = null;

    protected function render(string $template, array $variables = []): void
    {
        $templatePath = dirname(__DIR__, 2) . '/public/views/' . $template . '.html.php';

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
        require $templatePath;
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

    /** Przekierowanie po udanym żądaniu POST (303 See Other → przeglądarka wykona GET). */
    protected function redirect(string $path): void
    {
        header('Location: ' . $path, true, 303);
    }
}