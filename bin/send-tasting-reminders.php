<?php

declare(strict_types=1);

/**
 * Zadanie CRON (Lab 13): przypomnienie e-mailem o degustacji, która zaczyna się w ciągu najbliższych godzin.
 *
 *   php bin/send-tasting-reminders.php               wysyła przypomnienia (domyślnie dla degustacji w ciągu 24 h)
 *   php bin/send-tasting-reminders.php --within=48   okno 48 godzin
 *   php bin/send-tasting-reminders.php --dry-run     tylko liczy, ile przypomnień by wysłano, niczego nie zmienia
 *
 * W Dockerze uruchamia je usługa cron co godzinę (docker/cron/crontab). Wynik to jedna linia z czasem
 * i liczbami, którą widać w logu: docker compose logs cron. Kod wyjścia 0 = zadanie się wykonało,
 * 1 = któraś wiadomość się nie wysłała (zostanie ponowiona), 2 = błędne argumenty lub brak połączenia z bazą.
 */

use App\Database;
use App\Repository\PostgresReminderRepository;
use App\Service\MailService;
use App\Service\TastingReminderJob;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$options = getopt('', ['within::', 'dry-run', 'help']);

if (isset($options['help'])) {
    fwrite(STDOUT, "Użycie: php bin/send-tasting-reminders.php [--within=GODZINY] [--dry-run]\n");
    exit(0);
}

$within = 24;

if (isset($options['within'])) {
    $value = $options['within'];

    if (!is_string($value) || !ctype_digit($value) || (int) $value < 1 || (int) $value > 168) {
        fwrite(STDERR, "--within musi być liczbą godzin od 1 do 168.\n");
        exit(2);
    }

    $within = (int) $value;
}

$dryRun = isset($options['dry-run']);
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

try {
    $job = new TastingReminderJob(new PostgresReminderRepository(Database::shared()), MailService::create());
    $result = $job->run($now, $within, $dryRun);
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf("[%s] reminders: error: %s\n", $now->format('Y-m-d H:i:s \U\T\C'), $exception->getMessage()));
    exit(2);
}

echo sprintf(
    "[%s] reminders%s: window=%dh due=%d sent=%d failed=%d skipped=%d\n",
    $now->format('Y-m-d H:i:s \U\T\C'),
    $dryRun ? ' (dry-run)' : '',
    $within,
    $result['due'],
    $result['sent'],
    $result['failed'],
    $result['skipped'],
);

exit($result['failed'] > 0 ? 1 : 0);
