@extends('admin.layout')

@section('title', 'Zbiórki: teksty i podziękowania')

@php use App\Support\FundraisingTexts; @endphp

@section('content')
    @if (session('status'))
        <div role="status" class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm text-ink">{{ session('status') }}</div>
    @endif
    @if (session('error') || ! $ready)
        <div role="alert" class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') ?? 'Brak kolumny fundraising_texts — uruchom migracje.' }}</div>
    @endif

    <form method="POST" action="{{ route('admin.zbiorki-teksty.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-md bg-white p-6">
            <h2 class="text-base font-bold">Teksty modułu zbiórek</h2>
            <p class="mt-1 text-sm text-ink">
                Obowiązują na liście zbiórek, na każdej zbiórce, na stronach podziękowań, w e-mailu i w potwierdzeniu PDF.
                Pojedyncza zbiórka może je nadpisać w swoim formularzu (sekcja „Teksty tej zbiórki”).
                Strona podziękowania darowizny ogólnej (<a href="{{ route('donation.show') }}" class="font-bold text-brand-dark underline" target="_blank" rel="noopener">/wsparcie/darowizna<span class="sr-only"> (otwiera się w nowej karcie)</span></a>) też korzysta z tych tekstów.
            </p>
            <div class="mt-4">
                @include('admin.fundraising.partials.texts-fields', [
                    'groups' => array_keys(FundraisingTexts::GROUPS),
                    'values' => $values,
                    'inherit' => fn ($key) => FundraisingTexts::DEFS[$key][3] ?? '',
                    'inheritLabel' => 'domyślny (widoczny jako podpowiedź w polu)',
                ])
            </div>
        </div>

        <div class="space-y-5 rounded-md bg-white p-6">
            <h2 class="text-base font-bold">Grafiki podziękowań</h2>
            @include('admin.partials.library-image-picker', [
                'name' => 'thanks_image', 'label' => 'Zdjęcie na stronie podziękowania',
                'current' => $settings->getFirstMediaUrl('fundraising_thanks_image') ?: null,
                'help' => 'Opis alternatywny: Teksty → Podziękowanie: wpłata przyjęta → Opis alternatywny zdjęcia. Film (jeśli ustawiony) ma pierwszeństwo przed zdjęciem.',
            ])
            @if ($settings->getFirstMediaUrl('fundraising_thanks_image'))
                <label class="flex items-center gap-2 text-sm text-red-700"><input type="checkbox" name="remove_thanks_image" value="1" class="rounded border-gray-500"> Usuń zdjęcie</label>
            @endif
            @include('admin.partials.library-image-picker', [
                'name' => 'thanks_email_image', 'label' => 'Grafika w e-mailu z podziękowaniem',
                'current' => $settings->getFirstMediaUrl('fundraising_thanks_email_image') ?: null,
                'help' => 'Szerokość 600 px wystarczy. Opis alternatywny: Teksty → E-mail z podziękowaniem.',
            ])
            @if ($settings->getFirstMediaUrl('fundraising_thanks_email_image'))
                <label class="flex items-center gap-2 text-sm text-red-700"><input type="checkbox" name="remove_thanks_email_image" value="1" class="rounded border-gray-500"> Usuń grafikę</label>
            @endif
        </div>

        <button type="submit" class="rounded bg-brand-dark px-6 py-2.5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
    </form>
@endsection
