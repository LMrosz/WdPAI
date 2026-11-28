<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MailException;
use App\Repository\ReminderRepositoryInterface;

/**
 * Zadanie cykliczne: wysyła gościom przypomnienie o degustacji, która zaczyna się w ciągu najbliższych godzin.
 *
 * Można je bezpiecznie uruchamiać wielokrotnie. Rezerwację najpierw przejmuje się w bazie (claim),
 * dopiero potem wysyła wiadomość, a po błędzie wysyłki przejęcie jest cofane. Dzięki temu:
 *  - drugie uruchomienie w tej samej godzinie nie wyśle nic drugi raz,
 *  - jedna zepsuta wiadomość (np. chwilowa awaria SMTP) nie blokuje pozostałych i zostanie ponowiona przy kolejnym uruchomieniu.
 */
final class TastingReminderJob
{
    public function __construct(
        private readonly ReminderRepositoryInterface $reminders,
        private readonly MailService $mail,
        private readonly ?\Closure $logError = null,
    ) {
    }

    /**
     * @return array{due: int, sent: int, failed: int, skipped: int} due = znalezione, sent = wysłane,
     *         failed = błąd wysyłki (zostaną ponowione), skipped = przejęte przez inny proces lub anulowane w międzyczasie
     */
    public function run(\DateTimeImmutable $now, int $withinHours, bool $dryRun = false): array
    {
        $due = $this->reminders->findDue($now, $withinHours);
        $result = ['due' => count($due), 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        if ($dryRun) {
            return $result;
        }

        foreach ($due as $reminder) {
            if (!$this->reminders->claim($reminder->reservationId, $now)) {
                $result['skipped']++;

                continue;
            }

            try {
                $this->mail->sendTastingReminder($reminder);
                $result['sent']++;
            } catch (MailException $exception) {
                $this->reminders->release($reminder->reservationId);
                $result['failed']++;
                ($this->logError ?? static fn (string $message) => error_log($message))(
                    'Reminder for reservation ' . $reminder->reservationId . ' failed: ' . $exception->getMessage()
                );
            }
        }

        return $result;
    }
}
