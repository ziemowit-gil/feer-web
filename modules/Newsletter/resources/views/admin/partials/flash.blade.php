@if (session('status'))
    <p class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm font-bold text-green-900" role="status">{{ session('status') }}</p>
@endif
@if (session('error'))
    <p class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm font-bold text-red-900" role="alert">{{ session('error') }}</p>
@endif
@if ($errors->any())
    <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
        <p class="font-bold">Popraw błędy w formularzu:</p>
        <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
