<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Minimalny zapis arkusza XLSX (jeden arkusz, tekst i liczby, pogrubiony
 * nagłówek) bez zewnętrznych bibliotek — wystarcza do eksportu dla księgowości.
 * Tekst idzie jako inlineStr, więc nie trzeba tablicy sharedStrings.
 */
final class SimpleXlsx
{
    /**
     * @param  array<int, string>  $header
     * @param  iterable<array<int, string|int|float|null>>  $rows
     * @return string ścieżka pliku tymczasowego
     */
    public static function write(array $header, iterable $rows, string $sheetName = 'Arkusz1'): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Brak rozszerzenia PHP zip.');
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.self::esc(mb_substr($sheetName, 0, 31)).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            .'<cellXfs count="2"><xf fontId="0"/><xf fontId="1" applyFont="1"/></cellXfs>'
            .'</styleSheet>');

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $xml .= self::row(1, $header, true);
        $r = 2;
        foreach ($rows as $row) {
            $xml .= self::row($r++, $row, false);
        }
        $xml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();

        return $path;
    }

    private static function row(int $r, array $cells, bool $bold): string
    {
        $out = '<row r="'.$r.'">';
        foreach (array_values($cells) as $i => $value) {
            $ref = self::column($i).$r;
            $style = $bold ? ' s="1"' : '';
            if (is_int($value) || is_float($value)) {
                $out .= '<c r="'.$ref.'"'.$style.'><v>'.$value.'</v></c>';
            } else {
                $out .= '<c r="'.$ref.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'.self::esc((string) $value).'</t></is></c>';
            }
        }

        return $out.'</row>';
    }

    private static function column(int $i): string
    {
        $name = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $name = chr(65 + ($i - 1) % 26).$name;
        }

        return $name;
    }

    private static function esc(string $value): string
    {
        // Usuń znaki sterujące niedozwolone w XML.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
