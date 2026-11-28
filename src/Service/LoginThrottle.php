<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\FailedLoginRepositoryInterface;

/**
 * Ograniczenie liczby prób logowania (ochrona przed zgadywaniem haseł) i dziennik nieudanych prób.
 *
 * Blokada działa na dwa sposoby, w oknie 15 minut:
 *   * 5 błędów dla tej samej pary adres e-mail + adres IP blokuje kolejne próby tej pary,
 *   * 20 błędów z jednego adresu IP blokuje wszystkie próby z tego adresu (zgadywanie wielu kont).
 * Blokada dotyczy pary, a nie samego konta, więc obcy człowiek nie zablokuje cudzego konta
 * ze swojego adresu IP dla jego właściciela, który loguje się z innego miejsca.
 * Adres IP pochodzi z REMOTE_ADDR, a nie z nagłówków wysyłanych przez klienta (X-Forwarded-For
 * można podrobić).
 */
final class LoginThrottle
{
    public const WINDOW_SECONDS = 900;
    public const MAX_FAILURES_PER_ACCOUNT = 5;
    public const MAX_FAILURES_PER_IP = 20;

    public function __construct(private readonly FailedLoginRepositoryInterface $failures)
    {
    }

    /**
     * Ile sekund trzeba jeszcze poczekać z kolejną próbą; 0 oznacza, że logowanie jest dozwolone.
     * Czas liczymy od chwili, gdy najstarszy błąd wypadnie z okna na tyle, że liczba błędów spadnie poniżej limitu.
     */
    public function retryAfter(string $email, string $ipAddress, ?int $now = null): int
    {
        $now ??= time();
        $email = strtolower($email);

        return max(
            $this->waitFor($this->failures->recentForAccount($email, $ipAddress, self::WINDOW_SECONDS), self::MAX_FAILURES_PER_ACCOUNT, $now),
            $this->waitFor($this->failures->recentForIp($ipAddress, self::WINDOW_SECONDS), self::MAX_FAILURES_PER_IP, $now),
        );
    }

    /**
     * Zapisuje nieudaną próbę (do limitu i do audytu) oraz wpis w logu serwera.
     * Hasło nie jest tu nigdzie przekazywane, więc nie ma jak trafić do dziennika.
     */
    public function recordFailure(string $email, string $ipAddress, string $reason, ?string $userAgent): void
    {
        $email = strtolower($email);

        $this->failures->record($email, $ipAddress, $reason, $userAgent);

        // adres e-mail przeszedł walidację formatu, a IP pochodzi z połączenia, więc w linii nie ma znaków sterujących
        error_log(sprintf('[security] failed login: email=%s ip=%s reason=%s', $email, $ipAddress, $reason));
    }

    /** @param list<int> $timestamps rosnąco */
    private function waitFor(array $timestamps, int $limit, int $now): int
    {
        $excess = count($timestamps) - $limit;

        if ($excess < 0) {
            return 0;
        }

        return max(1, $timestamps[$excess] + self::WINDOW_SECONDS - $now);
    }
}
