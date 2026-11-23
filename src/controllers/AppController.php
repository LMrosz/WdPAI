<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database;
use App\Exception\BadRequestException;
use App\Exception\ForbiddenException;
use App\Exception\UnauthorizedException;
use App\Model\Permission;
use App\Model\Role;
use App\Model\User;
use App\Repository\PostgresUserRepository;
use App\Repository\UserRepositoryInterface;
use App\Service\Session;
use App\Service\SiteContent;
use App\Service\Validator;

abstract class AppController
{
    private static ?SiteContent $content = null;

    private ?UserRepositoryInterface $userRepository = null;

    /**
     * Renderuje widok. Przy $withLayout widok zawiera tylko <main>, a resztę strony
     * (head, nagłówek, stopkę, skrypty) dokłada views/layout.html.php.
     */
    protected function render(string $template, array $variables = [], bool $withLayout = false): void
    {
        $viewsPath = dirname(__DIR__, 2) . '/public/views/';
        $templatePath = $viewsPath . $template . '.html.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        $content = $this->content();

        // $content, $t (tekst z escapowaniem), $e (escapowanie), $csrf (token do formularzy POST)
        // i $authUser (dane z sesji albo null) są dostępne w każdym widoku
        $variables += [
            'content' => $content,
            't' => static fn (string $key, array $replace = []): string => self::escape($content->text($key, $replace)),
            'e' => static fn (mixed $value): string => self::escape($value),
            'csrf' => fn (): string => $this->csrfToken(),
            'authUser' => Session::user(),
        ];

        extract($variables, EXTR_SKIP);

        if (!$withLayout) {
            require $templatePath;

            return;
        }

        ob_start();
        require $templatePath;
        $main = (string) ob_get_clean();

        // komunikat zapisany przez flash() przed przekierowaniem pokazuje się raz, na kolejnej stronie
        $flashKey = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $flash = is_string($flashKey) ? $content->text($flashKey) : null;

        require $viewsPath . 'layout.html.php';
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

    /** Wartość pola z POST jako tekst; brak pola albo tablica (np. email[]=x) daje pusty tekst. */
    protected function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * Zamienia błędy walidatora na komunikaty z config/content.php (validation.<reguła>).
     *
     * @return array<string, string> nazwa pola => komunikat
     */
    protected function validationMessages(Validator $validator): array
    {
        $messages = [];

        foreach ($validator->errors() as $field => $error) {
            $messages[$field] = $this->text('validation.' . $error['rule'], $error['params']);
        }

        return $messages;
    }

    /** Id z adresu (/wines/{id}) jako liczba; coś innego niż dodatnia liczba całkowita to błąd klienta (400). */
    protected function idFromPath(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            throw new BadRequestException('Id must be a positive integer, got "' . $id . '"');
        }

        return (int) $id;
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * Przekierowanie po udanym żądaniu POST (303 See Other → przeglądarka wykona GET).
     * Kończy skrypt, żeby kod za przekierowaniem nigdy się nie wykonał. Ścieżka musi być adresem
     * wewnątrz aplikacji (zaczynać się od jednego "/"), więc nie da się przekierować na obcą stronę.
     */
    protected function redirect(string $path): never
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            throw new \InvalidArgumentException('Redirect target must be a local path.');
        }

        header('Location: ' . $path, true, 303);
        exit;
    }

    /** Zapamiętuje komunikat (klucz z config/content.php, "flash.*"), który pokaże się po przekierowaniu. */
    protected function flash(string $contentKey): void
    {
        $_SESSION['flash'] = $contentKey;
    }

    protected function isLoggedIn(): bool
    {
        return Session::user() !== null;
    }

    /** Rola z sesji — tylko do decydowania, co pokazać w widoku; o dostępie rozstrzyga requirePermission(). */
    protected function sessionRole(): ?Role
    {
        $user = Session::user();

        return $user === null ? null : Role::tryFrom((string) $user['role']);
    }

    /** Czy zalogowany użytkownik ma uprawnienie według roli z sesji (np. czy pokazać przycisk "Dodaj"). */
    protected function can(Permission $permission): bool
    {
        return $this->sessionRole()?->can($permission) ?? false;
    }

    /** Akcje dla zalogowanych zaczynają się od tego wywołania; bez sesji rzuca UnauthorizedException (401). */
    protected function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            throw new UnauthorizedException('Login required');
        }
    }

    /**
     * Zalogowany użytkownik odczytany z bazy (świeża rola i istnienie konta, a nie sama sesja).
     * Gdy konto zostało usunięte, sesja jest niszczona, a wynik to 401.
     */
    protected function requireUser(): User
    {
        $this->requireLogin();

        $user = $this->users()->findById((int) Session::user()['id']);

        if ($user === null) {
            Session::destroy();

            throw new UnauthorizedException('Account no longer exists');
        }

        return $user;
    }

    /** Jak requireUser(), a dodatkowo rola musi mieć uprawnienie; w przeciwnym razie 403. */
    protected function requirePermission(Permission $permission): User
    {
        $user = $this->requireUser();

        if (!$user->role->can($permission)) {
            throw new ForbiddenException('Role ' . $user->role->value . ' lacks ' . $permission->value);
        }

        return $user;
    }

    /** Token CSRF sesji (tworzony przy pierwszym użyciu); trafia do ukrytego pola każdego formularza POST. */
    protected function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    protected function isCsrfTokenValid(string $token): bool
    {
        $expected = $_SESSION['csrf_token'] ?? null;

        return is_string($expected) && $token !== '' && hash_equals($expected, $token);
    }

    /** Pierwsze wywołanie w każdej akcji POST: żądanie bez poprawnego tokenu kończy się błędem 403. */
    protected function requireValidCsrf(): void
    {
        if (!$this->isCsrfTokenValid($this->postString('csrf_token'))) {
            throw new ForbiddenException('Invalid CSRF token');
        }
    }

    protected function users(): UserRepositoryInterface
    {
        return $this->userRepository ??= new PostgresUserRepository((new Database())->connect());
    }
}