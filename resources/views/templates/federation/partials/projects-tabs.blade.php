@php $onArchive = request()->routeIs('projects.archive'); @endphp
<div class="mb-8 inline-flex rounded-lg border border-gray-200 p-1" role="tablist" aria-label="Zakres projektów">
    <a href="{{ route('projects.index') }}" role="tab" aria-selected="{{ $onArchive ? 'false' : 'true' }}"
        class="rounded-md px-4 py-2 text-sm font-bold transition {{ $onArchive ? 'text-muted hover:text-ink' : 'bg-ink text-white' }}">
        Aktualne
    </a>
    <a href="{{ route('projects.archive') }}" role="tab" aria-selected="{{ $onArchive ? 'true' : 'false' }}"
        class="rounded-md px-4 py-2 text-sm font-bold transition {{ $onArchive ? 'bg-ink text-white' : 'text-muted hover:text-ink' }}">
        Już zrealizowane
    </a>
</div>
