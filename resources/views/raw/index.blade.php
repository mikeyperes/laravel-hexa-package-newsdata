@extends('layouts.app')

@section('title', 'NewsData Raw — ' . config('hws.app_name'))
@section('header', 'NewsData — Raw Functions')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Package Functions Index --}}
    <div class="bg-gray-900 rounded-xl p-6 text-sm font-mono">
        <h2 class="text-white font-semibold mb-3">NewsData Functions</h2>
        <table class="w-full text-left">
            <thead>
                <tr class="text-gray-400 border-b border-gray-700">
                    <th class="py-1.5 px-2">Function</th>
                    <th class="py-1.5 px-2">Method</th>
                    <th class="py-1.5 px-2">Route</th>
                    <th class="py-1.5 px-2">Status</th>
                </tr>
            </thead>
            <tbody class="text-gray-300">
                <tr class="border-b border-gray-800">
                    <td class="py-1.5 px-2">Test API key validity</td>
                    <td class="py-1.5 px-2 text-blue-400">testApiKey()</td>
                    <td class="py-1.5 px-2 text-green-400">POST /settings/newsdata/test</td>
                    <td class="py-1.5 px-2 text-green-400">LIVE</td>
                </tr>
                <tr class="border-b border-gray-800">
                    <td class="py-1.5 px-2">Search articles by keyword</td>
                    <td class="py-1.5 px-2 text-blue-400">searchArticles()</td>
                    <td class="py-1.5 px-2 text-green-400">POST /newsdata/search</td>
                    <td class="py-1.5 px-2 text-green-400">LIVE</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Search Articles Test --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Search Articles</h2>

        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Query</label>
                <input type="text" id="newsdata-query" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="e.g. technology, bitcoin, climate change">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Results Count</label>
                    <input type="number" id="newsdata-size" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="5" min="1" max="50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                    <input type="text" id="newsdata-lang" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="en" placeholder="en">
                </div>
            </div>
            <button id="btn-newsdata-search" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                Search
            </button>
        </div>

        <div id="newsdata-search-result" class="mt-4"></div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#btn-newsdata-search').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).html('<svg class="animate-spin h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Searching...');

        $.ajax({
            url: '{{ route("newsdata.search") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                query: $('#newsdata-query').val(),
                size: $('#newsdata-size').val(),
                language: $('#newsdata-lang').val()
            },
            success: function(data) {
                var html = '';
                if (data.success && data.data && data.data.articles) {
                    html += '<div class="p-3 rounded-lg text-sm bg-green-50 border border-green-200 text-green-800 mb-3">' + data.message + ' (Total: ' + data.data.total + ')</div>';
                    data.data.articles.forEach(function(article) {
                        html += '<div class="p-4 border border-gray-200 rounded-lg mb-2">';
                        html += '<h3 class="font-semibold text-sm text-gray-900 break-words">' + (article.title || 'No title') + '</h3>';
                        html += '<p class="text-xs text-gray-500 mt-1 break-words">' + (article.description || '') + '</p>';
                        html += '<div class="mt-2 text-xs text-gray-400">';
                        html += '<span>' + (article.source_name || '') + '</span>';
                        html += ' &middot; <span>' + (article.published_at || '') + '</span>';
                        if (article.url) {
                            html += ' &middot; <a href="' + article.url + '" target="_blank" class="text-blue-500 hover:underline">View <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>';
                        }
                        html += '</div></div>';
                    });
                } else {
                    html = '<div class="p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800">' + (data.message || 'Error') + '</div>';
                }
                $('#newsdata-search-result').html(html);
            },
            error: function() {
                $('#newsdata-search-result').html('<div class="p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800">Request failed.</div>');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>
@endpush
@endsection
