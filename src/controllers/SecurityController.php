<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database;
use App\Exception\AccountBlockedException;
use App\Exception\EmailAlreadyUsedException;
use App\Exception\MailException;
use App\Repository\FailedLoginRepositoryInterface;
use App\Repository\PostgresFailedLoginRepository;
use App\Service\AuthService;
use App\Service\LoginThrottle;
use App\Service\MailService;
use App\Service\Session;
use App\Service\Validator;

final class SecurityController extends AppController
{
    /** Limity długości pól (ochrona przed zbyt długimi danymi). */
    private const EMAIL_MAX_LENGTH = 254;
    private const PASSWORD_MIN_LENGTH = 8;
    private const PASSWORD_MAX_LENGTH = 64;
    /** bcrypt (PASSWORD_DEFAULT) ignoruje bajty hasła po 72. */
    private const PASSWORD_MAX_BYTES = 72;

    /**
     * Reguły pól logowania i rejestracji. Ten sam zapis sprawdza serwer (Validator::rules)
     * i przeglądarka (widok wypisuje go w atrybucie data-rules, czyta go public/js).
     */
    private const LOGIN_RULES = [
        'email' => ['required', 'email', 'max_length:' . self::EMAIL_MAX_LENGTH],
        'password' => ['required', 'max_length:' . self::PASSWORD_MAX_LENGTH],
    ];

    private const REGISTER_RULES = [
        'email' => ['required', 'email', 'max_length:' . self::EMAIL_MAX_LENGTH],
        'password' => [
            'required',
            'min_length:' . self::PASSWORD_MIN_LENGTH,
            'max_length:' . self::PASSWORD_MAX_LENGTH,
            'max_bytes:' . self::PASSWORD_MAX_BYTES,
            'letter_and_digit',
            'special_character',
        ],
        'password_confirmation' => ['required', 'matches:password'],
    ];

    private ?AuthService $auth = null;
    private ?LoginThrottle $throttle = null;
    private ?MailService $mail;

    /** Serwis poczty można podać z zewnątrz (testy); bez niego powstaje dopiero przy pierwszej wysyłce. */
    public function __construct(?MailService $mail = null)
    {
        $this->mail = $mail;
    }

    /** GET /login – formularz logowania (GUEST, CELLAR_MASTER, WINERY_OWNER). */
    public function login(): void
    {
        $this->requireSecureConnection();
        $this->redirectIfLoggedIn();
        $this->renderLogin();
    }

    /**
     * POST /login – obsługa formularza.
     * Brak pola albo zły token CSRF to 403, błędny format pól to 422 z komunikatami przy polach.
     * Zły adres lub hasło dają jeden, ogólny komunikat (nie zdradza, czy konto istnieje).
     * Zablokowane konto z poprawnym hasłem dostaje 403 i osobny komunikat.
     * Po 5 nieudanych próbach z jednego adresu IP na to samo konto (albo 20 na dowolne konta)
     * logowanie jest wstrzymane na kilka minut: 429 z nagłówkiem Retry-After. Każda nieudana próba
     * trafia do dziennika failed_logins (bez hasła).
     * Po poprawnym logowaniu id sesji jest wymieniane, a użytkownik trafia na /account (303).
     */
    public function authenticate(): void
    {
        $this->requireSecureConnection();
        $this->requireValidCsrf();

        $email = trim($this->postString('email'));
        $password = $this->postString('password');

        $validator = Validator::withRules(['email' => $email, 'password' => $password], self::LOGIN_RULES);

        if ($validator->fails()) {
            http_response_code(422);
            $this->renderLogin($this->validationMessages($validator), ['email' => $email]);

            return;
        }

        $ipAddress = $this->clientIp();

        // blokada sprawdzana przed weryfikacją hasła, więc w czasie blokady nawet poprawne hasło nie otwiera konta
        $retryAfter = $this->throttle()->retryAfter($email, $ipAddress);

        if ($retryAfter > 0) {
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            $this->renderLogin([], ['email' => $email], $this->text('login.too_many_attempts', [
                'minutes' => (string) max(1, (int) ceil($retryAfter / 60)),
            ]));

            return;
        }

        try {
            $user = $this->auth()->authenticate($email, $password);
        } catch (AccountBlockedException) {
            $this->throttle()->recordFailure($email, $ipAddress, FailedLoginRepositoryInterface::ACCOUNT_BLOCKED, $this->userAgent());
            http_response_code(403);
            $this->renderLogin([], ['email' => $email], $this->text('login.account_blocked'));

            return;
        }

        if ($user === null) {
            $this->throttle()->recordFailure($email, $ipAddress, FailedLoginRepositoryInterface::INVALID_CREDENTIALS, $this->userAgent());
            http_response_code(422);
            $this->renderLogin([], ['email' => $email], $this->text('login.invalid_credentials'));

            return;
        }

        Session::signIn($user);

        $this->redirect('/account');
    }

    /** GET /register – formularz rejestracji. */
    public function register(): void
    {
        $this->requireSecureConnection();
        $this->redirectIfLoggedIn();
        $this->renderRegister();
    }

