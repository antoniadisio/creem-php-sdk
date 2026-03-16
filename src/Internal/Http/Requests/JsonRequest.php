<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/** @internal */
abstract class JsonRequest extends Request implements HasBody
{
    use HasJsonBody;

    private readonly ?string $idempotencyKey;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $payload = [],
        ?string $idempotencyKey = null,
    ) {
        $this->idempotencyKey = IdempotencyKey::normalize($idempotencyKey);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->payload;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return IdempotencyKey::header($this->idempotencyKey);
    }
}
