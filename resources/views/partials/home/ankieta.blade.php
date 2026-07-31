@php
    $quickLinksHere = $siteSettings->header_layout !== 'wide_mission' || $quickLinks->isEmpty()
        ? $quickLinks
        : collect();
@endphp
@if ($poll || $quickLinksHere->isNotEmpty())
<section class="border-t border-gray-100 bg-gray-50">
    <div class="mx-auto max-w-6xl px-4 py-12 {{ $poll ? 'grid gap-10 md:grid-cols-2' : '' }}">
        @if ($poll)
            <div id="ankieta">
                <h2 class="mb-4 text-xl font-bold text-ink">Ankieta</h2>

                @php
                    $votedKey = "voted_polls.{$poll->id}";
                    $votedOptionId = session($votedKey);
                    $totalVotes = $poll->totalVotes();
                @endphp

                <p class="mb-4 text-sm text-ink">{{ $poll->question }}</p>

                <form action="{{ route('polls.vote', $poll) }}" method="POST" class="space-y-3">
                    @csrf
                    @foreach ($poll->options as $i => $option)
                        <label class="block {{ $votedOptionId ? '' : 'cursor-pointer' }}">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="option_id" value="{{ $option->id }}"
                                    {{ $votedOptionId ? ($votedOptionId == $option->id ? 'checked' : 'disabled') : ($i === 0 ? 'checked' : '') }}
                                    class="accent-brand">
                                <span class="text-sm text-ink">{{ $option->label }} ({{ $option->percent($totalVotes) }}%)</span>
                            </div>
                            <div class="ml-6 mt-1 h-2 w-full max-w-xs overflow-hidden rounded-full bg-gray-200">
                                <div class="h-full rounded-full bg-brand" style="width: {{ $option->percent($totalVotes) }}%"></div>
                            </div>
                        </label>
                    @endforeach

                    @if ($votedOptionId)
                        <p class="text-xs font-bold text-muted">Dziękujemy za oddanie głosu.</p>
                    @else
                        <button type="submit" class="mt-2 rounded bg-brand px-6 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-brand-dark">Głosuj</button>
                    @endif
                </form>
            </div>
        @endif

        @if ($quickLinksHere->isNotEmpty())
            <div>
                <h2 class="mb-4 text-xl font-bold text-ink">Na skróty</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($quickLinksHere as $link)
                        @if (\App\Support\Color::isValid($link->color))
                            @php $qa = \App\Support\Color::button($link->color); @endphp
                            <a href="{{ $link->url }}" class="flex flex-col items-center gap-2 rounded-lg border-2 border-gray-200 bg-white px-4 py-6 text-center shadow-sm transition hover:shadow-md"
                                onmouseover="this.style.borderColor='{{ $qa['bg'] }}'" onmouseout="this.style.borderColor=''">
                                <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full text-2xl"
                                    style="background-color: {{ $qa['bg'] }}; color: {{ $qa['text'] }};">
                                    <i class="bi {{ $link->icon }}" aria-hidden="true"></i>
                                </span>
                                <span class="text-sm font-bold">{{ $link->label }}</span>
                            </a>
                        @else
                            <a href="{{ $link->url }}" class="flex flex-col items-center gap-2 rounded-lg border-2 border-gray-200 bg-white px-4 py-6 text-center shadow-sm hover:border-brand hover:text-brand hover:shadow-md">
                                <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
                                    <i class="bi {{ $link->icon }}" aria-hidden="true"></i>
                                </span>
                                <span class="text-sm font-bold">{{ $link->label }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endif
