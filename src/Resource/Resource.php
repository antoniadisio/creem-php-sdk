<?php

declare(strict_types=1);

namespace Creem\Resource;

use Creem\Internal\Http\CreemConnector;
use Creem\Internal\Http\ResponseDecoder;
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
