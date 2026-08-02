@extends('admin.layout')

@section('title', 'Tagi aktualności')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nazwa tagu</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Aktualności</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" x-data="{ editing: null }">
                @forelse ($tags as $tag)
                    <tr x-bind:class="editing === {{ $tag->id }} ? 'bg-brand-light/30' : ''">
                        <td class="px-4 py-3 font-medium">
                            <span x-show="editing !== {{ $tag->id }}">{{ $tag->name }}</span>

                            <form x-show="editing === {{ $tag->id }}"
                                  action="{{ route('admin.tagi.update', $tag) }}"
                                  method="POST"
                                  class="flex items-center gap-2"
                                  x-cloak>
                                @csrf
                                @method('PUT')
                                <input type="text"
                                       name="name"
                                       value="{{ $tag->name }}"
                                       class="rounded border border-gray-300 px-2 py-1 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                                       required
                                       maxlength="100"
                                       aria-label="Nowa nazwa tagu {{ $tag->name }}">
                                <button type="submit" class="text-brand hover:text-brand-dark" title="Zapisz">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button type="button" @click="editing = null" class="text-muted hover:text-ink" title="Anuluj">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-muted font-mono text-xs">{{ $tag->slug }}</td>
                        <td class="px-4 py-3">
                            @if ($tag->news_count > 0)
                                <a href="{{ route('admin.newsy.index', ['tag' => $tag->slug]) }}"
                                   class="font-bold text-ink hover:text-brand">
                                    {{ $tag->news_count }}
                                </a>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3" x-show="editing !== {{ $tag->id }}">
                                <button @click="editing = {{ $tag->id }}"
                                        class="text-muted hover:text-brand"
                                        title="Zmień nazwę">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('admin.tagi.destroy', $tag) }}"
                                      onsubmit="return confirm('Usunąć tag &quot;{{ $tag->name }}&quot;?{{ $tag->news_count > 0 ? '\n\nTag jest używany przez '.$tag->news_count.' aktualności. Zostanie odpięty od wszystkich.' : '' }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-muted">
                            Brak tagów. Tagi tworzone są automatycznie podczas edycji aktualności.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-3 text-xs text-muted">
        Tagi tworzone są automatycznie przy zapisie aktualności. Tutaj możesz zmienić ich nazwy lub usunąć nieużywane.
    </p>
@endsection
