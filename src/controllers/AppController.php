<?php

declare(strict_types=1);

namespace App\Controller;

abstract class AppController
{
    protected function render(string $template, array $variables = []): void
    {
        $templatePath = dirname(__DIR__, 2) . '/public/views/' . $template . '.html.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        extract($variables, EXTR_SKIP);
        require $templatePath;
    }

    /** Przekierowanie po udanym żądaniu POST (303 See Other → przeglądarka wykona GET). */
    protected function redirect(string $path): void
    {
        header('Location: ' . $path, true, 303);
    }
}