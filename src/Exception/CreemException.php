<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Exception;

use RuntimeException;
use Throwable;

class CreemException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'The Creem API request failed.',
        private readonly ?int $statusCode = null,
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
