<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Internal\Http\Requests\Discounts\DeleteDiscountRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Products\CreateProductRequest as CreateProductOperation;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\CancelSubscriptionRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\PauseSubscriptionRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\ResumeSubscriptionRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\UpdateSubscriptionRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\UpgradeSubscriptionRequest;
use InvalidArgumentException;

test('mutating requests reject invalid idempotency keys', function (): void {
    expect(static fn (): CreateProductOperation => new CreateProductOperation([], " \r\n "))
        ->toThrow(InvalidArgumentException::class, 'The Creem idempotency key cannot be blank.');
});

test('mutating endpoint requests normalize identifiers before endpoint resolution', function (): void {
    expect(new CancelSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123/cancel')
        ->and(new UpdateSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123')
        ->and(new UpgradeSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123/upgrade')
        ->and(new PauseSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123/pause')
        ->and(new ResumeSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123/resume')
        ->and(new DeleteDiscountRequest('  disc_123  ')->resolveEndpoint())
        ->toBe('/v1/discounts/disc_123/delete');
});

foreach (invalidMutatingPathIdentifiers() as $dataset => [$factory, $message]) {
    test("mutating endpoint requests reject invalid path identifiers ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(): mixed, 1: string}>
 */
function invalidMutatingPathIdentifiers(): array
{
    return [
        'subscription path traversal with slash' => [
            static fn (): CancelSubscriptionRequest => new CancelSubscriptionRequest('sub_123/upgrade'),
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'subscription query injection' => [
            static fn (): PauseSubscriptionRequest => new PauseSubscriptionRequest('sub_123?mode=cancel'),
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'subscription fragment injection' => [
            static fn (): ResumeSubscriptionRequest => new ResumeSubscriptionRequest('sub_123#admin'),
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'subscription percent encoding probe' => [
            static fn (): UpdateSubscriptionRequest => new UpdateSubscriptionRequest('sub%2F123'),
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'subscription unsupported punctuation' => [
            static fn (): UpgradeSubscriptionRequest => new UpgradeSubscriptionRequest('sub:123'),
            'The subscription ID contains unsupported characters. Allowed characters are letters, numbers, ".", "_", and "-".',
        ],
        'discount reserved path separator' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('disc_123/delete'),
            'The discount ID cannot contain reserved URI characters or control characters.',
        ],
        'discount unsupported whitespace' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('disc 123'),
            'The discount ID contains unsupported characters. Allowed characters are letters, numbers, ".", "_", and "-".',
        ],
        'blank subscription id' => [
            static fn (): CancelSubscriptionRequest => new CancelSubscriptionRequest('  '),
            'The subscription ID cannot be blank.',
        ],
        'subscription single dot segment (cancel)' => [
            static fn (): CancelSubscriptionRequest => new CancelSubscriptionRequest('.'),
            'The subscription ID cannot be "." or "..".',
        ],
        'subscription double dot segment (update)' => [
            static fn (): UpdateSubscriptionRequest => new UpdateSubscriptionRequest('..'),
            'The subscription ID cannot be "." or "..".',
        ],
        'subscription single dot segment (upgrade)' => [
            static fn (): UpgradeSubscriptionRequest => new UpgradeSubscriptionRequest('.'),
            'The subscription ID cannot be "." or "..".',
        ],
        'subscription double dot segment (pause)' => [
            static fn (): PauseSubscriptionRequest => new PauseSubscriptionRequest('..'),
            'The subscription ID cannot be "." or "..".',
        ],
        'subscription single dot segment (resume)' => [
            static fn (): ResumeSubscriptionRequest => new ResumeSubscriptionRequest('.'),
            'The subscription ID cannot be "." or "..".',
        ],
        'blank discount id' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('  '),
            'The discount ID cannot be blank.',
        ],
        'discount single dot segment' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('.'),
            'The discount ID cannot be "." or "..".',
        ],
        'discount double dot segment' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('..'),
            'The discount ID cannot be "." or "..".',
        ],
    ];
}
