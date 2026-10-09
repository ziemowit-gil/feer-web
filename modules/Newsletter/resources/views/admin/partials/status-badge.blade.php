@php
    $map = [
        'confirmed' => ['bg-green-100 text-green-900', 'fa-circle-check'], 'pending' => ['bg-amber-100 text-amber-900', 'fa-clock'],
        'unsubscribed' => ['bg-gray-200 text-gray-800', 'fa-user-slash'], 'bounced' => ['bg-red-100 text-red-900', 'fa-triangle-exclamation'],
        'complained' => ['bg-red-100 text-red-900', 'fa-flag'], 'suppressed' => ['bg-gray-800 text-white', 'fa-ban'],
        'expired' => ['bg-gray-200 text-gray-800', 'fa-hourglass-end'], 'anonymized' => ['bg-gray-200 text-gray-800', 'fa-user-secret'],
        'draft' => ['bg-gray-200 text-gray-800', 'fa-pen'], 'scheduled' => ['bg-blue-100 text-blue-900', 'fa-calendar'], 'queued' => ['bg-amber-100 text-amber-900', 'fa-layer-group'],
        'sending' => ['bg-amber-100 text-amber-900', 'fa-paper-plane'], 'paused' => ['bg-amber-100 text-amber-900', 'fa-pause'], 'sent' => ['bg-green-100 text-green-900', 'fa-circle-check'],
        'cancelled' => ['bg-gray-200 text-gray-800', 'fa-xmark'], 'failed' => ['bg-red-100 text-red-900', 'fa-triangle-exclamation'],
        'delivered' => ['bg-green-100 text-green-900', 'fa-circle-check'], 'soft_bounced' => ['bg-amber-100 text-amber-900', 'fa-rotate'], 'hard_bounced' => ['bg-red-100 text-red-900', 'fa-triangle-exclamation'], 'skipped' => ['bg-gray-200 text-gray-800', 'fa-forward'],
    ];
    [$cls, $icon] = $map[$value] ?? ['bg-gray-200 text-gray-800', 'fa-circle'];
@endphp
<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $cls }}"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i> {{ $label }}</span>
