<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Exception;

use Throwable;

final class RateLimitException extends CreemException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'The Creem API request failed.',
        ?int $statusCode = null,
        array $context = [],
        ?Throwable $previous = null,
        private readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message, $statusCode, $context, $previous);
    }

    public function retryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
