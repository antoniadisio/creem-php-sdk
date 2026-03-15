<?php

declare(strict_types=1);

namespace Antoniadisio\Creem;

use InvalidArgumentException;
use LogicException;

use function array_keys;
use function count;
use function preg_match;
use function sprintf;
use function trim;

final readonly class CredentialProfiles implements \Stringable
{
    /**
     * @var array<string, CredentialProfile>
     */
    private array $profiles;

    /**
     * @param  array<string, mixed>  $profiles
     */
    public function __construct(array $profiles)
    {
        if ($profiles === []) {
            throw new InvalidArgumentException('At least one Creem credential profile is required.');
        }

        $normalizedProfiles = [];
        $seenNames = [];

        foreach ($profiles as $name => $profile) {
            if (! $profile instanceof CredentialProfile) {
                throw new InvalidArgumentException(sprintf(
                    'Credential profile [%s] must be an instance of Creem\\CredentialProfile.',
                    $name,
                ));
            }

            $normalizedName = $this->normalizeName($name);

            if (isset($seenNames[$normalizedName])) {
                throw new InvalidArgumentException(sprintf(
                    'Duplicate Creem credential profile name [%s].',
                    $normalizedName,
                ));
            }

            $normalizedProfiles[$normalizedName] = $profile;
            $seenNames[$normalizedName] = true;
        }

        $this->profiles = $normalizedProfiles;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        /** @var list<string> $names */
        $names = array_keys($this->profiles);

        return $names;
    }

    public function hasProfile(string $name): bool
    {
        return isset($this->profiles[$this->normalizeName($name)]);
    }

    public function profile(string $name): CredentialProfile
    {
        $normalizedName = $this->normalizeName($name);
        $profile = $this->profiles[$normalizedName] ?? null;

        if (! $profile instanceof CredentialProfile) {
            throw new InvalidArgumentException(sprintf(
                'Unknown Creem credential profile [%s].',
                $normalizedName,
            ));
        }

        return $profile;
    }

    public function config(string $name): Config
    {
        return $this->profile($name)->config();
    }

    public function webhookSecret(string $name): string
    {
        $normalizedName = $this->normalizeName($name);
        $profile = $this->profile($normalizedName);
        $secret = $profile->webhookSecret();

        if ($secret === null) {
            throw new InvalidArgumentException(sprintf(
                'The Creem credential profile [%s] does not define a webhook secret.',
                $normalizedName,
            ));
        }

        return $secret;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return $this->safeRepresentation();
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return $this->safeRepresentation();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        throw new LogicException('Unserializing Creem\\CredentialProfiles is not supported.');
    }

    public function __toString(): string
    {
        return sprintf(
            'Creem\\CredentialProfiles(names=%s)',
            implode(', ', $this->names()),
        );
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Credential profile names cannot be blank.');
        }

        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name)) {
            throw new InvalidArgumentException(
                'Credential profile names must start with an alphanumeric character and contain only letters, numbers, dots, underscores, and dashes.',
            );
        }

        return $name;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeRepresentation(): array
    {
        $profiles = [];

        foreach ($this->profiles as $name => $profile) {
            $profiles[$name] = $profile->__debugInfo();
        }

        return [
            'profiles' => $profiles,
            'count' => count($this->profiles),
            'names' => $this->names(),
        ];
    }
}
