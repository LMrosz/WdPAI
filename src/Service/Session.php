<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;

/**
 * Konfiguracja i uruchomienie sesji PHP. Wywoływane raz, w public/index.php, przed routingiem.
 *
 * Ciasteczko sesji: bez daty ważności (znika z zamknięciem przeglądarki), HttpOnly (niedostępne
 * dla JavaScriptu), SameSite=Lax (nie jest wysyłane w żądaniach POST z innych witryn) i Secure
 * (przeglądarka nie wyśle go przez HTTP). Secure jest ustawiane, gdy żądanie przyszło przez HTTPS
 * albo gdy aplikacja wymaga HTTPS (zmienna FORCE_HTTPS); wyłączenie tej zmiennej pozwala pracować po
 * http://localhost bez certyfikatu.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        // identyfikator sesji tylko z ciasteczka (nie z adresu), a nieznany identyfikator od klienta
        // jest odrzucany zamiast przyjęty (ochrona przed session fixation)
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_trans_sid', '0');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => self::isHttps() || self::httpsRequired(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Zapisuje zalogowanego użytkownika w sesji. Id sesji jest wymieniane na nowe (ochrona przed
     * session fixation), a token CSRF kasowany, więc po zalogowaniu powstaje świeży. W sesji ląduje
     * tylko to, co potrzebne do nagłówka strony i sprawdzenia roli — nigdy hasło ani jego skrót.
     */
    public static function signIn(User $user): void
    {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);

        $_SESSION['user'] = [
            'id' => $user->id,
            'email' => $user->email->value(),
            'name' => $user->label(),
            'role' => $user->role->value,
            'logged_in_at' => time(),
        ];
    }

    /** @return array{id: int, email: string, name: string, role: string, logged_in_at: int}|null */
    public static function user(): ?array
    {
        $user = $_SESSION['user'] ?? null;

        return is_array($user) && isset($user['id'], $user['role']) ? $user : null;
    }

    /** Czyści dane sesji, wygasza ciasteczko i niszczy sesję (wylogowanie). */
    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?: 'Lax',
            ]);
        }

        session_destroy();
    }

    /** Czy aplikacja ma działać wyłącznie przez HTTPS (zmienna środowiskowa FORCE_HTTPS=1). */
    public static function httpsRequired(): bool
    {
        return filter_var(getenv('FORCE_HTTPS'), FILTER_VALIDATE_BOOLEAN);
    }

    /** Czy bieżące żądanie przyszło przez HTTPS (nginx przekazuje to do PHP-FPM jako HTTPS=on). */
    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}
