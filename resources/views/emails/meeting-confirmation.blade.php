<x-mail::message>
# Potwierdzenie spotkania

Cześć **{{ $signup->name }}**,

potwierdzamy Twoje zgłoszenie na spotkanie z {{ $siteName }}.

@if ($signup->term)
**Termin:** {{ $signup->term }}
@endif

Do zobaczenia! Jeśli masz pytania, odpisz na ten e-mail lub skontaktuj się z nami bezpośrednio na adres [{{ $contactEmail }}](mailto:{{ $contactEmail }}).

<x-mail::button :url="'mailto:' . $contactEmail">
Napisz do nas
</x-mail::button>

Pozdrawiamy,<br>
Zespół {{ $siteName }}
</x-mail::message>
