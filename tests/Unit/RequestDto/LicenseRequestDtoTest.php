<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\License\ActivateLicenseRequest;
use Antoniadisio\Creem\Dto\License\DeactivateLicenseRequest;
use Antoniadisio\Creem\Dto\License\ValidateLicenseRequest;
use InvalidArgumentException;

test('license request dtos serialize activation deactivation and validation payloads', function (): void {
    expect(new ActivateLicenseRequest('lic_key', 'macbook')->toArray())->toBe([
        'key' => 'lic_key',
        'instance_name' => 'macbook',
    ])
        ->and(new DeactivateLicenseRequest('lic_key', 'ins_123')->toArray())->toBe([
            'key' => 'lic_key',
            'instance_id' => 'ins_123',
        ])
        ->and(new ValidateLicenseRequest('lic_key', 'ins_456')->toArray())->toBe([
            'key' => 'lic_key',
            'instance_id' => 'ins_456',
        ]);
});

foreach (invalidLicenseRequestInputs() as $dataset => [$factory, $message]) {
    test("license request dtos reject invalid input ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(): mixed, 1: string}>
 */
function invalidLicenseRequestInputs(): array
{
    return [
        'blank license key' => [
            static fn (): ActivateLicenseRequest => new ActivateLicenseRequest(' ', 'prod-web-1'),
            'The license key cannot be blank.',
        ],
        'blank deactivate instance id' => [
            static fn (): DeactivateLicenseRequest => new DeactivateLicenseRequest('lic_key', ' '),
            'The license instance ID cannot be blank.',
        ],
        'blank validate instance id' => [
            static fn (): ValidateLicenseRequest => new ValidateLicenseRequest('lic_key', ' '),
            'The license instance ID cannot be blank.',
        ],
    ];
}
