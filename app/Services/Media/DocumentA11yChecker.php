<?php

namespace App\Services\Media;

use App\Models\Media;
use Throwable;
use ZipArchive;

/**
 * Podstawowe sprawdzenie dostępności dokumentów z biblioteki (PDF, DOCX).
 *
 * To nie jest pełna walidacja PDF/UA ani WCAG — wyłapuje braki, które najczęściej
 * wykluczają osoby korzystające z czytników ekranu i da się je łatwo poprawić
 * w programie, w którym powstał dokument:
 *  PDF:  tytuł, język, tagi struktury (dokument oznakowany), warstwa tekstowa (nie sam skan),
 *        wyświetlanie tytułu zamiast nazwy pliku, brak blokady treści;
 *  DOCX: tytuł we właściwościach, język, nagłówki ze stylów, teksty alternatywne obrazów.
 *
 * Wynik: ['status' => ok|warn|fail|error, 'checks' => [[key, label, ok, hint], …], 'checked_at' => …].
 */
class DocumentA11yChecker
{
    /** Większych plików nie wczytujemy w całości do pamięci. */
    private const MAX_BYTES = 40 * 1024 * 1024;

    public function check(Media $media): array
    {
        $path = $media->getPath();

        try {
            if (! is_file($path)) {
                return $this->result('error', [], 'Pliku nie ma na dysku.');
            }
            if (filesize($path) > self::MAX_BYTES) {
                return $this->result('error', [], 'Plik jest za duży do automatycznego sprawdzenia (ponad 40 MB).');
            }

            return match ($media->mime_type) {
                'application/pdf' => $this->pdf($path),
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => $this->docx($path),
                default => $this->result('error', [], 'Ten typ pliku nie jest sprawdzany.'),
            };
        } catch (Throwable $e) {
            return $this->result('error', [], 'Nie udało się odczytać pliku: ' . $e->getMessage());
        }
    }

    /** Sprawdza i zapisuje wynik na pliku. */
    public function checkAndStore(Media $media): array
    {
        $report = $this->check($media);
        $media->forceFill(['a11y_report' => $report, 'a11y_checked_at' => now()])->saveQuietly();

        return $report;
    }

    private function pdf(string $path): array
    {
        $raw = (string) file_get_contents($path);
        // Słownik katalogu (/Lang, /MarkInfo, /StructTreeRoot) bywa w skompresowanym strumieniu obiektów (PDF 1.5+).
        $haystack = $raw . "\n" . $this->inflatedObjectStreams($raw);

        if (preg_match('#/Encrypt\s+\d+\s+\d+\s+R#', $raw) && ! str_contains($haystack, '/StructTreeRoot')) {
            return $this->result('warn', [
                $this->item('encrypted', 'Dokument nie jest zaszyfrowany', false,
                    'Plik jest zaszyfrowany — nie da się go sprawdzić automatycznie, a zabezpieczenia mogą blokować czytniki ekranu. Zapisz go bez ograniczeń.'),
            ]);
        }

        $title = $this->pdfString($haystack, 'Title');
        if ($title === '' && preg_match('#<dc:title>.*?<rdf:li[^>]*>(.*?)</rdf:li>#s', $haystack, $m)) {
            $title = trim(strip_tags($m[1]));
        }
        $lang = $this->pdfString($haystack, 'Lang');
        $tagged = (bool) preg_match('#/MarkInfo\s*<<[^>]*?/Marked\s+true#s', $haystack) && str_contains($haystack, '/StructTreeRoot');
        $hasText = str_contains($haystack, '/Font');
        $displayTitle = (bool) preg_match('#/DisplayDocTitle\s+true#', $haystack);

        return $this->summarize([
            $this->item('title', 'Tytuł dokumentu', $title !== '',
                'Uzupełnij tytuł we właściwościach dokumentu (Plik → Właściwości) przed eksportem do PDF.', $title ?: null),
            $this->item('lang', 'Język dokumentu', $lang !== '',
                'Ustaw język dokumentu (np. polski) — bez tego czytnik ekranu czyta tekst z niewłaściwą wymową.', $lang ?: null),
            $this->item('tagged', 'Tagi struktury (PDF oznakowany)', $tagged,
                'Eksportuj z opcją „Dokument ze znacznikami” / „Tagged PDF” — bez tagów czytnik nie rozpozna nagłówków, list i tabel.'),
            $this->item('text', 'Warstwa tekstowa (nie sam skan)', $hasText,
                'Wygląda na skan bez tekstu. Przepuść plik przez OCR albo opublikuj wersję tekstową.'),
            $this->item('display_title', 'Wyświetlanie tytułu zamiast nazwy pliku', $displayTitle,
                'Opcjonalnie: w Acrobacie Właściwości → Widok początkowy → „Pokaż tytuł dokumentu”.', null, false),
        ]);
    }

