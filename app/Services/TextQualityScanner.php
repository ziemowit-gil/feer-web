<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Skaner jakości tekstów serwisu (panel → Jakość tekstów). Tylko odczyt: szuka w opublikowanych treściach wzorców,
 * które często zdradzają „maszynowy” albo niedopracowany tekst, i pokazuje je z fragmentem kontekstu.
 * Niczego nie zmienia: poprawki robi redakcja w edytorze.
 *
 * Reguły pochodzą z listy wzorców humanizera (myślniki jako spójniki, kontrasty „nie X, lecz Y”, slogany,
 * słowa reklamowe) oraz z błędów, które widać gołym okiem (nawiasy szablonowe, spacja przed interpunkcją).
 */
class TextQualityScanner
{
    public const RULES = [
        'placeholder' => [
            'label' => 'Nawiasy szablonowe do uzupełnienia',
            'hint' => 'Fragment w nawiasach kwadratowych to najpewniej niewypełniony szablon. Uzupełnij albo usuń.',
        ],
        'dash' => [
            'label' => 'Myślnik jako spójnik',
            'hint' => 'Zastąp dwukropkiem, przecinkiem albo kropką. Myślnik w nazwie własnej lub zakresie zostaw.',
        ],
        'contrast' => [
            'label' => 'Konstrukcja „nie X, lecz Y”',
            'hint' => 'Zwykle da się napisać wprost, co jest prawdą, bez zaprzeczania czemuś, czego nikt nie twierdził.',
        ],
        'slogan' => [
            'label' => 'Slogan lub zdanie-wypełniacz',
            'hint' => 'Zdanie, które powtarza myśl albo ogłasza wagę, zamiast dodawać fakt. Rozważ skrócenie.',
        ],
        'sales' => [
            'label' => 'Słowo reklamowe',
            'hint' => 'Przymiotnik z ulotki („unikalny”, „kompleksowy”). Napisz, co dokładnie robicie.',
        ],
        'space' => [
            'label' => 'Spacja przed znakiem interpunkcyjnym',
            'hint' => 'Literówka w składzie, np. „Krakowa .” Usuń zbędną spację.',
        ],
    ];

    public const SOURCES = [
        'page' => 'Strony',
        'news' => 'Aktualności',
        'project' => 'Działania',
        'faq' => 'FAQ',
    ];

    private const SHORTCODES = 'formularz|kafelki|kafelki-zestaw|blok|pasek|banery|baner|podstrony|aktualnosci|materialy|klauzule-rodo|nabory|zapytania-ofertowe';

    private const SLOGANS = [
        'każda pomoc ma znaczenie', 'zapraszamy do kontaktu', 'serdecznie zachęcamy', 'wierzymy, że', 'razem budujemy',
        'krok w stronę świata', 'dlatego działamy', 'w centrum naszych działań', 'to nie jest kolejny', 'dzieje się tak dzięki',
    ];

    /**
     * @return array{findings: Collection<int, array<string, mixed>>, counts: array<string, int>, total: int, scanned: int}
     */
    public function scan(?string $rule = null, ?string $source = null, int $limit = 400): array
    {
        $findings = collect();
        $counts = array_fill_keys(array_keys(self::RULES), 0);
        $scanned = 0;

        foreach ($this->records() as $record) {
            $scanned++;
            foreach ($record['fields'] as $field => $raw) {
                $text = $this->plain((string) $raw);
                if ($text === '') {
                    continue;
                }
                foreach ($this->matches($text) as $m) {
                    $counts[$m['rule']]++;
                    if (($rule && $rule !== $m['rule']) || ($source && $source !== $record['source'])) {
                        continue;
                    }
                    $findings->push($m + [
                        'source' => $record['source'],
                        'source_label' => self::SOURCES[$record['source']],
                        'title' => $record['title'],
                        'field' => $field,
                        'edit_url' => $record['edit_url'],
                    ]);
                }
            }
        }

        $total = $findings->count();

        return ['findings' => $findings->take($limit)->values(), 'counts' => $counts, 'total' => $total, 'scanned' => $scanned];
    }

