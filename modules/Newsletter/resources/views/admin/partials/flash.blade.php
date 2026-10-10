{{-- Komunikaty status/error renderuje admin.layout; tu tylko błędy walidacji. --}}
@if ($errors->any())
    <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
        <p class="font-bold">Popraw błędy w formularzu:</p>
        <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
