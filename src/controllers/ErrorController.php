<?php

declare(strict_types=1);

namespace App\Controller;

final class ErrorController extends AppController
{
    /** Kody HTTP, które mają własny tekst w config/content.php (errors.<klucz>). */
    private const KEYS = [
        400 => 'bad_request',
        401 => 'unauthorized',
        403 => 'forbidden',
        404 => 'not_found',
        500 => 'server_error',
    ];

    /** Powód błędu API (api_errors.<powód>) dla kodów, które nie mają własnego powodu z ApiException. */
    private const API_REASONS = [
        400 => 'bad_request',
        401 => 'unauthorized',
        403 => 'forbidden',
        404 => 'not_found',
        409 => 'conflict',
        422 => 'invalid_body',
        500 => 'server_error',
    ];

    /**
     * Błąd żądania do /api/...: JSON z kodem, powodem i komunikatem zamiast strony HTML,
     * bo skrypt nie wyświetli całej strony błędu. Nieznany kod to 500.
     */
    public function showJson(int $statusCode, ?string $reason = null): void
    {
        $statusCode = isset(self::API_REASONS[$statusCode]) ? $statusCode : 500;
        $reason ??= self::API_REASONS[$statusCode];

        $this->json([
            'error' => [
                'status' => $statusCode,
                'reason' => $reason,
                'message' => $this->text('api_errors.' . $reason),
            ],
        ], $statusCode);
    }

    /** Strona błędu z odpowiednim kodem odpowiedzi; nieznany kod wyświetla się jako 500. */
    public function show(int $statusCode): void
    {
        $key = self::KEYS[$statusCode] ?? self::KEYS[500];

        http_response_code(isset(self::KEYS[$statusCode]) ? $statusCode : 500);

        $this->render('error', [
            'pageTitle' => $this->text("errors.$key.page_title"),
            'activePage' => null,
            'errorKey' => $key,
            // po 401 sensowny następny krok to logowanie, a nie strona główna
            'backUrl' => $statusCode === 401 ? '/login' : '/',
        ], true);
    }
}
