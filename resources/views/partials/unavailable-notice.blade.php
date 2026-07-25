{{--
    Full-screen stand-in shown instead of an entity's content when it is
    disabled or in full-screen "under construction" mode. Works for any model
    exposing wipIsFull()/wipMessage()/disabledMessage() and a `title` (pages,
    blog articles, …).

    Expects: $entity
    Optional: $backUrl (default: home), $backLabel (default: "Wróć na stronę główną")
--}}
@php
    $backUrl = $backUrl ?? route('home');
    $backLabel = $backLabel ?? 'Wróć na stronę główną';
    $isWip = $entity->wipIsFull();
    $icon = $isWip ? 'fa-person-digging' : 'fa-circle-pause';
    $message = $isWip ? $entity->wipMessage() : $entity->disabledMessage();
@endphp

<section class="mx-auto max-w-2xl px-4 py-20 text-center">
    <span class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-brand-light text-3xl text-brand" aria-hidden="true">
        <i class="fa-solid {{ $icon }}"></i>
    </span>

    <h1 class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $entity->title }}</h1>

    <p class="mx-auto max-w-xl text-lg text-muted">{{ $message }}</p>

    <div class="mt-10">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-dark">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> {{ $backLabel }}
        </a>
    </div>
</section>
