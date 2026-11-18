<?php

declare(strict_types=1);

/**
 * Dane połączenia z PostgreSQL.
 *
 * Wartości pochodzą ze zmiennych środowiskowych kontenera php. Ustawia je docker-compose.yaml
 * na podstawie pliku .env (jest w .gitignore), więc hasło nie trafia do repozytorium.
 * W Dockerze host bazy to nazwa usługi (db), a nie localhost, i port wewnętrzny 5432
 * (port 5433 z docker-compose.yaml jest tylko dla klientów uruchomionych na komputerze).
 */
return [
    'host' => getenv('DB_HOST') ?: 'db',
    'port' => (int) (getenv('DB_PORT') ?: 5432),
    'name' => getenv('DB_NAME') ?: 'app',
    'user' => getenv('DB_USER') ?: 'app',
    'password' => (string) getenv('DB_PASSWORD'),
];
