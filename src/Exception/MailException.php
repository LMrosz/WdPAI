<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Nie udało się wysłać wiadomości (brak połączenia z SMTP, odrzucony adres, brak biblioteki).
 * Komunikat jest techniczny i trafia tylko do logu; użytkownik widzi krótki, ogólny tekst.
 */
final class MailException extends \RuntimeException
{
}
