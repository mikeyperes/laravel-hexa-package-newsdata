<?php

namespace hexa_package_newsdata\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use hexa_package_newsdata\Services\NewsDataService;
use hexa_core\Models\Setting;

/**
 * NewsDataController — handles settings, raw view, and API test endpoints.
 */
class NewsDataController extends Controller
{
    /**
     * Show the raw development/test page.
     *
     * @return \Illuminate\View\View
     */
    public function raw()
    {
        return view('newsdata::raw.index');
    }

    /**
     * Show the NewsData settings page.
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        return view('newsdata::settings.index', [
            'apiKey' => Setting::getValue('newsdata_api_key', ''),
        ]);
    }

    /**
     * Save the NewsData API key.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSettings(Request $request)
    {
        $request->validate(['api_key' => 'required|string']);

        Setting::setValue('newsdata_api_key', $request->input('api_key'));

        return response()->json([
            'success' => true,
            'message' => 'NewsData API key saved successfully.',
            'api_key' => $request->input('api_key'),
        ]);
    }

    /**
     * Test the NewsData API key.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testApiKey(Request $request)
    {
        $service = app(NewsDataService::class);
        $apiKey = $request->input('api_key') ?: null;
        $result = $service->testApiKey($apiKey);

        return response()->json($result);
    }

    /**
     * Search articles via NewsData API (for raw page testing).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchArticles(Request $request)
    {
        $request->validate(['query' => 'required|string']);

        $service = app(NewsDataService::class);
        $result = $service->searchArticles(
            $request->input('query'),
            $request->input('size', 10),
            $request->input('language', 'en')
        );

        return response()->json($result);
    }
}
