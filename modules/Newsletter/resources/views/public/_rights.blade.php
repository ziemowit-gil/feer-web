{{-- Prawa z RODO i działające linki do eksportu i usunięcia danych (wypis, preferencje). Parametr: $subscriber. --}}
<ul style="margin:0 0 12px;padding-left:20px;font-size:14px;line-height:1.6">
    <li>dostęp do danych i ich kopia — <a href="{{ route('newsletter.preferences.export', ['token' => $subscriber->token]) }}" class="np__link">pobierz moje dane (JSON)</a>,</li>
    <li>sprostowanie (zmiana imienia, telefonu i tematów — <a href="{{ route('newsletter.preferences', ['token' => $subscriber->token]) }}" class="np__link">ustawienia subskrypcji</a>),</li>
    <li>usunięcie danych, ograniczenie przetwarzania, przenoszenie i sprzeciw,</li>
    <li>wycofanie zgody w każdej chwili — nie wpływa na zgodność z prawem przetwarzania sprzed wycofania,</li>
    <li>skarga do Prezesa Urzędu Ochrony Danych Osobowych (uodo.gov.pl).</li>
</ul>
<form method="post" action="{{ route('newsletter.preferences.destroy', ['token' => $subscriber->token]) }}" onsubmit="return confirm('Usunąć wszystkie Twoje dane z newslettera? Tej operacji nie można cofnąć.');" style="margin:0">
    @csrf @method('DELETE')
    <button type="submit" class="np__btn np__btn--danger">Usuń moje dane</button>
</form>
