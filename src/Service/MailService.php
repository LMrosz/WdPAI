<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MailException;
use App\Model\TastingReminder;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Wysyłka wiadomości e-mail przez SMTP (PHPMailer). Kontrolery i zadania nie znają protokołu:
 * wołają gotową metodę (sendWelcomeEmail, sendTastingReminder), a każdy błąd wysyłki kończy się
 * wyjątkiem MailException, który wywołujący łapie, żeby awaria poczty nie psuła reszty działania.
 *
 * Teksty wiadomości pochodzą z config/content.php (klucze mail.*), a dane serwera z config/mail.php
 * (zmienne środowiskowe). Lokalnie wiadomości przechwytuje Mailpit (http://localhost:8025).
 */
final class MailService
{
    /** Krótki limit połączenia, żeby niedostępny serwer poczty nie blokował żądania HTTP (domyślnie PHPMailer czeka 5 minut). */
    private const TIMEOUT_SECONDS = 5;

    /** @var \Closure(): PHPMailer */
    private readonly \Closure $newMailer;

    /** @param (\Closure(): PHPMailer)|null $newMailer fabryka obiektu PHPMailer (w testach można podstawić własny) */
    public function __construct(
        private readonly MailConfig $config,
        private readonly SiteContent $content,
        ?\Closure $newMailer = null,
    ) {
        $this->newMailer = $newMailer ?? static fn (): PHPMailer => new PHPMailer(true);
    }

    /** Serwis ze standardową konfiguracją aplikacji (config/mail.php i config/content.php). */
    public static function create(): self
    {
        $root = dirname(__DIR__, 2);

        return new self(
            MailConfig::fromFile($root . '/config/mail.php'),
            SiteContent::fromFile($root . '/config/content.php'),
        );
    }

    /**
     * Wiadomość powitalna po założeniu konta.
     *
     * @throws MailException gdy wysyłka się nie powiodła
     */
    public function sendWelcomeEmail(string $to, string $name): void
    {
        $name = $this->cleanName($name);
        $link = $this->link('/tastings');

        $this->deliver(
            $to,
            $name,
            $this->content->text('mail.welcome.subject'),
            $this->content->text('mail.welcome.greeting', ['name' => $name]),
            [$this->content->text('mail.welcome.text')],
            $link === '' ? null : [$this->content->text('mail.welcome.button'), $link],
        );
    }

    /**
     * Przypomnienie o zbliżającej się degustacji.
     *
     * @throws MailException gdy wysyłka się nie powiodła
     */
    public function sendTastingReminder(TastingReminder $reminder): void
    {
        $name = $this->cleanName($reminder->userName);
        $details = [
            'tasting' => $reminder->session->tastingName,
            'date' => $reminder->session->startsAtLabel(),
            'seats' => $reminder->seats,
        ];
        $link = $this->link('/tastings');

        $this->deliver(
            $reminder->email,
            $name,
            $this->content->text('mail.reminder.subject', $details),
            $this->content->text('mail.reminder.greeting', ['name' => $name]),
            [
                $this->content->text('mail.reminder.text', $details),
                $this->content->text('mail.reminder.cancel_hint'),
            ],
            $link === '' ? null : [$this->content->text('mail.reminder.button'), $link],
        );
    }

    /**
     * @param list<string> $paragraphs akapity treści
     * @param array{0: string, 1: string}|null $button etykieta i adres przycisku
     */
    private function deliver(
        string $to,
        string $toName,
        string $subject,
        string $greeting,
        array $paragraphs,
        ?array $button,
    ): void {
        $signature = $this->content->text('mail.signature');

        $text = $greeting . "\r\n\r\n" . implode("\r\n\r\n", $paragraphs);

        if ($button !== null) {
            $text .= "\r\n\r\n" . $button[0] . ': ' . $button[1];
        }

        $text .= "\r\n\r\n" . $signature;

        try {
            $mailer = ($this->newMailer)();
            $this->configure($mailer);

            $mailer->setFrom($this->config->fromAddress, $this->config->fromName);
            $mailer->addAddress($to, $toName);
            $mailer->Subject = $subject;
            $mailer->isHTML(true);
            $mailer->Body = $this->html($greeting, $paragraphs, $button, $signature);
            $mailer->AltBody = $text;

            $mailer->send();
        } catch (\Throwable $exception) {
            // także brak biblioteki (Error) i niepoprawny adres odbiorcy kończą się tym samym, kontrolowanym wyjątkiem
            throw new MailException('E-mail sending failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private function configure(PHPMailer $mailer): void
    {
        $mailer->isSMTP();
        $mailer->Host = $this->config->host;
        $mailer->Port = $this->config->port;
        $mailer->Timeout = self::TIMEOUT_SECONDS;
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->SMTPDebug = 0;

        $mailer->SMTPAuth = $this->config->usesAuthentication();
        $mailer->Username = $this->config->username;
        $mailer->Password = $this->config->password;

        // bez jawnego szyfrowania nie próbujemy też opportunistycznego STARTTLS (Mailpit go nie oferuje)
        $mailer->SMTPAutoTLS = $this->config->encryption !== '';
        $mailer->SMTPSecure = match ($this->config->encryption) {
            'tls' => PHPMailer::ENCRYPTION_STARTTLS,
            'ssl' => PHPMailer::ENCRYPTION_SMTPS,
            default => '',
        };
    }

    /**
     * Prosty, jednokolumnowy HTML w kolorach marki (style inline, bo klienty poczty nie czytają arkuszy CSS).
     * Każda wartość z zewnątrz przechodzi przez htmlspecialchars.
     *
     * @param list<string> $paragraphs
     * @param array{0: string, 1: string}|null $button
     */
    private function html(string $greeting, array $paragraphs, ?array $button, string $signature): string
    {
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $brand = $escape($this->content->text('site.name'));

        $body = '<p style="margin:0 0 16px;font-size:18px;color:#241015;">' . $escape($greeting) . '</p>';

        foreach ($paragraphs as $paragraph) {
            $body .= '<p style="margin:0 0 16px;line-height:1.5;color:#241015;">' . $escape($paragraph) . '</p>';
        }

        if ($button !== null) {
            $body .= '<p style="margin:24px 0;"><a href="' . $escape($button[1]) . '" style="display:inline-block;'
                . 'padding:12px 24px;background:#6b1d2f;color:#ffffff;text-decoration:none;border-radius:4px;">'
                . $escape($button[0]) . '</a></p>';
        }

        $body .= '<p style="margin:24px 0 0;color:#5e4b50;">' . $escape($signature) . '</p>';

        return '<!doctype html><html lang="pl"><body style="margin:0;padding:24px;background:#fbf6f3;'
            . 'font-family:Georgia,\'Times New Roman\',serif;">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-top:4px solid #d4af37;padding:32px;">'
            . '<h1 style="margin:0 0 24px;font-size:22px;color:#6b1d2f;">' . $brand . '</h1>'
            . $body . '</div></body></html>';
    }

    /** Pełny adres w aplikacji albo pusty tekst, gdy nie znamy jej adresu (wtedy wiadomość jest bez przycisku). */
    private function link(string $path): string
    {
        return $this->config->appUrl === '' ? '' : $this->config->appUrl . $path;
    }

    /** Imię trafia do treści i nagłówka To, więc usuwamy znaki końca linii (wstrzykiwanie nagłówków). */
    private function cleanName(string $name): string
    {
        return trim((string) preg_replace('/[\r\n\t]+/', ' ', $name));
    }
}
