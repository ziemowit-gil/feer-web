@extends('admin.layout')
@section('title', $segment->exists ? 'Edycja segmentu' : 'Nowy segment')
@section('content')
    @include('newsletter::admin.partials.flash')
    @php
        $options = ['status' => $statuses, 'topics' => $topics, 'channels' => $channels, 'list_id' => $lists->all(), 'site_id' => $sites->all()];
        $initial = $segment->rules['rules'] ?? [];
    @endphp
    <form method="POST" action="{{ $segment->exists ? route('admin.newsletter.segmenty.update', $segment) : route('admin.newsletter.segmenty.store') }}" class="max-w-4xl space-y-4 rounded-lg border border-gray-200 bg-white p-6"
          x-data="segmentBuilder(@js($initial), @js($options), '{{ route('admin.newsletter.segmenty.count') }}')" x-init="count()">
        @csrf @if ($segment->exists) @method('PUT') @endif
        <div class="flex flex-wrap items-end gap-4">
            <div class="grow"><label for="name" class="mb-1 block text-sm font-bold">Nazwa</label><input id="name" name="name" required value="{{ old('name', $segment->name) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
            <div><label for="match" class="mb-1 block text-sm font-bold">Dopasowanie</label><select id="match" name="match" x-model="match" @change="count()" class="rounded border-gray-300 focus:border-brand focus:ring-brand"><option value="all">wszystkie reguły</option><option value="any">dowolna reguła</option></select></div>
            <p class="rounded bg-brand/10 px-4 py-2 text-sm font-bold text-brand-dark" aria-live="polite">Odbiorców: <span x-text="total === null ? '…' : total"></span></p>
        </div>
        <template x-for="(r, i) in rules" :key="i">
            <div class="flex flex-wrap items-center gap-2 rounded border border-gray-200 p-2">
                <select :name="`rules[${i}][field]`" x-model="r.field" @change="r.value=''; count()" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand" aria-label="Pole">@foreach ($fields as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <select :name="`rules[${i}][op]`" x-model="r.op" @change="count()" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand" aria-label="Operator">@foreach ($operators as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <template x-if="options[r.field]">
                    <select :name="`rules[${i}][value]`" x-model="r.value" @change="count()" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand" aria-label="Wartość"><template x-for="(label, val) in options[r.field]" :key="val"><option :value="val" x-text="label"></option></template></select>
                </template>
                <template x-if="!options[r.field]">
                    <input :name="`rules[${i}][value]`" x-model="r.value" @input.debounce.500ms="count()" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand" aria-label="Wartość" placeholder="wartość (dni / tekst / lista po przecinku)">
                </template>
                <button type="button" @click="rules.splice(i,1); count()" class="ml-auto text-muted hover:text-red-700" aria-label="Usuń regułę"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>
        </template>
        <button type="button" @click="rules.push({field:'topics', op:'contains', value:''})" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm font-bold text-gray-700 hover:bg-gray-50"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj regułę</button>
        <p class="text-xs text-muted">Pola dat (zapis, potwierdzenie, ostatnie otwarcie) z operatorami „w ciągu N dni” / „dawniej niż N dni” — wartość to liczba dni. Pola listowe (tematy, kanały, tagi) przyjmują kilka wartości po przecinku.</p>
        <div class="flex gap-2 border-t border-gray-200 pt-4"><button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button><a href="{{ route('admin.newsletter.segmenty.index') }}" class="rounded border border-gray-300 bg-white px-5 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Anuluj</a></div>
    </form>
    <script>
    function segmentBuilder(initial, options, countUrl) {
        return {
            rules: initial.map(r => ({ field: r.field, op: r.op, value: Array.isArray(r.value) ? r.value.join(',') : (r.value ?? '') })),
            match: '{{ ($segment->rules['match'] ?? 'all') }}', options, total: null, timer: null,
            count() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => {
                    const fd = new FormData(); fd.append('match', this.match);
                    this.rules.forEach((r, i) => { fd.append(`rules[${i}][field]`, r.field); fd.append(`rules[${i}][op]`, r.op); fd.append(`rules[${i}][value]`, r.value); });
                    fetch(countUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: fd })
                        .then(r => r.json()).then(d => { this.total = d.count; }).catch(() => { this.total = '?'; });
                }, 300);
            }
        };
    }
    </script>
@endsection
