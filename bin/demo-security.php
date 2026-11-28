<?php

declare(strict_types=1);

/**
 * Pokaz zabezpieczeń z Lab 12: singleton repozytorium, prepared statements, minimalny zestaw danych
 * o użytkowniku, skrót hasła, limit prób logowania i dziennik nieudanych prób.
 *
 *   docker compose exec php php bin/demo-security.php
 *
 * Skrypt zostawia bazę taką, jaka była: konto i wpisy dziennika, które zakłada, usuwa na końcu.
 */

use App\Database;
use App\Model\Email;
use App\Repository\FailedLoginRepositoryInterface;
use App\Repository\PostgresFailedLoginRepository;
use App\Repository\PostgresUserRepository;
use App\Service\AuthService;
use App\Service\LoginThrottle;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$pdo = Database::shared();

echo "== D1 Singleton ==\n";
$connectionsBefore = (int) $pdo->query("SELECT count(*) FROM pg_stat_activity WHERE datname = current_database()")->fetchColumn();
$users = PostgresUserRepository::instance();
echo 'UserRepository::instance() zwraca ten sam obiekt: ' . var_export($users === PostgresUserRepository::instance(), true) . "\n";
echo 'Database::shared() zwraca to samo połączenie: ' . var_export(Database::shared() === $pdo, true) . "\n";
(new PostgresFailedLoginRepository(Database::shared()));
$connectionsAfter = (int) $pdo->query("SELECT count(*) FROM pg_stat_activity WHERE datname = current_database()")->fetchColumn();
echo 'Nowe połączenia z bazą po utworzeniu kolejnych repozytoriów: ' . ($connectionsAfter - $connectionsBefore) . "\n";

echo "\n== A1 Prepared statements ==\n";
$attack = "' OR '1'='1";
try {
    $found = $users->findByEmail(new Email($attack));
} catch (InvalidArgumentException) {
    $found = null;
    echo "Adres \"$attack\" odrzucony już przez Value Object Email.\n";
}
$statement = $pdo->prepare('SELECT count(*) FROM users WHERE email = :email');
$statement->execute(['email' => $attack]);
echo 'Zapytanie z parametrem i tekstem ataku zwraca wierszy: ' . $statement->fetchColumn() . " (tekst jest daną, nie SQL-em)\n";

echo "\n== E2 i C5 Konto: skrót hasła i minimalny zestaw danych ==\n";
$email = 'demo-security-' . bin2hex(random_bytes(3)) . '@corvo-bianco.example';
$password = 'Demo-Haslo-2026!';
$auth = new AuthService($users);
$created = $auth->register($email, $password);
echo 'Skrót w bazie zaczyna się od ' . substr($created->passwordHash, 0, 4) . ', ma ' . strlen($created->passwordHash) . " znaków, hasła jawnego w nim nie ma\n";
echo 'findById (sesja, lista kont) pobiera skrót hasła? ' . ($users->findById($created->id)?->passwordHash === '' ? 'nie' : 'tak') . "\n";
echo 'findByEmail (tylko logowanie) pobiera skrót hasła? ' . ($users->findByEmail($created->email)?->passwordHash !== '' ? 'tak' : 'nie') . "\n";

echo "\n== A4 i E5 Limit prób logowania i dziennik ==\n";
$throttle = new LoginThrottle(new PostgresFailedLoginRepository($pdo));
$ip = '203.0.113.7';

for ($attempt = 1; $attempt <= LoginThrottle::MAX_FAILURES_PER_ACCOUNT + 1; $attempt++) {
    $wait = $throttle->retryAfter($email, $ip);

    if ($wait > 0) {
        echo "Próba $attempt: zablokowana, kolejna możliwa za $wait s (w aplikacji: 429 i nagłówek Retry-After)\n";
        break;
    }

    $user = $auth->authenticate($email, 'Zle-Haslo-1!');
    $throttle->recordFailure($email, $ip, FailedLoginRepositoryInterface::INVALID_CREDENTIALS, 'demo-security');
    echo "Próba $attempt: " . ($user === null ? 'złe hasło, zapisano w dzienniku' : 'zalogowano') . "\n";
}

echo 'Ten sam adres e-mail z innego IP: ' . ($throttle->retryAfter($email, '203.0.113.8') === 0 ? 'dozwolone' : 'zablokowane') . "\n";
$logged = $pdo->prepare('SELECT count(*) FROM failed_logins WHERE email = :email');
$logged->execute(['email' => $email]);
echo 'Wpisów w failed_logins dla tego konta: ' . $logged->fetchColumn() . " (kolumny: adres e-mail, IP, powód, przeglądarka, czas; bez hasła)\n";

$pdo->prepare('DELETE FROM failed_logins WHERE email = :email')->execute(['email' => $email]);
$users->delete($created->id);
echo "\nKonto demo i wpisy dziennika usunięte.\n";
