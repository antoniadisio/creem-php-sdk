<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Resource;

use Antoniadisio\Creem\Internal\Http\CreemConnector;
use Antoniadisio\Creem\Internal\Http\ResponseDecoder;
use Saloon\Http\Request;

abstract class Resource
{
    public function __construct(
        protected readonly CreemConnector $connector,
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function send(Request $request): array
    {
        return ResponseDecoder::decode($this->connector->send($request));
    }
}
