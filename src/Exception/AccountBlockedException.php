<?php

declare(strict_types=1);

namespace App\Exception;

/** Hasło jest poprawne, ale konto zablokował administrator, więc logowanie jest zamknięte. */
final class AccountBlockedException extends \RuntimeException
{
}
