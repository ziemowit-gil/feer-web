@php $values = $siteSettings->wrzosValues(); @endphp
@if (! empty($values))
<section class="bg-gray-100 py-14" aria-labelledby="wrzos-values-heading">
    <div class="mx-auto max-w-[1400px] px-4">
        <h2 id="wrzos-values-heading" class="sr-only">Nasze wartości</h2>
        <ul class="grid gap-px overflow-hidden rounded-lg bg-white/40 sm:grid-cols-2 lg:grid-cols-3" role="list">
            @foreach ($values as $value)
                <li class="flex flex-col items-center gap-3 bg-brand px-6 py-10 text-center text-white">
                    <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full border-2 border-white/70" aria-hidden="true">
                        <i class="{{ $value['icon'] ?? 'fa-solid fa-circle-check' }} text-xl"></i>
                    </span>
                    <span class="text-sm font-extrabold uppercase tracking-wide">{{ $value['title'] ?? '' }}</span>
                    <span class="max-w-xs text-sm leading-relaxed text-white/90">{{ $value['text'] ?? '' }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
