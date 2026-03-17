<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Support;

use Antoniadisio\Creem\Config;
use Antoniadisio\Creem\Exception\CreemException;
use Antoniadisio\Creem\Internal\Http\CreemConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Http\Response;

final class HttpTestSupport
{
    public static function pingRequest(): Request
    {
        return new class extends Request {
            protected Method $method = Method::GET;

            public function resolveEndpoint(): string
            {
                return '/v1/ping';
            }
        };
    }

    public static function successResponse(MockResponse $mockResponse): Response
    {
        $connector = new CreemConnector(new Config('creem_test_123', \Antoniadisio\Creem\Enum\Environment::Test));

        return $connector->send(self::pingRequest(), new MockClient([$mockResponse]));
    }

    public static function captureException(callable $callback): ?CreemException
    {
        try {
            $callback();
        } catch (CreemException $exception) {
            return $exception;
        }

        return null;
    }
}
