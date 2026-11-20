<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Jedyne miejsce, w którym powstaje połączenie PDO z PostgreSQL.
 * Konfiguracja pochodzi z config/database.php (zmienne środowiskowe), a repozytoria
 * dostają gotowy obiekt PDO.
 */
final class Database
{
    /** @var array{host: string, port: int, name: string, user: string, password: string} */
    private array $config;

    /** @param array{host: string, port: int, name: string, user: string, password: string}|null $config */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require dirname(__DIR__) . '/config/database.php';
    }

    public function connect(): PDO
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=prefer',
            $this->config['host'],
            $this->config['port'],
            $this->config['name'],
        );

        try {
            return new PDO($dsn, $this->config['user'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // natywne prepared statements: dane są przekazywane osobno od składni SQL
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            // Komunikat sterownika może zdradzać host lub nazwę bazy, więc wyjątek niesie ogólny
            // tekst, a szczegóły (poprzedni wyjątek) trafiają do logu w public/index.php.
            throw new RuntimeException('Nie udało się połączyć z bazą danych.', 0, $exception);
        }
    }
}