    /**
     * POST /register – obsługa formularza.
     * Błędy wracają do formularza z kodem 422. Poprawne dane zakładają konto gościa z hasłem
     * zapisanym jako skrót i kończą się przekierowaniem (303) na stronę logowania.
     */
    public function createAccount(): void
    {
        $this->requireSecureConnection();
        $this->requireValidCsrf();

        $email = trim($this->postString('email'));

        $validator = Validator::withRules([
            'email' => $email,
            'password' => $this->postString('password'),
            'password_confirmation' => $this->postString('password_confirmation'),
        ], self::REGISTER_RULES);

        if ($validator->fails()) {
            http_response_code(422);
            $this->renderRegister($this->validationMessages($validator), ['email' => $email]);

            return;
        }

        try {
            $user = $this->auth()->register($email, $this->postString('password'));
        } catch (EmailAlreadyUsedException) {
            // także gdy dwa żądania z tym samym adresem trafią do bazy równocześnie (UNIQUE w bazie)
            http_response_code(422);
            $this->renderRegister(['email' => $this->text('register.email_taken')], ['email' => $email]);

            return;
        }

        // konto już istnieje, więc ewentualna awaria poczty niczego nie cofa: użytkownik dostaje tylko ostrzeżenie
        $this->redirect('/login?registered=1&mail=' . ($this->sendWelcomeEmail($user->email->value(), $user->label()) ? 'sent' : 'failed'));
    }

    /**
     * POST /logout – wylogowanie. To POST z tokenem CSRF, bo zwykły link GET dałoby się wywołać
     * z obcej strony (np. obrazkiem) i wylogować użytkownika bez jego wiedzy.
     */
    public function logout(): void
    {
        $this->requireSecureConnection();
        $this->requireValidCsrf();

        Session::destroy();

        // sesja nie istnieje, więc komunikat przechodzi w adresie (jak ?registered=1)
        $this->redirect('/login?logged_out=1');
    }

    /**
     * Wysyła wiadomość powitalną. Zwraca false, gdy się nie udało; szczegóły błędu SMTP trafiają
     * tylko do dziennika serwera, nigdy do użytkownika.
     */
    private function sendWelcomeEmail(string $email, string $name): bool
    {
        try {
            ($this->mail ??= MailService::create())->sendWelcomeEmail($email, $name);

            return true;
        } catch (MailException $exception) {
            error_log('Welcome e-mail failed: ' . $exception->getMessage());

            return false;
        }
    }

    /** Serwis ograniczający próby logowania powstaje przy pierwszym użyciu, na wspólnym połączeniu z bazą. */
    private function throttle(): LoginThrottle
    {
        return $this->throttle ??= new LoginThrottle(new PostgresFailedLoginRepository(Database::shared()));
    }

    /** Adres IP połączenia (REMOTE_ADDR); nagłówków typu X-Forwarded-For nie używamy, bo klient może je podrobić. */
    private function clientIp(): string
    {
        $address = $_SERVER['REMOTE_ADDR'] ?? '';

        return filter_var($address, FILTER_VALIDATE_IP) !== false ? $address : '0.0.0.0';
    }

    private function userAgent(): ?string
    {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // do dziennika trafia tylko tekst bez znaków sterujących
        return is_string($agent) ? preg_replace('/[[:cntrl:]]/', '', $agent) : null;
    }

    /** Zalogowany nie ma po co oglądać formularzy logowania i rejestracji. */
    private function redirectIfLoggedIn(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/account');
        }
    }

    /** Serwis powstaje dopiero przy pierwszym użyciu, więc sam formularz nie łączy się z bazą. */
    private function auth(): AuthService
    {
        return $this->auth ??= new AuthService($this->users());
    }

    /**
     * @param array<string, string> $errors nazwa pola => komunikat
     * @param array<string, string> $old wcześniej wpisane wartości (bez hasła)
     * @param string|null $formError ogólny błąd całego formularza (np. zły adres lub hasło)
     */
    private function renderLogin(array $errors = [], array $old = [], ?string $formError = null): void
    {
        $this->render('login', [
            'pageTitle' => $this->text('login.page_title'),
            'activePage' => 'login',
            'formAction' => '/login',
            'rules' => self::LOGIN_RULES,
            'errors' => $errors,
            'old' => $old,
            'formError' => $formError,
            'registered' => ($_GET['registered'] ?? null) === '1',
            // tylko wartości z zamkniętej listy; cokolwiek innego w adresie jest ignorowane
            'mailStatus' => in_array($_GET['mail'] ?? null, ['sent', 'failed'], true) ? $_GET['mail'] : null,
            'loggedOut' => ($_GET['logged_out'] ?? null) === '1',
        ], true);
    }

    /**
     * @param array<string, string> $errors nazwa pola => komunikat
     * @param array<string, string> $old wcześniej wpisane wartości (bez haseł)
     */
    private function renderRegister(array $errors = [], array $old = []): void
    {
        $this->render('register', [
            'pageTitle' => $this->text('register.page_title'),
            'activePage' => 'login',
            'formAction' => '/register',
            'rules' => self::REGISTER_RULES,
            'errors' => $errors,
            'old' => $old,
            'passwordMinLength' => self::PASSWORD_MIN_LENGTH,
        ], true);
    }
}
