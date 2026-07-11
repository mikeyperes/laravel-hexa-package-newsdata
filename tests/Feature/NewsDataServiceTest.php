<?php

namespace Tests\Feature;

use hexa_package_newsdata\Services\NewsDataService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsDataServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireInstalledPackage("hexawebsystems/laravel-hexa-package-newsdata", NewsDataService::class);
    }

    public function test_api_key_probe_uses_provider_endpoint(): void
    {
        Http::fake(["*newsdata.io/api/*" => Http::response(["status" => "success"], 200)]);

        $result = app(NewsDataService::class)->testApiKey("test-key");

        $this->assertTrue($result["success"]);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), "newsdata.io/api/1/latest"));
    }
}