    private function docx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return $this->result('error', [], 'Plik DOCX jest uszkodzony albo to nie jest dokument Worda.');
        }

        $core = (string) $zip->getFromName('docProps/core.xml');
        $styles = (string) $zip->getFromName('word/styles.xml');
        $settings = (string) $zip->getFromName('word/settings.xml');
        $document = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $title = preg_match('#<dc:title>(.*?)</dc:title>#s', $core, $m) ? trim(html_entity_decode(strip_tags($m[1]))) : '';
        $lang = preg_match('#<w:lang\b[^>]*w:val="([^"]+)"#', $styles . $settings, $m) ? $m[1] : '';

        // Nagłówki: style akapitu Heading1…/Nagwek1… (identyfikatory stylów różnią się między wersjami językowymi).
        $headings = preg_match_all('#<w:pStyle\s+w:val="(Heading|Nagwek|Naglowek|Titre|berschrift)\d#i', $document);
        $paragraphs = substr_count($document, '<w:p ') + substr_count($document, '<w:p>');

        // Obrazy: <wp:docPr …> bez niepustego descr (pomijamy oznaczone jako dekoracyjne).
        preg_match_all('#<wp:docPr\b([^>]*)>#', $document, $pics);
        $missingAlt = 0;
        foreach ($pics[1] as $attrs) {
            $decorative = str_contains($document, 'adec:decorative') && str_contains($attrs, 'decorative');
            if (! $decorative && ! preg_match('#\bdescr="[^"]*\S[^"]*"#', $attrs)) {
                $missingAlt++;
            }
        }
        $images = count($pics[1]);

        return $this->summarize([
            $this->item('title', 'Tytuł dokumentu', $title !== '',
                'Uzupełnij tytuł: Plik → Informacje → Właściwości → Tytuł.', $title ?: null),
            $this->item('lang', 'Język dokumentu', $lang !== '',
                'Ustaw język: Recenzja → Język → Ustaw język sprawdzania (dla całego tekstu).', $lang ?: null),
            $this->item('headings', 'Nagłówki ze stylów', $headings > 0 || $paragraphs < 15,
                'Oznacz tytuły sekcji stylami „Nagłówek 1”, „Nagłówek 2” — samo pogrubienie nie tworzy struktury dla czytnika ekranu.',
                $headings > 0 ? 'nagłówków: ' . $headings : null),
            $this->item('alt', 'Teksty alternatywne obrazów', $missingAlt === 0,
                'Dodaj tekst alternatywny (prawy przycisk na obrazie → Edytuj tekst alternatywny) albo oznacz obraz jako dekoracyjny.',
                $images ? ($missingAlt ? "bez opisu: {$missingAlt} z {$images}" : "obrazów: {$images}") : null),
        ]);
    }

    /**
     * Rozpakowana treść strumieni obiektów (/Type /ObjStm) — tam w nowszych PDF-ach
     * siedzi katalog dokumentu. Ograniczamy się do rozsądnej liczby i wielkości strumieni.
     */
    private function inflatedObjectStreams(string $raw): string
    {
        $out = '';
        $offset = 0;
        $found = 0;
        while ($found < 200 && ($pos = strpos($raw, '/ObjStm', $offset)) !== false) {
            $offset = $pos + 7;
            $start = strpos($raw, 'stream', $pos);
            if ($start === false) {
                break;
            }
            $start += 6;
            if (($raw[$start] ?? '') === "\r") {
                $start++;
            }
            if (($raw[$start] ?? '') === "\n") {
                $start++;
            }
            $end = strpos($raw, 'endstream', $start);
            if ($end === false || $end - $start > 5 * 1024 * 1024) {
                continue;
            }
            $data = @gzuncompress(substr($raw, $start, $end - $start));
            if ($data === false) {
                $data = @gzinflate(substr($raw, $start + 2, $end - $start - 2));
            }
            if (is_string($data)) {
                $out .= $data . "\n";
                $found++;
            }
            $offset = $end;
        }

        return $out;
    }

    /** Wartość tekstowa klucza PDF: /Klucz (tekst) albo /Klucz <hex> (UTF-16). */
    private function pdfString(string $pdf, string $key): string
    {
        if (preg_match('#/' . $key . '\s*\(((?:\\\\.|[^\\\\)])*)\)#s', $pdf, $m)) {
            $value = stripcslashes($m[1]);
            if (str_starts_with($value, "\xFE\xFF")) {
                $value = (string) mb_convert_encoding(substr($value, 2), 'UTF-8', 'UTF-16BE');
            }

            return trim($value);
        }
        if (preg_match('#/' . $key . '\s*<([0-9A-Fa-f\s]+)>#', $pdf, $m)) {
            $bin = (string) hex2bin(preg_replace('/\s+/', '', $m[1]));
            if (str_starts_with($bin, "\xFE\xFF")) {
                $bin = (string) mb_convert_encoding(substr($bin, 2), 'UTF-8', 'UTF-16BE');
            }

            return trim($bin);
        }

        return '';
    }

    private function item(string $key, string $label, bool $ok, string $hint, ?string $value = null, bool $required = true): array
    {
        return compact('key', 'label', 'ok', 'hint', 'value', 'required');
    }

    /** Status całości: fail, gdy brak wymaganego elementu; warn, gdy brak tylko zalecanego. */
    private function summarize(array $checks): array
    {
        $failed = collect($checks)->where('ok', false);
        $status = match (true) {
            $failed->where('required', true)->isNotEmpty() => 'fail',
            $failed->isNotEmpty() => 'warn',
            default => 'ok',
        };

        return $this->result($status, $checks);
    }

    private function result(string $status, array $checks, ?string $message = null): array
    {
        return ['status' => $status, 'checks' => $checks, 'message' => $message, 'checked_at' => now()->toIso8601String()];
    }
}
