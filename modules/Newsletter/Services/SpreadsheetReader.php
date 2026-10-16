<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Odczyt pierwszego arkusza pliku Excel (.xlsx) lub OpenDocument (.ods) bez zewnętrznych
 * bibliotek — strumieniowo (ZipArchive + XMLReader), więc duże pliki nie zjadają pamięci.
 * Arkusz zamieniamy na CSV i dalej importujemy jak zwykły plik CSV.
 */
class SpreadsheetReader
{
    public const EXTENSIONS = ['xlsx', 'ods'];

    /** Zapisuje pierwszy arkusz jako CSV (UTF-8, separator „;”) do `$target`. */
    public function toCsv(string $source, string $extension, string $target): void
    {
        $zip = new ZipArchive;
        if ($zip->open($source) !== true) {
            throw new RuntimeException('Plik nie jest poprawnym arkuszem '.strtoupper($extension).'. Zapisz go w Excelu jako .xlsx albo wyeksportuj do CSV.');
        }

        $out = fopen($target, 'w');
        try {
            $rows = $extension === 'ods' ? $this->odsRows($zip) : $this->xlsxRows($zip);
            $written = 0;
            foreach ($rows as $row) {
                if (array_filter($row, fn ($v) => $v !== '' && $v !== null) === []) {
                    continue;
                }
                fputcsv($out, $row, ';', '"', '');
                $written++;
            }
            if ($written === 0) {
                throw new RuntimeException('Pierwszy arkusz pliku jest pusty.');
            }
        } finally {
            fclose($out);
            $zip->close();
        }
    }

    // ── XLSX ────────────────────────────────────────────────────────────────

    /** @return \Generator<int, array<int, string>> */
    private function xlsxRows(ZipArchive $zip): \Generator
    {
        $strings = $this->xlsxSharedStrings($zip);
        $sheet = $this->xlsxFirstSheetPath($zip);
        $xml = $zip->getFromName($sheet);
        if ($xml === false) {
            throw new RuntimeException('W pliku Excel nie ma arkusza z danymi.');
        }

        $reader = new XMLReader;
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }
            $node = $reader->expand();
            $row = [];
            foreach ($node->childNodes as $c) {
                if ($c->nodeName !== 'c' && $c->localName !== 'c') {
                    continue;
                }
                $index = $this->columnIndex((string) $c->getAttribute('r'), count($row));
                $type = (string) $c->getAttribute('t');
                $value = '';
                foreach ($c->childNodes as $child) {
                    if ($child->localName === 'v') {
                        $value = $child->textContent;
                    } elseif ($child->localName === 'is') {
                        $value = $child->textContent;
                    }
                }
                if ($type === 's') {
                    $value = $strings[(int) $value] ?? '';
                } elseif ($type === 'b') {
                    $value = $value === '1' ? 'TRUE' : 'FALSE';
                }
                $row = $this->place($row, $index, trim($value));
            }
            yield $row;
        }
        $reader->close();
    }

    /** @return array<int, string> */
    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $strings = [];
        $reader = new XMLReader;
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $node = $reader->expand();
                // Tekst z formatowaniem (<r><t>…) — sklejamy wszystkie <t>, pomijając fonetykę (<rPh>).
                $text = '';
                foreach ($node->getElementsByTagName('t') as $t) {
                    if ($t->parentNode?->localName !== 'rPh') {
                        $text .= $t->textContent;
                    }
                }
                $strings[] = $text;
            }
        }
        $reader->close();

        return $strings;
    }

    private function xlsxFirstSheetPath(ZipArchive $zip): string
    {
        $workbook = (string) $zip->getFromName('xl/workbook.xml');
        $rels = (string) $zip->getFromName('xl/_rels/workbook.xml.rels');
        if (preg_match('/<(?:\w+:)?sheet\b[^>]*\br:id="([^"]+)"/', $workbook, $m) && preg_match_all('/<(?:\w+:)?Relationship\b[^>]*>/', $rels, $tags)) {
            foreach ($tags[0] as $tag) {
                if (preg_match('/\bId="([^"]+)"/', $tag, $id) && $id[1] === $m[1] && preg_match('/\bTarget="([^"]+)"/', $tag, $t)) {
                    $target = ltrim($t[1], '/');

                    return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /** „C7” → 2; bez adresu — następna kolumna. */
    private function columnIndex(string $ref, int $next): int
    {
        if (! preg_match('/^([A-Z]+)/', $ref, $m)) {
            return $next;
        }
        $n = 0;
        foreach (str_split($m[1]) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }

    // ── ODS ─────────────────────────────────────────────────────────────────

    /** @return \Generator<int, array<int, string>> */
    private function odsRows(ZipArchive $zip): \Generator
    {
        $xml = $zip->getFromName('content.xml');
        if ($xml === false) {
            throw new RuntimeException('W pliku ODS nie ma arkusza z danymi.');
        }
        $office = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
        $table = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';

        $reader = new XMLReader;
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        $inTable = false;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'table' && $reader->namespaceURI === $table) {
                if ($inTable) {
                    break; // tylko pierwszy arkusz
                }
                $inTable = true;

                continue;
            }
            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'table' && $reader->namespaceURI === $table) {
                break;
            }
            if (! $inTable || $reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'table-row') {
                continue;
            }
            $node = $reader->expand();
            $row = [];
            foreach ($node->childNodes as $cell) {
                if (! in_array($cell->localName, ['table-cell', 'covered-table-cell'], true)) {
                    continue;
                }
                $repeat = max(1, (int) $cell->getAttributeNS($table, 'number-columns-repeated'));
                $value = match ($cell->getAttributeNS($office, 'value-type')) {
                    'date' => (string) $cell->getAttributeNS($office, 'date-value'),
                    'float', 'percentage', 'currency' => (string) $cell->getAttributeNS($office, 'value'),
                    'boolean' => $cell->getAttributeNS($office, 'boolean-value') === 'true' ? 'TRUE' : 'FALSE',
                    default => implode("\n", array_map(fn ($p) => $p->textContent, iterator_to_array($cell->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'p')))),
                };
                $value = trim($value);
                // Puste komórki powtarzane do końca arkusza (np. 1000×) pomijamy.
                if ($value === '' && $repeat > 1) {
                    $row = $this->place($row, count($row) + $repeat - 1, '');

                    continue;
                }
                for ($i = 0; $i < min($repeat, 100); $i++) {
                    $row[] = $value;
                }
            }
            $row = $this->trimRight($row);
            $times = max(1, (int) $node->getAttributeNS($table, 'number-rows-repeated'));
            if ($row === []) {
                continue;
            }
            for ($i = 0; $i < min($times, 1000); $i++) {
                yield $row;
            }
        }
        $reader->close();
    }

    // ── Pomocnicze ──────────────────────────────────────────────────────────

    /** @param array<int, string> $row */
    private function place(array $row, int $index, string $value): array
    {
        for ($i = count($row); $i < $index; $i++) {
            $row[$i] = '';
        }
        $row[$index] = $value;

        return $row;
    }

    /** @param array<int, string> $row */
    private function trimRight(array $row): array
    {
        while ($row !== [] && end($row) === '') {
            array_pop($row);
        }

        return $row;
    }
}
