<?php

declare(strict_types=1);

namespace Antoniadisio\Creem;

use function trim;

final class ClientFactory
{
    /**
     * @var array<string, Client>
     */
    private array $clients = [];

    public function __construct(
        private readonly CredentialProfiles $profiles,
    ) {}

    public function profiles(): CredentialProfiles
    {
        return $this->profiles;
    }

    public function forProfile(string $name): Client
    {
        $cacheKey = trim($name);

        return $this->clients[$cacheKey] ??= new Client($this->profiles->config($name));
    }
}
