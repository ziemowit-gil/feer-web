@php
    $sidebarActive = $siteSettings->headerLayoutValue() === 'wide_mission' && ($siteSettings->wide_mission_sidebar ?? false);
    $quickLinksHere = ($sidebarActive && $quickLinks->isNotEmpty()) ? collect() : $quickLinks;
@endphp
@if ($poll || $quickLinksHere->isNotEmpty())
<section class="{{ ($siteSettings->quick_actions_panel_negative ?? false) ? 'bg-white' : 'bg-gray-50' }}">
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
                            <div class="ml-6 mt-1 h-2 w-full max-w-xs overflow-hidden bg-gray-200" aria-hidden="true">
                                <div class="h-full bg-brand-dark" style="width: {{ $option->percent($totalVotes) }}%"></div>
                            </div>
                        </label>
                    @endforeach

                    @if ($votedOptionId)
                        <p class="text-sm font-bold text-ink">Dziękujemy za oddanie głosu.</p>
                    @else
                        <button type="submit" class="mt-2 inline-flex min-h-11 items-center rounded-md bg-brand-dark px-6 text-sm font-bold text-white transition-colors hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Głosuj</button>
                    @endif
                </form>
            </div>
        @endif

        @if ($quickLinksHere->isNotEmpty())
            <div>
                <h2 class="mb-4 text-xl font-bold text-ink">Na skróty</h2>
                @include('partials._tiles-grid', ['tiles' => $quickLinksHere, 'label' => 'Na skróty'])
            </div>
        @endif
    </div>
</section>
@endif
