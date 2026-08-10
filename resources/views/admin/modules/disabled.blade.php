@extends('admin.layout')

@section('title', $moduleName . ' — moduł dezaktywowany')

@section('content')
    <div class="flex min-h-[60vh] flex-col items-center justify-center text-center">
        <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50">
            <i class="fa-solid fa-puzzle-piece text-2xl text-amber-400" aria-hidden="true"></i>
        </div>

        <h1 class="mb-2 text-xl font-bold text-ink">Moduł dezaktywowany</h1>
        <p class="mb-1 text-sm font-medium text-muted">{{ $moduleName }}</p>
        <p class="mb-8 max-w-sm text-sm text-muted">
            Ten moduł jest wyłączony. Aktywuj go na stronie modułów, żeby mieć dostęp do tej sekcji.
        </p>

        <a href="{{ route('admin.moduly.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            <i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i>
            Przejdź do modułów
        </a>
    </div>
@endsection
