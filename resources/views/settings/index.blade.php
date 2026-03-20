@extends('layouts.app')

@section('title', 'NewsData Settings — ' . config('hws.app_name'))
@section('header', 'NewsData Settings')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('settings.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Settings</a>
    </div>

    {{-- Install Instructions --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6">
        <h2 class="text-lg font-semibold text-blue-900 mb-2">Setup Instructions</h2>
        <ol class="list-decimal list-inside text-sm text-blue-800 space-y-1">
            <li>Go to <a href="https://newsdata.io" target="_blank" class="underline font-medium">newsdata.io</a> <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></li>
            <li>Create a free account (200 credits/day on free tier)</li>
            <li>Go to API Keys in your dashboard</li>
            <li>Copy your API key</li>
            <li>Paste the key below and click Save</li>
            <li>Click Test to verify the key works</li>
        </ol>
    </div>

    {{-- API Key --}}
    @php $apiKey = \hexa_core\Models\Setting::getValue('newsdata_api_key', ''); @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">API Key</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NewsData API Key</label>

                <div id="newsdata-masked" class="{{ $apiKey ? '' : 'hidden' }}">
                    <input type="password" value="{{ $apiKey }}" disabled
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500 mb-2">
                </div>
                <div id="newsdata-no-key" class="{{ $apiKey ? 'hidden' : '' }}">
                    <p class="text-xs text-gray-400 italic mb-2">No API key configured.</p>
                </div>
                <div id="newsdata-edit" class="hidden">
                    <input type="text" id="newsdata-api-key-input"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono mb-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        placeholder="Paste your NewsData.io API key">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button id="btn-newsdata-change" class="{{ $apiKey ? '' : 'hidden' }} px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Change API Key</button>
                <button id="btn-newsdata-set" class="{{ $apiKey ? 'hidden' : '' }} px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Set API Key</button>
                <button id="btn-newsdata-save" class="hidden px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 inline-flex items-center gap-2">
                    <svg id="spinner-newsdata-save" class="hidden animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span id="btn-text-newsdata-save">Save</span>
                </button>
                <button id="btn-newsdata-cancel" class="hidden text-sm text-gray-500 hover:text-gray-700 px-4 py-2">Cancel</button>
                <button id="btn-newsdata-test" class="{{ $apiKey ? '' : 'hidden' }} px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 inline-flex items-center gap-2">
                    <svg id="spinner-newsdata-test" class="hidden animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span id="btn-text-newsdata-test">Test Key</span>
                </button>
            </div>

            <div id="newsdata-result" class="hidden"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    let hasKey = {{ $apiKey ? 'true' : 'false' }};

    const masked = document.getElementById('newsdata-masked');
    const noKey = document.getElementById('newsdata-no-key');
    const editDiv = document.getElementById('newsdata-edit');
    const input = document.getElementById('newsdata-api-key-input');
    const result = document.getElementById('newsdata-result');

    const btnChange = document.getElementById('btn-newsdata-change');
    const btnSet = document.getElementById('btn-newsdata-set');
    const btnSave = document.getElementById('btn-newsdata-save');
    const btnCancel = document.getElementById('btn-newsdata-cancel');
    const btnTest = document.getElementById('btn-newsdata-test');

    function showEditMode() {
        masked.classList.add('hidden');
        noKey.classList.add('hidden');
        editDiv.classList.remove('hidden');
        btnChange.classList.add('hidden');
        btnSet.classList.add('hidden');
        btnSave.classList.remove('hidden');
        btnCancel.classList.remove('hidden');
        btnTest.classList.add('hidden');
        input.focus();
    }

    function showViewMode() {
        editDiv.classList.add('hidden');
        btnSave.classList.add('hidden');
        btnCancel.classList.add('hidden');
        input.value = '';
        if (hasKey) {
            masked.classList.remove('hidden');
            noKey.classList.add('hidden');
            btnChange.classList.remove('hidden');
            btnSet.classList.add('hidden');
            btnTest.classList.remove('hidden');
        } else {
            masked.classList.add('hidden');
            noKey.classList.remove('hidden');
            btnChange.classList.add('hidden');
            btnSet.classList.remove('hidden');
            btnTest.classList.add('hidden');
        }
    }

    function showResult(success, message) {
        result.classList.remove('hidden');
        result.innerHTML = '<div class="p-3 rounded-lg text-sm ' +
            (success ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800') + '">' +
            (success ? '<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' : '') +
            message + '</div>';
    }

    btnChange.addEventListener('click', showEditMode);
    btnSet.addEventListener('click', showEditMode);
    btnCancel.addEventListener('click', function() { showViewMode(); result.classList.add('hidden'); });

    btnSave.addEventListener('click', function() {
        const key = input.value.trim();
        if (!key) { showResult(false, 'Please enter an API key.'); return; }
        btnSave.disabled = true;
        document.getElementById('spinner-newsdata-save').classList.remove('hidden');
        document.getElementById('btn-text-newsdata-save').textContent = 'Saving...';

        fetch('{{ route("settings.newsdata.save") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ api_key: key }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                hasKey = true;
                masked.querySelector('input').value = data.api_key || key;
                showViewMode();
                showResult(true, data.message || 'API key saved.');
            } else { showResult(false, data.message || 'Failed to save.'); }
        })
        .catch(err => showResult(false, 'Request failed: ' + err.message))
        .finally(() => {
            btnSave.disabled = false;
            document.getElementById('spinner-newsdata-save').classList.add('hidden');
            document.getElementById('btn-text-newsdata-save').textContent = 'Save';
        });
    });

    btnTest.addEventListener('click', function() {
        btnTest.disabled = true;
        document.getElementById('spinner-newsdata-test').classList.remove('hidden');
        document.getElementById('btn-text-newsdata-test').textContent = 'Testing...';

        fetch('{{ route("settings.newsdata.test") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({}),
        })
        .then(r => r.json())
        .then(data => showResult(data.success, data.message || (data.success ? 'Key is valid.' : 'Key test failed.')))
        .catch(err => showResult(false, 'Request failed: ' + err.message))
        .finally(() => {
            btnTest.disabled = false;
            document.getElementById('spinner-newsdata-test').classList.add('hidden');
            document.getElementById('btn-text-newsdata-test').textContent = 'Test Key';
        });
    });
});
</script>
@endpush
@endsection
