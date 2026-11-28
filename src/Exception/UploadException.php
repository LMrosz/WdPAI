<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Plik z formularza nie spełnia wymagań. $reason to kod z zamkniętej listy
 * (too_large, invalid_type, invalid_extension, invalid_image, upload_failed), z którego kontroler
 * wybiera komunikat po polsku. Treść wyjątku jest techniczna i nie trafia do użytkownika.
 */
final class UploadException extends \RuntimeException
{
    public function __construct(public readonly string $reason, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $reason);
    }
}
