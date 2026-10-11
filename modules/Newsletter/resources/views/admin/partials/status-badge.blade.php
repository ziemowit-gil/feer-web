@php
    $map = [
        'confirmed' => ['text-ink', 'fa-circle-check'], 'pending' => ['text-ink', 'fa-clock'],
        'unsubscribed' => ['text-muted', 'fa-user-slash'], 'bounced' => ['text-ink', 'fa-triangle-exclamation'],
        'complained' => ['text-ink', 'fa-flag'], 'suppressed' => ['text-ink', 'fa-ban'],
        'expired' => ['text-muted', 'fa-hourglass-end'], 'anonymized' => ['text-muted', 'fa-user-secret'],
        'draft' => ['text-muted', 'fa-pen'], 'scheduled' => ['text-ink', 'fa-calendar'], 'queued' => ['text-ink', 'fa-layer-group'],
        'sending' => ['text-ink', 'fa-paper-plane'], 'paused' => ['text-ink', 'fa-pause'], 'sent' => ['text-ink', 'fa-circle-check'],
        'cancelled' => ['text-muted', 'fa-xmark'], 'failed' => ['text-ink', 'fa-triangle-exclamation'],
        'delivered' => ['text-ink', 'fa-circle-check'], 'soft_bounced' => ['text-ink', 'fa-rotate'], 'hard_bounced' => ['text-ink', 'fa-triangle-exclamation'], 'skipped' => ['text-muted', 'fa-forward'],
    ];
    [$cls, $icon] = $map[$value] ?? ['text-muted', 'fa-circle'];
@endphp
<span class="inline-flex items-center gap-1 text-xs font-bold {{ $cls }}"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i> {{ $label }}</span>
