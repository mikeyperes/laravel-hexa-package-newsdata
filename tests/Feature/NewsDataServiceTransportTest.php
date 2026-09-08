<?php

namespace Tests\Feature;

use hexa_core\Security\Http\OutboundHttpRequest;
use hexa_core\Security\Http\OutboundHttpResponse;
use hexa_core\Security\Http\OutboundUrlGuard;
use hexa_core\Security\Http\SafeOutboundHttpClient;
use hexa_package_newsdata\Services\NewsDataService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class NewsDataServiceTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->requireInstalledPackage('hexawebsystems/laravel-hexa-package-newsdata', NewsDataService::class);
        Http::preventStrayRequests();
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
        });
        DB::table('settings')->insert(['key' => 'newsdata_api_key', 'value' => 'fixture-secret']);
    }

    public function test_probe_is_pinned_bounded_and_preserves_its_public_contract(): void
    {
        $requests = [];
        $service = $this->service($requests, $this->response(['status' => 'success']));
        $this->assertTrue($service->testApiKey('fixture-secret')['success']);
        $this->assertCount(1, $requests);
        $this->assertStringStartsWith('https://newsdata.io/api/1/', $requests[0]->target->url);
        $this->assertSame(10, $requests[0]->timeoutSeconds);
        $this->assertSame(4 * 1024 * 1024, $requests[0]->maxResponseBytes);
        Http::assertNothingSent();
    }

    public function test_search_preserves_fields_and_provider_query_mapping(): void
    {
        $requests = [];
        $service = $this->service($requests, $this->response(['status' => 'success', 'results' => [['title' => 'Fixture article', 'link' => 'https://news.example.test/a', 'creator' => ['Author A', 'Author B']]], 'totalResults' => 7]));
        $result = $service->searchArticles('fixture query', 0);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']['articles']);
        $this->assertSame('Fixture article', $result['data']['articles'][0]['title']);
        $this->assertSame('newsdata', $result['data']['articles'][0]['source_api']);
        $this->assertArrayHasKey('image', $result['data']['articles'][0]);
        parse_str((string) parse_url($requests[0]->target->url, PHP_URL_QUERY), $query);
        $this->assertSame('fixture-secret', $query['apikey']);
        $this->assertSame('1', $query['size']);
        $this->assertSame(15, $requests[0]->timeoutSeconds);
        Http::assertNothingSent();
    }

    public function test_missing_key_makes_no_network_request(): void
    {
        DB::table('settings')->delete();
        $requests = [];
        $service = $this->service($requests, $this->response(['status' => 'success', 'results' => [['title' => 'Fixture article', 'link' => 'https://news.example.test/a', 'creator' => ['Author A', 'Author B']]], 'totalResults' => 7]));
        $this->assertFalse($service->testApiKey()['success']);
        $this->assertFalse($service->searchArticles('fixture query', 0)['success']);
        $this->assertSame([], $requests);
    }

    public function test_redirects_do_not_forward_credentials_or_expose_provider_detail(): void
    {
        $requests = [];
        $service = $this->service($requests, new OutboundHttpResponse(302, [
            'Location' => 'https://redirect.example.test/?key=fixture-secret',
        ], 'private-provider-detail fixture-secret'));
        $result = [$service->testApiKey('fixture-secret'), $service->searchArticles('fixture query', 0)];
        $this->assertFalse($result[0]['success']);
        $this->assertFalse($result[1]['success']);
        $this->assertCount(2, $requests);
        $this->assertStringNotContainsString('fixture-secret', json_encode($result));
        $this->assertStringNotContainsString('private-provider-detail', json_encode($result));
    }

    public function test_malformed_and_oversized_responses_fail_without_false_success(): void
    {
        foreach (['not-json', '{"unexpected":true}', str_repeat('x', (4 * 1024 * 1024) + 1)] as $body) {
            $requests = [];
            $service = $this->service($requests, new OutboundHttpResponse(200, [], $body));
            $this->assertFalse($service->searchArticles('fixture query', 0)['success']);
        }
    }

    public function test_private_dns_and_transport_errors_fail_without_leaking_keys(): void
    {
        foreach ([['127.0.0.1'], ['93.184.216.34']] as $addresses) {
            $requests = [];
            $service = $this->service($requests, new \RuntimeException('fixture-secret private-provider-detail'), $addresses);
            $result = [$service->testApiKey('fixture-secret'), $service->searchArticles('fixture query', 0)];
            $this->assertFalse($result[0]['success']);
            $this->assertFalse($result[1]['success']);
            $this->assertStringNotContainsString('fixture-secret', json_encode($result));
            $this->assertStringNotContainsString('private-provider-detail', json_encode($result));
            if ($addresses === ['127.0.0.1']) {
                $this->assertSame([], $requests);
            }
        }
    }

    private function response(array $payload): OutboundHttpResponse
    {
        return new OutboundHttpResponse(200, [], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function service(array &$requests, OutboundHttpResponse|\Throwable $response, array $addresses = ['93.184.216.34']): NewsDataService
    {
        return new NewsDataService(new SafeOutboundHttpClient(
            new OutboundUrlGuard(static fn (string $host): array => $addresses),
            static function (OutboundHttpRequest $request) use (&$requests, $response): OutboundHttpResponse {
                $requests[] = $request;
                if ($response instanceof \Throwable) {
                    throw $response;
                }

                return $response;
            },
        ));
    }
}
