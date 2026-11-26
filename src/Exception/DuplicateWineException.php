<?php

declare(strict_types=1);

namespace App\Exception;

/** Wino o tej nazwie i tym roczniku już istnieje (naruszenie unikalności wines_name_vintage_key). */
final class DuplicateWineException extends \DomainException
{
}