    /** Tekst jawny: bez znaczników, z jednolitymi spacjami. */
    public function plain(string $html): string
    {
        $html = preg_replace('~<(?:br|/p|/li|/h[1-6]|/div|/td|/th|/tr)\b[^>]*>~i', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** @return list<array{rule: string, before: string, match: string, after: string}> */
    public function matches(string $text): array
    {
        $found = [];
        $push = function (string $rule, int $bytePos, string $match) use ($text, &$found): void {
            $start = mb_strlen(substr($text, 0, $bytePos));
            $len = mb_strlen($match);
            $found[] = [
                'rule' => $rule,
                'before' => ($start > 60 ? '…' : '').mb_substr($text, max(0, $start - 60), min(60, $start)),
                'match' => $match,
                'after' => mb_substr($text, $start + $len, 60).(mb_strlen($text) > $start + $len + 60 ? '…' : ''),
            ];
        };

        $patterns = [
            'placeholder' => ['/\[(?!(?:'.self::SHORTCODES.')\b)[^\[\]]{3,160}\]/u'],
            'dash' => ['/\s[—–]\s/u'],
            'contrast' => [
                '/\bnie\b[^.!?]{1,60},\s*lecz\b/iu',
                '/\bnie\s+(?:tylko|jedynie)\b[^.!?]{1,70},\s*(?:ale|lecz)\b/iu',
                '/\bnie\b[^.!?]{1,50}\s[—–]\s(?:to|tylko)\b/iu',
            ],
            'sales' => ['/\b(?:unikaln|wyjątkow|kompleksow|profesjonaln|innowacyjn|rewolucyjn|przełomow|niezwykł)\p{L}*\b/iu'],
            'space' => ['/[\p{L}\d]\s+[,.;:](?=\s|$)/u'],
        ];

        foreach ($patterns as $rule => $list) {
            foreach ($list as $pattern) {
                if (preg_match_all($pattern, $text, $all, PREG_OFFSET_CAPTURE)) {
                    foreach ($all[0] as [$match, $pos]) {
                        $push($rule, $pos, trim($match) === '' ? $match : $match);
                    }
                }
            }
        }

        $lower = mb_strtolower($text);
        foreach (self::SLOGANS as $slogan) {
            $offset = 0;
            while (($pos = mb_strpos($lower, $slogan, $offset)) !== false) {
                $push('slogan', strlen(mb_substr($text, 0, $pos)), mb_substr($text, $pos, mb_strlen($slogan)));
                $offset = $pos + mb_strlen($slogan);
            }
        }

        usort($found, fn ($a, $b) => strcmp($a['rule'], $b['rule']));

        return $found;
    }

    /** @return iterable<array{source: string, title: string, edit_url: string, fields: array<string, ?string>}> */
    private function records(): iterable
    {
        foreach (Page::query()->where('is_published', true)->get(['id', 'title', 'content', 'about_intro']) as $p) {
            yield ['source' => 'page', 'title' => (string) $p->title, 'edit_url' => route('admin.podstrony.edit', $p),
                'fields' => ['Tytuł' => $p->title, 'Treść' => $p->content, 'Wstęp' => $p->about_intro]];
        }
        foreach (News::query()->where('is_published', true)->get(['id', 'title', 'excerpt', 'content']) as $n) {
            yield ['source' => 'news', 'title' => (string) $n->title, 'edit_url' => route('admin.newsy.edit', $n),
                'fields' => ['Tytuł' => $n->title, 'Zajawka' => $n->excerpt, 'Treść' => $n->content]];
        }
        foreach (Project::query()->where('is_published', true)->get(['id', 'title', 'excerpt', 'content', 'why']) as $p) {
            yield ['source' => 'project', 'title' => (string) $p->title, 'edit_url' => route('admin.projekty.edit', $p),
                'fields' => ['Tytuł' => $p->title, 'Zajawka' => $p->excerpt, 'Treść' => $p->content, 'Dlaczego to robimy' => $p->why]];
        }
        foreach (Faq::query()->get(['id', 'question', 'answer']) as $f) {
            yield ['source' => 'faq', 'title' => (string) $f->question, 'edit_url' => route('admin.faq.edit', $f),
                'fields' => ['Pytanie' => $f->question, 'Odpowiedź' => $f->answer]];
        }
    }
}
