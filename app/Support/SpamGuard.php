<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Warstwowa ochrona formularzy publicznych przed spamem — bez obrazkowej
 * CAPTCHY, która jest barierą dostępności (WCAG 1.1.1).
 *
 * Warstwy:
 *  1. pole-pułapka (honeypot) — niewidoczne, człowiek go nie wypełni,
 *  2. zaszyfrowany żeton z czasem wygenerowania strony — chroni przed
 *     wysyłką natychmiastową i przed formularzem sklejonym poza stroną,
 *  3. proste zadanie tekstowe („ile wynosi 3 + 5?”) — czytane przez czytniki
 *     ekranu, z odpowiedzią słowną lub cyfrą,
 *  4. analiza treści (nadmiar odnośników, typowe frazy) i wykrywanie duplikatów.
 *
 * Dwa rodzaje odrzuceń:
 *  - „ciche” (bot): honeypot, brak lub podmieniony żeton, wysyłka w mgnieniu
 *    oka, przedawniona strona. Bot dostaje zwykłe potwierdzenie, więc nie wie,
 *    że został odfiltrowany.
 *  - „jawne”: zła odpowiedź na zadanie, podejrzana treść, duplikat. Tu możliwy
 *    jest fałszywy alarm u człowieka, więc pokazujemy zrozumiały komunikat,
 *    który pozwala poprawić zgłoszenie.
 *
 * Metody: challenge(), inspect().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SpamGuard
{
    /** Nazwa pola-pułapki: prawdziwy użytkownik nigdy go nie wypełni. */
    public const HONEYPOT_FIELD = 'website';

    /** Zaszyfrowany żeton: czas wygenerowania strony + oczekiwany wynik zadania. */
    public const TOKEN_FIELD = 'form_check';

    /** Pole z odpowiedzią na zadanie tekstowe. */
    public const ANSWER_FIELD = 'form_answer';

    /** Minimalny czas wypełniania — poniżej tego progu to skrypt, nie człowiek. */
    private const MIN_SECONDS = 3;

    /** Po tym czasie formularz uznajemy za przedawniony (i tak wygasł już CSRF). */
    private const MAX_AGE_SECONDS = 86400;

    /** Dopuszczalna liczba odnośników w treści zgłoszenia. */
    private const MAX_LINKS = 2;

    /** Okno, w którym identyczne zgłoszenie z tego samego IP to duplikat. */
    private const DUPLICATE_WINDOW_SECONDS = 600;

    /** Frazy i wzorce typowe dla spamu rozsyłanego automatycznie. */
    private const SPAM_PATTERNS = [
        '/\[url[\s=\]]/i',
        '/\[link[\s=\]]/i',
        '/<a\s[^>]*href/i',
        '/\b(?:viagra|cialis|casino|kasyno online|payday loan|kredyt bez ba[żz]y|seo services|buy backlinks|crypto invest)\b/i',
    ];

    /** Liczebniki słowne przyjmowane zamiast cyfry — wynik nigdy nie przekroczy 18. */
    private const NUMBER_WORDS = [
        'zero' => 0, 'jeden' => 1, 'jedno' => 1, 'dwa' => 2, 'trzy' => 3, 'cztery' => 4,
        'pięć' => 5, 'piec' => 5, 'sześć' => 6, 'szesc' => 6, 'siedem' => 7, 'osiem' => 8,
        'dziewięć' => 9, 'dziewiec' => 9, 'dziesięć' => 10, 'dziesiec' => 10,
        'jedenaście' => 11, 'jedenascie' => 11, 'dwanaście' => 12, 'dwanascie' => 12,
        'trzynaście' => 13, 'trzynascie' => 13, 'czternaście' => 14, 'czternascie' => 14,
        'piętnaście' => 15, 'pietnascie' => 15, 'szesnaście' => 16, 'szesnascie' => 16,
        'siedemnaście' => 17, 'siedemnascie' => 17, 'osiemnaście' => 18, 'osiemnascie' => 18,
    ];

    /**
     * Losuje zadanie tekstowe i zwraca treść pytania wraz z żetonem, w którym
     * zaszyfrowany jest oczekiwany wynik i czas wygenerowania strony.
     *
     * @return array{question: string, token: string}
     */
    public static function challenge(): array
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);

        return [
            'question' => "Ile wynosi {$a} + {$b}?",
            'token'    => self::issueToken($a + $b),
        ];
    }

    /**
     * Sprawdza zgłoszenie. Zwraca null, gdy jest w porządku.
     *
     * @param  array<int|string, mixed>  $values  wartości pól wpisane przez użytkownika
     * @param  string  $scope  identyfikator formularza — rozdziela wykrywanie duplikatów
     * @return array{reason: string, silent: bool, message: string|null}|null
     */
    public static function inspect(Request $request, array $values, string $scope): ?array
    {
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return self::bot('honeypot');
        }

        $token = self::readToken($request);

        if ($token === null) {
            return self::bot('brak lub nieważny żeton formularza');
        }

        $elapsed = now()->timestamp - $token['t'];

        if ($elapsed < self::MIN_SECONDS) {
            return self::bot('wysłano po '.max(0, $elapsed).' s');
        }

        if ($elapsed > self::MAX_AGE_SECONDS) {
            return self::bot('przedawniony formularz');
        }

        if (self::parseAnswer((string) $request->input(self::ANSWER_FIELD, '')) !== $token['a']) {
            return self::content(
                'błędna odpowiedź na zadanie',
                'Odpowiedź na zadanie antyspamowe jest nieprawidłowa. Poniżej znajdziesz nowe zadanie — '
                .'wpisz wynik cyfrą lub słownie.',
            );
        }

        $text = self::textOf($values);

        if (self::countLinks($text) > self::MAX_LINKS) {
            return self::content(
                'nadmiar odnośników',
                'Zgłoszenie zawiera zbyt wiele odnośników i zostało zablokowane przez filtr antyspamowy. '
                .'Usuń część adresów internetowych i spróbuj ponownie.',
            );
        }

        foreach (self::SPAM_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return self::content(
                    'wzorzec spamu',
                    'Treść zgłoszenia została rozpoznana jako spam. Zmień jej brzmienie lub skontaktuj się z nami telefonicznie.',
                );
            }
        }

        if (self::isDuplicate($request, $values, $scope)) {
            return self::content(
                'duplikat',
                'To zgłoszenie zostało już wysłane. Jeśli chcesz przekazać coś jeszcze, zmień treść wiadomości.',
            );
        }

        return null;
    }

    /** Szyfruje czas wygenerowania strony razem z oczekiwanym wynikiem zadania. */
    private static function issueToken(int $answer): string
    {
        return Crypt::encryptString(json_encode(['t' => now()->timestamp, 'a' => $answer]));
    }

    /**
     * Odszyfrowuje żeton; null dla braku, podmiany lub śmieci.
     *
     * @return array{t: int, a: int}|null
     */
    private static function readToken(Request $request): ?array
    {
        $raw = $request->input(self::TOKEN_FIELD);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($raw), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['t'], $payload['a'])) {
            return null;
        }

        return ['t' => (int) $payload['t'], 'a' => (int) $payload['a']];
    }

    /** Odpowiedź cyfrą albo słownie; null, gdy nie da się jej odczytać. */
    private static function parseAnswer(string $input): ?int
    {
        $value = mb_strtolower(trim($input));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{1,3}$/', $value)) {
            return (int) $value;
        }

        // Liczebniki złożone typu „czternaście” mamy w słowniku, ale „dwanaście
        // i pół” czy zapisy z myślnikiem sprowadzamy do samych liter.
        $word = preg_replace('/[^a-ząćęłńóśźż]/u', '', $value);

        return self::NUMBER_WORDS[$word] ?? null;
    }

    /** Skleja wszystkie wartości tekstowe w jeden łańcuch do analizy treści. */
    private static function textOf(array $values): string
    {
        $flat = [];

        array_walk_recursive($values, function ($value) use (&$flat) {
            if (is_string($value)) {
                $flat[] = $value;
            }
        });

        return implode("\n", $flat);
    }

    private static function countLinks(string $text): int
    {
        return preg_match_all('#(?:https?://|www\.)[^\s<>"\']+#i', $text);
    }

    /** Identyczna treść z tego samego IP w krótkim okienku = duplikat. */
    private static function isDuplicate(Request $request, array $values, string $scope): bool
    {
        $key = 'spam-guard:'.sha1($scope.'|'.$request->ip().'|'.json_encode($values));

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, self::DUPLICATE_WINDOW_SECONDS);

        return false;
    }

    /** @return array{reason: string, silent: bool, message: null} */
    private static function bot(string $reason): array
    {
        return ['reason' => $reason, 'silent' => true, 'message' => null];
    }

    /** @return array{reason: string, silent: bool, message: string} */
    private static function content(string $reason, string $message): array
    {
        return ['reason' => $reason, 'silent' => false, 'message' => $message];
    }
}
