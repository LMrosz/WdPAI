<?php

declare(strict_types=1);

namespace App\Controller;

final class ErrorController extends AppController
{
    /** Kody HTTP, które mają własny tekst w config/content.php (errors.<klucz>). */
    private const KEYS = [
        400 => 'bad_request',
        404 => 'not_found',
        500 => 'server_error',
    ];

    /** Strona błędu z odpowiednim kodem odpowiedzi; nieznany kod wyświetla się jako 500. */
    public function show(int $statusCode): void
    {
        $key = self::KEYS[$statusCode] ?? self::KEYS[500];

        http_response_code(isset(self::KEYS[$statusCode]) ? $statusCode : 500);

        $this->render('error', [
            'pageTitle' => $this->text("errors.$key.page_title"),
            'activePage' => null,
            'errorKey' => $key,
        ], true);
    }
}
