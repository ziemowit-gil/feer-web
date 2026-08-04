<?php

namespace App\Services;

use App\Models\WcagScan;
use DOMDocument;
use DOMXPath;

class WcagScannerService
{
    private array $issues = [];

    public function scan(string $url): WcagScan
    {
        $this->issues = [];

        $html = $this->fetchHtml($url);

        if ($html === null) {
            return WcagScan::updateOrCreate(['url' => $url], [
                'page_title'  => null,
                'issues'      => [['level' => 'error', 'code' => 'FETCH_FAILED', 'message' => 'Nie udało się pobrać strony.', 'context' => '']],
                'issue_count' => 1,
                'scanned_at'  => now(),
            ]);
        }

        $doc  = $this->parseHtml($html);
        $xpath = new DOMXPath($doc);

        $title = $this->extractTitle($xpath);

        $this->checkLangAttribute($doc);
        $this->checkTitleElement($xpath);
        $this->checkImageAlts($xpath);
        $this->checkLinkTexts($xpath);
        $this->checkInputLabels($xpath);
        $this->checkHeadingHierarchy($xpath);
        $this->checkColorContrast($xpath);
        $this->checkFormButtons($xpath);
        $this->checkTabindex($xpath);
        $this->checkAriaRoles($xpath);

        return WcagScan::updateOrCreate(['url' => $url], [
            'page_title'  => $title,
            'issues'      => $this->issues,
            'issue_count' => count($this->issues),
            'scanned_at'  => now(),
        ]);
    }

