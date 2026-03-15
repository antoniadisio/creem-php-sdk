<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Support\Contract;

trait InteractsWithContractSupport
{
    private ?CoverageManifest $coverageManifestHelper = null;

    private ?OpenApiSpec $openApiSpecHelper = null;

    private ?ResponseFixtureCatalog $responseFixtureCatalogHelper = null;

    private ?ResponseFixturePolicy $responseFixturePolicyHelper = null;

    public function coverageManifest(): CoverageManifest
    {
        return $this->coverageManifestHelper ??= new CoverageManifest;
    }

    public function openApiSpec(): OpenApiSpec
    {
        return $this->openApiSpecHelper ??= OpenApiSpec::fromFixture();
    }

    public function responseFixtureCatalog(): ResponseFixtureCatalog
    {
        return $this->responseFixtureCatalogHelper ??= new ResponseFixtureCatalog;
    }

    public function responseFixturePolicy(): ResponseFixturePolicy
    {
        return $this->responseFixturePolicyHelper ??= new ResponseFixturePolicy;
    }
}
