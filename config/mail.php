<?php

declare(strict_types=1);

/**
 * Ustawienia poczty wychodzącej (SMTP).
 *
 * Wartości pochodzą ze zmiennych środowiskowych kontenera php. Ustawia je docker-compose.yaml
 * na podstawie pliku .env (jest w .gitignore), więc dane serwera pocztowego nie trafiają do repozytorium.
 * Lokalnie wiadomości przechwytuje Mailpit (host mailpit, port 1025, bez logowania i szyfrowania),
 * a na produkcji wystarczy ustawić MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD i MAIL_ENCRYPTION.
 */
return [
    'host' => getenv('MAIL_HOST') ?: 'mailpit',
    'port' => (int) (getenv('MAIL_PORT') ?: 1025),
    'username' => (string) getenv('MAIL_USERNAME'),
    'password' => (string) getenv('MAIL_PASSWORD'),
    // pusty = bez szyfrowania (Mailpit), tls = STARTTLS (zwykle port 587), ssl = SMTPS (zwykle port 465)
    'encryption' => strtolower((string) getenv('MAIL_ENCRYPTION')),
    'from_address' => getenv('MAIL_FROM') ?: 'noreply@corvobianco.local',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Corvo Bianco',
    // adres, pod którym użytkownicy widzą aplikację; służy do linków w wiadomościach
    'app_url' => rtrim((string) (getenv('APP_URL') ?: 'https://localhost:8443'), '/'),
];
