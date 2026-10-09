@extends('admin.layout')
@section('title', 'Kampanie newslettera')
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1 text-xs">
            <a href="{{ route('admin.newsletter.kampanie.index') }}" class="rounded-full border px-3 py-1 font-bold {{ $status === '' ? 'border-brand-dark bg-brand-dark text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">Wszystkie</a>
            @foreach ($statuses as $k => $l)<a href="{{ route('admin.newsletter.kampanie.index', ['status' => $k]) }}" class="rounded-full border px-3 py-1 font-bold {{ $status === $k ? 'border-brand-dark bg-brand-dark text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">{{ $l }}</a>@endforeach
        </div>
        <a href="{{ route('admin.newsletter.kampanie.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nowa kampania</a>
    </div>
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-4 py-3">Nazwa / temat</th><th class="px-4 py-3">Kanały</th><th class="px-4 py-3">Stan</th><th class="px-4 py-3">Termin / wysłano</th><th class="px-4 py-3 text-right">Odbiorcy</th><th class="px-4 py-3 text-right">Otw.</th><th class="px-4 py-3 text-right">Klik.</th><th class="px-4 py-3"><span class="sr-only">Akcje</span></th></tr></thead>
            <tbody>
            @forelse ($campaigns as $c)
                <tr class="border-t border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-2"><a href="{{ route('admin.newsletter.kampanie.show', $c) }}" class="font-bold text-brand-dark hover:underline">{{ $c->title }}</a>@if($c->isRecurring()) <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-900" title="Kampania cykliczna">cykliczna</span>@endif @if($c->parent_campaign_id) <span class="text-xs text-muted">(z cyklu)</span>@endif<br><span class="text-xs text-muted">{{ $c->subject }}</span></td>
                    <td class="px-4 py-2 text-muted">@foreach ($c->channels ?? [] as $ch)<i class="fa-solid {{ ['email' => 'fa-envelope', 'webpush' => 'fa-bell', 'sms' => 'fa-comment-sms'][$ch] ?? 'fa-circle' }} mr-1" aria-label="{{ \App\Models\Subscriber::CHANNELS[$ch] ?? $ch }}" title="{{ \App\Models\Subscriber::CHANNELS[$ch] ?? $ch }}"></i>@endforeach</td>
                    <td class="px-4 py-2">@include('newsletter::admin.partials.status-badge', ['value' => $c->status, 'label' => $c->statusLabel()])</td>
                    <td class="px-4 py-2 text-muted">{{ ($c->finished_at ?? $c->started_at ?? $c->scheduled_at)?->format('d.m.Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-2 text-right">{{ $c->recipients_count ?: '—' }}</td>
                    <td class="px-4 py-2 text-right">{{ $c->rate('opened_unique') !== null ? $c->rate('opened_unique') . ' %' : '—' }}</td>
                    <td class="px-4 py-2 text-right">{{ $c->rate('clicked_unique') !== null ? $c->rate('clicked_unique') . ' %' : '—' }}</td>
                    <td class="px-4 py-2 text-right whitespace-nowrap">
                        @if ($c->isEditable())<a href="{{ route('admin.newsletter.kampanie.editor', $c) }}" class="mr-2 text-muted hover:text-brand-dark" aria-label="Treść {{ $c->title }}"><i class="fa-solid fa-pen-ruler" aria-hidden="true"></i></a>@endif
                        @if ($c->status === 'sent')<a href="{{ route('admin.newsletter.kampanie.report', $c) }}" class="mr-2 text-muted hover:text-brand-dark" aria-label="Raport {{ $c->title }}"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></a>@endif
                        <form method="POST" action="{{ route('admin.newsletter.kampanie.duplicate', $c) }}" class="inline">@csrf<button class="text-muted hover:text-brand-dark" aria-label="Duplikuj {{ $c->title }}"><i class="fa-solid fa-copy" aria-hidden="true"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-muted">Brak kampanii.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $campaigns->links() }}</div>
@endsection
