<?php

declare(strict_types=1);

/**
 * Pokaz uwierzytelniania z Lab 9: rejestracja (skrót hasła), logowanie i uprawnienia ról.
 *
 *   docker compose exec php php bin/demo-auth.php
 *
 * Skrypt zakłada na chwilę konto demo i od razu je usuwa, więc po uruchomieniu baza jest taka sama jak przedtem.
 */

use App\Database;
use App\Exception\EmailAlreadyUsedException;
use App\Model\Email;
use App\Model\Permission;
use App\Model\Role;
use App\Repository\PostgresUserRepository;
use App\Service\AuthService;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$users = new PostgresUserRepository((new Database())->connect());
$auth = new AuthService($users);

$email = 'demo-auth-' . bin2hex(random_bytes(3)) . '@corvo-bianco.example';
$password = 'Demo-Haslo-2026!';

echo "== Rejestracja ==\n";
$user = $auth->register(strtoupper($email), $password);
echo 'Konto #' . $user->id . ' ' . $user->email . ', rola ' . $user->role->value . "\n";
echo 'Skrót hasła w bazie: ' . substr($user->passwordHash, 0, 7) . '... (' . strlen($user->passwordHash) . " znaków)\n";
echo 'Hasło jawne w skrócie? ' . (str_contains($user->passwordHash, $password) ? 'tak' : 'nie') . "\n";

try {
    $auth->register($email, $password);
    echo "Drugie konto z tym samym adresem: założone (błąd!)\n";
} catch (EmailAlreadyUsedException) {
    echo "Drugie konto z tym samym adresem: odrzucone (UNIQUE w bazie)\n";
}

echo "\n== Logowanie ==\n";
$attempts = [
    'poprawne hasło' => [$email, $password],
    'adres wielkimi literami' => [strtoupper($email), $password],
    'złe hasło' => [$email, 'Inne-Haslo-1!'],
    'nieistniejące konto' => ['nikt@corvo-bianco.example', $password],
    'niepoprawny adres' => ['to-nie-jest-email', $password],
];

foreach ($attempts as $label => [$address, $attempt]) {
    $result = $auth->authenticate($address, $attempt);
    echo mb_str_pad($label, 26) . ($result === null ? 'odmowa (jeden ogólny komunikat)' : 'zalogowano jako #' . $result->id) . "\n";
}

echo "\n== Uprawnienia ról ==\n";
foreach (Role::cases() as $role) {
    $allowed = array_map(
        static fn (Permission $permission): string => $permission->value,
        $role->permissions(),
    );
    echo mb_str_pad($role->value, 15) . ($allowed === [] ? '(tylko przeglądanie)' : implode(', ', $allowed)) . "\n";
}

echo "\n== Sprzątanie ==\n";
echo $users->delete($user->id) ? "Konto demo usunięte.\n" : "Nie udało się usunąć konta demo.\n";
