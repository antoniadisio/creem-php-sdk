<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Exception;

use function is_array;

final class ValidationException extends CreemException
{
    /**
     * @return array<array-key, mixed>
     */
    public function errors(): array
    {
        $errors = $this->context()['errors'] ?? [];

        return is_array($errors) ? $errors : [];
    }
}
