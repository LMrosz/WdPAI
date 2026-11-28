<?php

declare(strict_types=1);

namespace App\Service;

/** Ustawienia serwera SMTP i nadawcy; powstają z tablicy z config/mail.php. */
final readonly class MailConfig
{
    public const ENCRYPTIONS = ['', 'tls', 'ssl'];

    public function __construct(
        public string $host,
        public int $port,
        public string $fromAddress,
        public string $fromName,
        public string $appUrl = '',
        public string $username = '',
        public string $password = '',
        public string $encryption = '',
    ) {
        if (!in_array($encryption, self::ENCRYPTIONS, true)) {
            throw new \InvalidArgumentException('MAIL_ENCRYPTION must be empty, "tls" or "ssl".');
        }
    }

    /** @param array<string, mixed> $config tablica zwracana przez config/mail.php */
    public static function fromArray(array $config): self
    {
        return new self(
            host: (string) $config['host'],
            port: (int) $config['port'],
            fromAddress: (string) $config['from_address'],
            fromName: (string) $config['from_name'],
            appUrl: (string) ($config['app_url'] ?? ''),
            username: (string) ($config['username'] ?? ''),
            password: (string) ($config['password'] ?? ''),
            encryption: (string) ($config['encryption'] ?? ''),
        );
    }

    public static function fromFile(string $path): self
    {
        $config = require $path;

        if (!is_array($config)) {
            throw new \RuntimeException('Mail config must return an array: ' . $path);
        }

        return self::fromArray($config);
    }

    /** Logowanie do SMTP włączamy tylko wtedy, gdy podano użytkownika (Mailpit go nie wymaga). */
    public function usesAuthentication(): bool
    {
        return $this->username !== '';
    }
}
