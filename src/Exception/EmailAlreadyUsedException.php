<?php

declare(strict_types=1);

namespace App\Exception;

/** Konto z takim adresem e-mail już istnieje (naruszenie unikalności users_email_key). */
final class EmailAlreadyUsedException extends \DomainException
{
}
