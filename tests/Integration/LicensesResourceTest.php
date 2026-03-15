<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Integration;

use Antoniadisio\Creem\Dto\License\ActivateLicenseRequest;
use Antoniadisio\Creem\Dto\License\DeactivateLicenseRequest;
use Antoniadisio\Creem\Dto\License\LicenseInstance;
use Antoniadisio\Creem\Dto\License\ValidateLicenseRequest;
use Antoniadisio\Creem\Enum\LicenseInstanceStatus;
use Antoniadisio\Creem\Enum\LicenseStatus;
use Antoniadisio\Creem\Resource\LicensesResource;
use Antoniadisio\Creem\Tests\IntegrationTestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('licenses resource activates deactivates and validates licenses', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('license.json')),
        MockResponse::make($this->responseFixture('license.json', [
            'status' => 'inactive',
            'activation' => 0,
            'instance' => [
                'id' => 'lki_fixture_active',
                'mode' => 'test',
                'object' => 'license-instance',
                'name' => 'fixture-workstation-primary',
                'status' => 'deactivated',
                'created_at' => '2026-03-10T08:43:11.285Z',
            ],
        ])),
        MockResponse::make($this->responseFixture('license.json', ['activation' => 1])),
    ]);
    $resource = new LicensesResource($this->connector($mockClient));

    $activated = $resource->activate(new ActivateLicenseRequest('LICENSE-FIXTURE-PRIMARY', 'fixture-workstation-primary'), 'idem-license-activate');

    expect($activated->id)->toBe('lk_fixture_primary')
        ->and($activated->status)->toBe(LicenseStatus::Active)
        ->and($activated->instance)->toBeInstanceOf(LicenseInstance::class)
        ->and($activated->instance?->id)->toBe('lki_fixture_active')
        ->and($activated->expiresAt)->toBeNull();
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/activate',
        [],
        ['key' => 'LICENSE-FIXTURE-PRIMARY', 'instance_name' => 'fixture-workstation-primary'],
        ['Idempotency-Key' => 'idem-license-activate'],
    );

    $deactivated = $resource->deactivate(new DeactivateLicenseRequest('LICENSE-FIXTURE-PRIMARY', 'lki_fixture_active'), 'idem-license-deactivate');

    expect($deactivated->status)->toBe(LicenseStatus::Inactive)
        ->and($deactivated->activation)->toBe(0)
        ->and($deactivated->instance?->status)->toBe(LicenseInstanceStatus::Deactivated);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/deactivate',
        [],
        ['key' => 'LICENSE-FIXTURE-PRIMARY', 'instance_id' => 'lki_fixture_active'],
        ['Idempotency-Key' => 'idem-license-deactivate'],
    );

    $validated = $resource->validate(new ValidateLicenseRequest('LICENSE-FIXTURE-PRIMARY', 'lki_fixture_active'), 'idem-license-validate');

    expect($validated->activation)->toBe(1);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/validate',
        [],
        ['key' => 'LICENSE-FIXTURE-PRIMARY', 'instance_id' => 'lki_fixture_active'],
        ['Idempotency-Key' => 'idem-license-validate'],
    );
});