    private function fetchHtml(string $url): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout'          => 15,
                'follow_location'  => 1,
                'max_redirects'    => 5,
                'user_agent'       => 'FEER-WCAG-Scanner/1.0 (dostepnosc@feer.org.pl)',
                'ignore_errors'    => true,
            ],
        ]);

        $html = @file_get_contents($url, false, $ctx);

        return $html !== false ? $html : null;
    }

    private function parseHtml(string $html): DOMDocument
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        return $doc;
    }

    private function extractTitle(DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//title');

        return $nodes->length ? trim($nodes->item(0)->textContent) : null;
    }

    private function checkLangAttribute(DOMDocument $doc): void
    {
        $html = $doc->getElementsByTagName('html')->item(0);

        if (!$html || !$html->hasAttribute('lang') || trim($html->getAttribute('lang')) === '') {
            $this->add('error', 'MISSING_LANG', 'Element <html> nie ma atrybutu lang — wymagane przez WCAG 3.1.1.', '<html>');
        }
    }

    private function checkTitleElement(DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//title');

        if ($nodes->length === 0 || trim($nodes->item(0)->textContent) === '') {
            $this->add('error', 'MISSING_TITLE', 'Strona nie ma elementu <title> lub jest pusty — wymagane przez WCAG 2.4.2.', '<title>');
        }
    }

    private function checkImageAlts(DOMXPath $xpath): void
    {
        $images = $xpath->query('//img');

        foreach ($images as $img) {
            if (!$img->hasAttribute('alt')) {
                $src = $img->getAttribute('src') ?? '';
                $this->add('error', 'IMG_NO_ALT', 'Obraz bez atrybutu alt — wymagane przez WCAG 1.1.1.', '<img src="' . $this->truncate($src) . '">');
            } elseif (trim($img->getAttribute('alt')) !== '' && preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $img->getAttribute('alt'))) {
                $this->add('warning', 'IMG_FILENAME_ALT', 'Atrybut alt obrazu wygląda jak nazwa pliku — sprawdź czy jest opisowy.', '<img alt="' . $this->truncate($img->getAttribute('alt')) . '">');
            }
        }
    }

    private function checkLinkTexts(DOMXPath $xpath): void
    {
        $links = $xpath->query('//a[@href]');

        foreach ($links as $link) {
            $text = trim($link->textContent);
            $ariaLabel = $link->getAttribute('aria-label');
            $title = $link->getAttribute('title');
            $hasImg = $xpath->query('.//img[@alt!=""]', $link)->length > 0;

            if ($text === '' && $ariaLabel === '' && $title === '' && !$hasImg) {
                $href = $link->getAttribute('href') ?? '';
                $this->add('error', 'LINK_NO_TEXT', 'Łącze bez dostępnej nazwy (brak tekstu, aria-label, title i obrazu z alt) — wymagane przez WCAG 2.4.4.', '<a href="' . $this->truncate($href) . '">');
            } elseif (in_array(strtolower($text), ['kliknij tutaj', 'tutaj', 'więcej', 'czytaj', 'link', 'kliknij'], true)) {
                $this->add('warning', 'LINK_GENERIC_TEXT', 'Łącze z nieopisowym tekstem („' . $text . '") — utrudnia nawigację czytnikiem ekranu.', '<a>' . $this->truncate($text) . '</a>');
            }
        }
    }

    private function checkInputLabels(DOMXPath $xpath): void
    {
        $inputs = $xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="image") and not(@type="reset")] | //textarea | //select');

        foreach ($inputs as $input) {
            $id          = $input->getAttribute('id');
            $ariaLabel   = $input->getAttribute('aria-label');
            $ariaLabelBy = $input->getAttribute('aria-labelledby');
            $title       = $input->getAttribute('title');
            $placeholder = $input->getAttribute('placeholder');

            $hasLabel = false;
            if ($id !== '') {
                $labels = $xpath->query('//label[@for="' . $id . '"]');
                $hasLabel = $labels->length > 0;
            }

            if (!$hasLabel && $ariaLabel === '' && $ariaLabelBy === '' && $title === '') {
                $tag  = $input->nodeName;
                $name = $input->getAttribute('name') ?? '';
                if ($placeholder !== '') {
                    $this->add('warning', 'INPUT_PLACEHOLDER_ONLY', 'Pole formularza używa tylko placeholder jako etykiety — placeholder znika podczas pisania (WCAG 1.3.1).', '<' . $tag . ' name="' . $this->truncate($name) . '">');
                } else {
                    $this->add('error', 'INPUT_NO_LABEL', 'Pole formularza bez dostępnej etykiety — wymagane przez WCAG 1.3.1.', '<' . $tag . ' name="' . $this->truncate($name) . '">');
                }
            }
        }
    }

    private function checkHeadingHierarchy(DOMXPath $xpath): void
    {
        $h1s = $xpath->query('//h1');

        if ($h1s->length === 0) {
            $this->add('warning', 'MISSING_H1', 'Strona nie ma nagłówka h1 — utrudnia orientację w treści (WCAG 2.4.6).', '');
        } elseif ($h1s->length > 1) {
            $this->add('warning', 'MULTIPLE_H1', 'Strona ma więcej niż jeden nagłówek h1 (' . $h1s->length . ') — zalecany tylko jeden nagłówek główny.', '');
        }

        $headings = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');
        $prevLevel = 0;

        foreach ($headings as $h) {
            $level = (int) substr($h->nodeName, 1);
            if ($prevLevel > 0 && $level > $prevLevel + 1) {
                $text = $this->truncate(trim($h->textContent));
                $this->add('warning', 'HEADING_SKIP', 'Pominięto poziom nagłówka: h' . $prevLevel . ' → h' . $level . ' (WCAG 1.3.1).', '<' . $h->nodeName . '>' . $text . '</' . $h->nodeName . '>');
            }
            $prevLevel = $level;
        }
    }

    private function checkColorContrast(DOMXPath $xpath): void
    {
        // Static check: warn about elements that commonly have contrast issues
        $smallGrayTexts = $xpath->query('//*[contains(@class,"text-gray-400") or contains(@class,"text-gray-300") or contains(@class,"text-slate-400")]');

        foreach ($smallGrayTexts as $el) {
            if (strlen(trim($el->textContent)) > 0) {
                $this->add('warning', 'LOW_CONTRAST_CANDIDATE', 'Element może mieć niewystarczający kontrast (jasny szary tekst) — zweryfikuj ręcznie (WCAG 1.4.3).', '<' . $el->nodeName . ' class="' . $this->truncate($el->getAttribute('class')) . '">');
                break; // one warning per page is enough for this heuristic
            }
        }
    }

    private function checkFormButtons(DOMXPath $xpath): void
    {
        $buttons = $xpath->query('//button[not(@type)] | //input[@type="submit"][not(@value) or @value=""]');

        foreach ($buttons as $btn) {
            $text = trim($btn->textContent);
            $ariaLabel = $btn->getAttribute('aria-label');
            if ($text === '' && $ariaLabel === '') {
                $this->add('error', 'BUTTON_NO_TEXT', 'Przycisk bez dostępnej nazwy — wymagane przez WCAG 4.1.2.', '<' . $btn->nodeName . '>');
            }
        }
    }

    private function checkTabindex(DOMXPath $xpath): void
    {
        $positives = $xpath->query('//*[@tabindex and @tabindex > 0]');

        if ($positives->length > 0) {
            $this->add('warning', 'POSITIVE_TABINDEX', 'Znaleziono tabindex > 0 (' . $positives->length . ' elementów) — zaburza naturalną kolejność fokusa (WCAG 2.4.3).', '');
        }
    }

    private function checkAriaRoles(DOMXPath $xpath): void
    {
        $landmarks = $xpath->query('//nav | //main | //header | //footer');

        if ($xpath->query('//main | //*[@role="main"]')->length === 0) {
            $this->add('warning', 'MISSING_MAIN_LANDMARK', 'Brak elementu <main> lub role="main" — czytniki ekranu nie mogą szybko dotrzeć do treści głównej (WCAG 2.4.1).', '');
        }

        $navs = $xpath->query('//nav[not(@aria-label) and not(@aria-labelledby)]');
        if ($navs->length > 1) {
            $this->add('warning', 'UNLABELED_NAVS', 'Wiele elementów <nav> bez aria-label — użytkownicy czytników nie rozróżnią nawigacji (WCAG 1.3.1).', '');
        }
    }

    private function add(string $level, string $code, string $message, string $context): void
    {
        $this->issues[] = compact('level', 'code', 'message', 'context');
    }

    private function truncate(string $text, int $max = 80): string
    {
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) . '…' : $text;
    }
}
