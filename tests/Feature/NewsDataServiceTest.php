<?php

namespace Tests\Feature;

use hexa_core\Security\Http\OutboundHttpRequest;
use hexa_core\Security\Http\OutboundHttpResponse;
use hexa_core\Security\Http\OutboundUrlGuard;
use hexa_core\Security\Http\SafeOutboundHttpClient;
use hexa_package_newsdata\Services\NewsDataService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsDataServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireInstalledPackage('hexawebsystems/laravel-hexa-package-newsdata', NewsDataService::class);
    }

    public function test_api_key_probe_uses_provider_endpoint(): void
    {
        Http::preventStrayRequests();
        $requests = [];
        $this->app->instance(SafeOutboundHttpClient::class, new SafeOutboundHttpClient(
            new OutboundUrlGuard(static fn (string $host): array => ['93.184.216.34']),
            static function (OutboundHttpRequest $request) use (&$requests): OutboundHttpResponse {
                $requests[] = $request;

                return new OutboundHttpResponse(200, [], '{"status":"success"}');
            },
        ));

        $result = app(NewsDataService::class)->testApiKey('test-key');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $requests);
        $this->assertStringContainsString('newsdata.io/api/1/latest', $requests[0]->target->url);
        Http::assertNothingSent();
    }
}
