{{--
    Pasek postępu zbiórki. Kwoty są tekstem (nie tylko kolorem paska), pasek ma
    role="progressbar" z aria-valuetext, więc czytnik słyszy „zebrano X z Y”.
    Parametry: $campaign, $texts, $compact (bool).
--}}
@php
    $compact = $compact ?? false;
    $collected = \App\Support\Money::zl($campaign->collected_amount);
    $goal = \App\Support\Money::zl($campaign->goal_amount);
    $percent = $campaign->progressPercent();
    $vars = ['cel' => $goal, 'zebrano' => $collected];
@endphp
<div>
    <p class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <span>
            <span class="vm-display {{ $compact ? 'text-xl' : 'text-3xl' }} text-ink">{{ $collected }}</span>
            <span class="block text-sm text-ink">
                {{ $campaign->goal_amount > 0 ? $texts->get('progress_collected', $vars) : $texts->get('progress_no_goal', $vars) }}
            </span>
        </span>
        @if ($campaign->goal_amount > 0)
            <span class="vm-display {{ $compact ? 'text-lg' : 'text-2xl' }} text-brand-dark">{{ $percent }}%</span>
        @endif
    </p>

    @if ($campaign->goal_amount > 0)
        <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-gray-300"
             role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
             aria-valuetext="{{ $collected }} z {{ $goal }} ({{ $percent }}%)"
             aria-label="{{ $campaign->title }}: postęp zbiórki">
            <div class="h-full rounded-full bg-brand-dark" style="width: {{ $percent }}%"></div>
        </div>
    @endif

    @if ($campaign->isGoalReached() && ! $compact)
        <p class="mt-3 font-bold text-ink">
            <i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>{{ $texts->get('goal_reached') }}
        </p>
    @endif
</div>
