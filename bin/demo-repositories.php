<?php

declare(strict_types=1);

/**
 * Pokaz warstwy danych z Lab 8: użytkownicy (Value Object Email, role, JOIN) i degustacje (JOIN, wolne miejsca).
 *
 *   docker compose exec php php bin/demo-repositories.php
 *
 * Skrypt zakłada na chwilę konto demo i od razu je usuwa, więc po uruchomieniu baza jest taka sama jak przedtem.
 */

use App\Database;
use App\Exception\EmailAlreadyUsedException;
use App\Model\Email;
use App\Model\Role;
use App\Repository\PostgresTastingRepository;
use App\Repository\PostgresUserRepository;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$pdo = (new Database())->connect();
$users = new PostgresUserRepository($pdo);
$tastings = new PostgresTastingRepository($pdo);

echo "== Value Object Email ==\n";
foreach (['  Gosc@Corvo-Bianco.example ', 'to-nie-jest-email'] as $candidate) {
    try {
        echo '"' . $candidate . '" -> ' . new Email($candidate) . "\n";
    } catch (InvalidArgumentException $exception) {
        echo '"' . $candidate . '" -> ' . $exception->getMessage() . "\n";
    }
}

echo "\n== Użytkownicy (users JOIN roles) ==\n";
$guest = $users->findByEmail(new Email('gosc@corvo-bianco.example'));
echo $guest === null
    ? "Brak konta gosc@corvo-bianco.example (baza bez danych demonstracyjnych).\n"
    : '#' . $guest->id . ' ' . $guest->label() . ', rola ' . $guest->role->value . ', od ' . $guest->createdAt->format('Y-m-d') . "\n";

$demo = $users->create(new Email('demo-warstwa@corvo-bianco.example'), password_hash('Haslo-demo-1!', PASSWORD_DEFAULT), Role::Guest, 'Demo Warstwa');
echo 'Utworzono #' . $demo->id . ' ' . $demo->label() . ', hasło pasuje: ' . var_export($demo->verifyPassword('Haslo-demo-1!'), true) . "\n";

try {
    $users->create($demo->email, $demo->passwordHash, Role::Guest);
} catch (EmailAlreadyUsedException $exception) {
    echo 'Drugi raz ten sam adres: ' . $exception->getMessage() . "\n";
}

echo 'Usunięto konto demo: ' . var_export($users->delete($demo->id), true) . "\n";

echo "\n== Degustacje (tastings, tasting_wines JOIN wines, tasting_sessions LEFT JOIN reservations) ==\n";
foreach ($tastings->findAll() as $tasting) {
    echo $tasting->name . ' (' . $tasting->durationLabel() . ', do ' . $tasting->maxGroupSize . ' osób, ' . $tasting->pricePerPerson . ")\n";
    echo '  wina: ' . ($tasting->wines === [] ? '-' : implode(', ', array_map(static fn ($wine): string => $wine->title, $tasting->wines))) . "\n";

    foreach ($tasting->sessions as $session) {
        echo '  termin: ' . $session->startsAtLabel() . ', wolne miejsca ' . $session->seatsFree() . ' z ' . $session->seatsTotal . "\n";
    }
}
