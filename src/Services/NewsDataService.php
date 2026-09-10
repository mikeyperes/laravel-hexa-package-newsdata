<?php

namespace hexa_package_newsdata\Services;

use hexa_core\Models\Setting;
use hexa_core\Security\Http\OutboundHttpResponse;
use hexa_core\Security\Http\SafeOutboundHttpClient;
use Illuminate\Support\Facades\Log;

class NewsDataService
{
    public function __construct(private readonly ?SafeOutboundHttpClient $http = null) {}

    private function request(string $endpoint, array $query, int $timeout = 15): OutboundHttpResponse
    {
        return ($this->http ?? app(SafeOutboundHttpClient::class))->request(
            'GET',
            'https://newsdata.io/api/1/'.$endpoint.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            ['timeout' => $timeout, 'max_bytes' => 4 * 1024 * 1024, 'max_redirects' => 0],
        );
    }

    private function getApiKey(): ?string
    {
        return Setting::getValue('newsdata_api_key');
    }

    /**
     * Test the API key.
     *
     * @param  string|null  $apiKey  Override key to test.
     * @return array{success: bool, message: string}
     */
    public function testApiKey(?string $apiKey = null): array
    {
        $key = $apiKey ?? $this->getApiKey();
        if (! $key) {
            return ['success' => false, 'message' => 'No NewsData API key configured.'];
        }

        try {
            $response = $this->request('latest', [
                'apikey' => $key,
                'language' => 'en',
                'size' => 1,
            ], 10);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    return ['success' => true, 'message' => 'NewsData API key is valid.'];
                }

                return ['success' => false, 'message' => 'NewsData returned unexpected response.'];
            }
            if ($response->status === 401) {
                return ['success' => false, 'message' => 'Invalid API key.'];
            }

            return ['success' => false, 'message' => "NewsData returned HTTP {$response->status}."];
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'NewsData could not be reached securely.'];
        }
    }

    /**
     * Search for articles.
     *
     * @param  int  $size  Results per request (max 50).
     * @param  string  $language  Language code.
     * @param  string|null  $country  Comma separated ISO country codes. Falls back
     *                                to the configured default. Without one the
     *                                API returns worldwide results, which buries
     *                                a country-focused publication in coverage
     *                                from unrelated markets.
     * @return array{success: bool, message: string, data: array|null}
     */
    public function searchArticles(string $query, int $size = 10, string $language = 'en', ?string $country = null): array
    {
        $key = $this->getApiKey();
        if (! $key) {
            return ['success' => false, 'message' => 'No NewsData API key configured.', 'data' => null];
        }

        $country = trim((string) ($country ?? config('newsdata.default_country', '')));

        try {
            $params = [
                'apikey' => $key,
                'q' => $query,
                'language' => $language,
                'size' => max(1, min($size, 50)),
            ];
            if ($country !== '') {
                $params['country'] = $country;
            }

            $response = $this->request('news', $params);

            if ($response->successful()) {
                $data = $response->json();
                if (! is_array($data) || ($data['status'] ?? '') !== 'success' || ! is_array($data['results'] ?? null)) {
                    return ['success' => false, 'message' => 'NewsData returned an invalid article response.', 'data' => null];
                }
                $articles = collect($data['results'])->filter(static fn ($article): bool => is_array($article))->take(max(1, min($size, 50)))->map(fn ($a) => [
                    'source_api' => 'newsdata',
                    'title' => $a['title'] ?? '',
                    'description' => $a['description'] ?? '',
                    'content' => $a['content'] ?? '',
                    'url' => $a['link'] ?? '',
                    'image' => $a['image_url'] ?? null,
                    'published_at' => $a['pubDate'] ?? null,
                    'source_name' => $a['source_name'] ?? $a['source_id'] ?? '',
                    'source_url' => $a['source_url'] ?? '',
                    'author' => is_array($a['creator'] ?? null) ? implode(', ', $a['creator']) : ($a['creator'] ?? null),
                    'categories' => $a['category'] ?? [],
                    'keywords' => $a['keywords'] ?? [],
                    'language' => $a['language'] ?? null,
                    'country' => is_array($a['country'] ?? null) ? implode(', ', $a['country']) : ($a['country'] ?? null),
                ])->values()->toArray();

                return [
                    'success' => true,
                    'message' => count($articles).' articles found.',
                    'data' => ['articles' => $articles, 'total' => $data['totalResults'] ?? count($articles)],
                ];
            }

            return ['success' => false, 'message' => "NewsData returned HTTP {$response->status}.", 'data' => null];
        } catch (\Throwable) {
            Log::warning('NewsData article request failed securely.');

            return ['success' => false, 'message' => 'NewsData could not be reached securely.', 'data' => null];
        }
    }
}
