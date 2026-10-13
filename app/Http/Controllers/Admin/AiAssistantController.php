<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Asystent AI przy pisaniu tekstów (Claude API). Klucz zostaje na serwerze — przeglądarka wysyła tylko
 * tekst i wybraną czynność, a dostaje propozycję do podglądu (nic nie zapisuje się samo).
 */
class AiAssistantController extends Controller
{
    /** Czynność => [nazwa, polecenie, czy wynik to HTML]. */
    public const ACTIONS = [
        'improve' => ['Popraw język i styl', 'Popraw błędy językowe, interpunkcję i styl. Nie zmieniaj sensu, faktów, nazw ani liczb. Zachowaj ton i długość tekstu.', true],
        'simplify' => ['Prosty język', 'Przepisz tekst prostym językiem (plain language): krótkie zdania, codzienne słowa, strona czynna, najważniejsza informacja na początku. Nie pomijaj żadnych faktów.', true],
        'etr' => ['Tekst łatwy do czytania (ETR)', 'Przepisz tekst zgodnie z zasadami tekstu łatwego do czytania i zrozumienia (ETR): bardzo krótkie zdania, jedna myśl w zdaniu, proste słowa, każde zdanie w osobnej linii lub krótkie akapity, objaśnij trudne słowa. Zwracaj się bezpośrednio do czytelnika.', true],
        'shorten' => ['Skróć', 'Skróć tekst mniej więcej o połowę, zachowując najważniejsze informacje, fakty i wezwania do działania.', true],
        'expand' => ['Rozwiń', 'Rozwiń tekst: dodaj wyjaśnienia i przykłady, ale nie wymyślaj faktów, dat, liczb ani nazw, których nie ma w tekście.', true],
        'title' => ['Zaproponuj tytuł', 'Zaproponuj 3 krótkie, konkretne tytuły (do 70 znaków) dla tego tekstu. Zwróć każdy w osobnej linii, bez numeracji i cudzysłowów.', false],
        'lead' => ['Zaproponuj zajawkę', 'Napisz zajawkę (lead) do tego tekstu: 1–2 zdania, do 220 znaków, zachęcające do przeczytania, bez wykrzykników.', false],
        'meta' => ['Zaproponuj opis SEO', 'Napisz opis meta (meta description) do tego tekstu: jedno zdanie, 120–155 znaków, zawierające najważniejsze słowa kluczowe.', false],
        'custom' => ['Własne polecenie', null, true],
    ];

    private const SYSTEM = 'Jesteś redaktorem stron internetowych polskiej organizacji pozarządowej działającej na rzecz osób z niepełnosprawnościami. '
        .'Piszesz po polsku, poprawnie, życzliwie i bez żargonu, z dbałością o dostępność (WCAG). Nie wymyślasz faktów. '
        .'Odpowiadasz wyłącznie gotowym wynikiem — bez wstępów, komentarzy i bloków kodu.';

    public function __invoke(Request $request): JsonResponse
    {
        $settings = SiteSetting::current();
        abort_unless($settings->aiAssistantEnabled(), 404);

        $data = $request->validate([
            'action' => ['required', Rule::in(array_keys(self::ACTIONS))],
            'text' => ['required', 'string', 'max:60000'],
            'instruction' => ['nullable', 'required_if:action,custom', 'string', 'max:1000'],
            'html' => ['sometimes', 'boolean'],
        ]);

        [, $task, $returnsHtml] = self::ACTIONS[$data['action']];
        $isHtml = $returnsHtml && $request->boolean('html', true);
        $task = $data['action'] === 'custom' ? trim($data['instruction']) : $task;
        $format = $isHtml
            ? 'Tekst wejściowy to HTML. Zwróć wynik jako HTML: zachowaj strukturę (nagłówki, listy, linki z atrybutami href, pogrubienia), nie dodawaj stylów, skryptów ani atrybutów class/style.'
            : 'Zwróć zwykły tekst bez HTML i bez formatowania Markdown.';

        try {
            $response = Http::withHeaders([
                'x-api-key' => $settings->aiApiKey(),
                'anthropic-version' => '2023-06-01',
            ])->acceptJson()->timeout(90)->post('https://api.anthropic.com/v1/messages', [
                'model' => $settings->aiModel(),
                'max_tokens' => 8000,
                'system' => self::SYSTEM,
                'messages' => [[
                    'role' => 'user',
                    'content' => "Zadanie: {$task}\n{$format}\n\nTekst:\n<<<\n{$data['text']}\n>>>",
                ]],
            ]);
        } catch (Throwable $e) {
            Log::warning('Asystent AI: brak połączenia.', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Nie udało się połączyć z asystentem AI. Spróbuj za chwilę.'], 502);
        }

        if (! $response->successful()) {
            Log::warning('Asystent AI: błąd API.', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            $message = match ($response->status()) {
                401, 403 => 'Klucz API asystenta jest nieprawidłowy — sprawdź go w Ustawienia → Treści.',
                429 => 'Asystent jest chwilowo przeciążony albo wyczerpano limit. Spróbuj za minutę.',
                default => 'Asystent AI nie odpowiedział poprawnie. Spróbuj ponownie.',
            };

            return response()->json(['message' => $message], 502);
        }

        $result = collect($response->json('content', []))->where('type', 'text')->pluck('text')->implode("\n");
        $result = $this->clean($result, $isHtml);

        return response()->json(['result' => $result, 'html' => $isHtml]);
    }

    /** Usuwa ewentualne bloki kodu, skrypty, style i atrybuty zdarzeń z odpowiedzi. */
    private function clean(string $text, bool $isHtml): string
    {
        $text = trim(preg_replace('/^```[a-z]*\s*|\s*```$/i', '', trim($text)));
        if (! $isHtml) {
            return trim(strip_tags($text));
        }
        $text = preg_replace('~<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>~is', '', $text);
        $text = preg_replace('~\s(on[a-z]+|style|class)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)~i', '', $text);
        $text = preg_replace('~href\s*=\s*(["\'])\s*javascript:[^"\']*\1~i', 'href="#"', $text);

        return trim($text);
    }
}
