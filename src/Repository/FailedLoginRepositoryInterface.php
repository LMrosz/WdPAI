<?php

declare(strict_types=1);

namespace App\Repository;

/** Dziennik nieudanych prób logowania (tabela failed_logins). Nigdy nie zawiera haseł. */
interface FailedLoginRepositoryInterface
{
    /** Powody zapisywane w dzienniku. */
    public const INVALID_CREDENTIALS = 'invalid_credentials';
    public const ACCOUNT_BLOCKED = 'account_blocked';

    /** Zapisuje nieudaną próbę: adres e-mail (małymi literami), adres IP i przeglądarkę. */
    public function record(string $email, string $ipAddress, string $reason, ?string $userAgent): void;

    /**
     * Czasy (rosnąco) nieudanych prób z ostatnich $windowSeconds sekund dla pary adres e-mail + IP.
     *
     * @return list<int> znaczniki czasu UNIX
     */
    public function recentForAccount(string $email, string $ipAddress, int $windowSeconds): array;

    /**
     * Czasy (rosnąco) nieudanych prób z ostatnich $windowSeconds sekund z jednego adresu IP.
     *
     * @return list<int> znaczniki czasu UNIX
     */
    public function recentForIp(string $ipAddress, int $windowSeconds): array;
}
