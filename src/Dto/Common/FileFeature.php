<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Internal\Hydration\Payload;

use function array_is_list;
use function is_array;

final readonly class FileFeature
{
    /**
     * @param  list<FeatureFile>  $files
     */
    public function __construct(
        public array $files,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::typedList(
                $payload,
                'files',
                self::class,
                static function (mixed $item): FeatureFile {
                    if (! is_array($item) || array_is_list($item)) {
                        throw HydrationException::invalidField(self::class, 'files', 'object', $item);
                    }

                    /** @var array<string, mixed> $item */
                    return FeatureFile::fromPayload($item);
                },
                true,
            ),
        );
    }
}
