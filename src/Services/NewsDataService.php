<?php

namespace hexa_package_newsdata\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use hexa_core\Models\Setting;

class NewsDataService
{
    /**
     * @return string|null
     */
    private function getApiKey(): ?string
    {
        return Setting::getValue('newsdata_api_key');
    }

    /**
     * Test the API key.
     *
     * @param string|null $apiKey Override key to test.
     * @return array{success: bool, message: string}
     */
    public function testApiKey(?string $apiKey = null): array
    {
        $key = $apiKey ?? $this->getApiKey();
        if (!$key) {
            return ['success' => false, 'message' => 'No NewsData API key configured.'];
        }

        try {
            $response = Http::timeout(10)
                ->get('https://newsdata.io/api/1/latest', [
                    'apikey' => $key,
                    'language' => 'en',
                    'size' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    return ['success' => true, 'message' => 'NewsData API key is valid.'];
                }
                return ['success' => false, 'message' => 'NewsData returned unexpected response.'];
            }
            if ($response->status() === 401) {
                return ['success' => false, 'message' => 'Invalid API key.'];
            }
            return ['success' => false, 'message' => "NewsData returned HTTP {$response->status()}."];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Search for articles.
     *
     * @param string $query
     * @param int $size Results per request (max 50).
     * @param string $language Language code.
     * @return array{success: bool, message: string, data: array|null}
     */
    public function searchArticles(string $query, int $size = 10, string $language = 'en'): array
    {
        $key = $this->getApiKey();
        if (!$key) {
            return ['success' => false, 'message' => 'No NewsData API key configured.', 'data' => null];
        }

        try {
            $response = Http::timeout(15)
                ->get('https://newsdata.io/api/1/news', [
                    'apikey' => $key,
                    'q' => $query,
                    'language' => $language,
                    'size' => min($size, 50),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $articles = collect($data['results'] ?? [])->map(fn($a) => [
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
                ])->toArray();

                return [
                    'success' => true,
                    'message' => count($articles) . ' articles found.',
                    'data' => ['articles' => $articles, 'total' => $data['totalResults'] ?? count($articles)],
                ];
            }

            return ['success' => false, 'message' => "NewsData returned HTTP {$response->status()}.", 'data' => null];
        } catch (\Exception $e) {
            Log::error('NewsDataService::searchArticles error', ['query' => $query, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => null];
        }
    }
}
