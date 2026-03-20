@extends('layouts.app')

@section('title', 'NewsData Raw — ' . config('hws.app_name'))
@section('header', 'NewsData — Raw Functions')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Package Functions Index --}}
    <div class="bg-gray-900 rounded-xl p-6 text-sm font-mono">
        <h2 class="text-white font-semibold mb-3">NewsData Functions</h2>
        @php $apiKey = \hexa_core\Models\Setting::getValue('newsdata_api_key', ''); @endphp
        <div class="flex items-center gap-2 mb-3">
            <span class="w-2 h-2 rounded-full {{ $apiKey ? 'bg-green-400' : 'bg-yellow-400' }}"></span>
            <span class="text-sm {{ $apiKey ? 'text-green-400' : 'text-yellow-400' }}">{{ $apiKey ? 'API Key Configured' : 'No API Key' }}</span>
        </div>
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
            <button id="btn-newsdata-search" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 inline-flex items-center gap-2">
                <svg id="spinner-newsdata" class="hidden animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span id="btn-text-newsdata">Search</span>
            </button>
        </div>

        <div id="newsdata-search-result" class="mt-4"></div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    document.getElementById('btn-newsdata-search').addEventListener('click', function() {
        const btn = this;
        const spinner = document.getElementById('spinner-newsdata');
        const btnText = document.getElementById('btn-text-newsdata');
        const query = document.getElementById('newsdata-query').value.trim();
        const resultDiv = document.getElementById('newsdata-search-result');

        if (!query) { resultDiv.innerHTML = '<div class="p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800">Enter a search query.</div>'; return; }

        btn.disabled = true; spinner.classList.remove('hidden'); btnText.textContent = 'Searching...';

        fetch('{{ route("newsdata.search") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ query: query, size: document.getElementById('newsdata-size').value, language: document.getElementById('newsdata-lang').value }),
        })
        .then(r => r.json())
        .then(data => {
            let html = '';
            if (data.success && data.data && data.data.articles) {
                html += '<div class="p-3 rounded-lg text-sm bg-green-50 border border-green-200 text-green-800 mb-3">' + esc(data.message) + ' (Total: ' + data.data.total + ')</div>';
                data.data.articles.forEach(function(a) {
                    html += '<div class="p-4 border border-gray-200 rounded-lg mb-2">';
                    html += '<h3 class="font-semibold text-sm text-gray-900 break-words">' + esc(a.title || 'No title') + '</h3>';
                    html += '<p class="text-xs text-gray-500 mt-1 break-words">' + esc(a.description || '') + '</p>';
                    html += '<div class="mt-2 text-xs text-gray-400"><span>' + esc(a.source_name || '') + '</span> &middot; <span>' + esc(a.published_at || '') + '</span>';
                    if (a.url) html += ' &middot; <a href="' + esc(a.url) + '" target="_blank" class="text-blue-500 hover:underline">View &#8599;</a>';
                    html += '</div></div>';
                });
            } else { html = '<div class="p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800">' + esc(data.message || 'Error') + '</div>'; }
            resultDiv.innerHTML = html;
        })
        .catch(err => { resultDiv.innerHTML = '<div class="p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800">Request failed: ' + esc(err.message) + '</div>'; })
        .finally(() => { btn.disabled = false; spinner.classList.add('hidden'); btnText.textContent = 'Search'; });
    });

    function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
});
</script>
@endpush
@endsection
