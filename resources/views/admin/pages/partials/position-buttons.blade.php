{{--
    Para przycisków „Wyżej / Niżej” zmieniających pozycję strony wśród rodzeństwa: zwykłe formularze
    do PageController::reorder() (przekierowanie z komunikatem o nowej pozycji — działa bez skryptu).
    Zmienne: $page, $parentId (null = poziom główny), $index (pozycja od 0), $count, $selectedId
    (strona, do której wraca widok drzewa).
--}}
@php
    $posBtn = 'inline-flex h-7 w-7 items-center justify-center rounded border border-gray-200 bg-white text-xs text-ink hover:border-brand-dark hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark disabled:cursor-not-allowed disabled:border-gray-100 disabled:text-muted';
    $posMoves = [
        ['to' => $index - 1, 'label' => 'Wyżej', 'icon' => 'fa-arrow-up', 'off' => $index <= 0],
        ['to' => $index + 1, 'label' => 'Niżej', 'icon' => 'fa-arrow-down', 'off' => $index >= $count - 1],
    ];
@endphp
<div class="inline-flex gap-1" role="group" aria-label="Pozycja strony „{{ $page->title }}”">
    @foreach ($posMoves as $m)
        <form method="POST" action="{{ route('admin.podstrony.uloz') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $page->id }}">
            <input type="hidden" name="parent_id" value="{{ $parentId }}">
            <input type="hidden" name="position" value="{{ max($m['to'], 0) }}">
            <input type="hidden" name="wybrana" value="{{ $selectedId }}">
            <button type="submit" class="{{ $posBtn }}" @disabled($m['off']) title="{{ $m['label'] }}"
                aria-label="{{ $m['label'] }}: {{ $page->title }}{{ $m['off'] ? '' : ' (na pozycję '.($m['to'] + 1).')' }}">
                <i class="fa-solid {{ $m['icon'] }}" aria-hidden="true"></i>
            </button>
        </form>
    @endforeach
</div>
