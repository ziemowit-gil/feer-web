<?php

namespace Modules\Blog\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Markdown → HTML dla treści Wiem FEER (zapis wpisu i podgląd w panelu — ten sam kod).
 *
 * CommonMark + z GFM: tabele, przekreślenie, autolinki (bez DisallowedRawHtml, żeby
 * osadzone ramki wideo z dawnych wpisów przetrwały). Surowy HTML jest dozwolony, bo
 * piszą wyłącznie zaufani redaktorzy — ale i tak usuwamy to, co wykonuje skrypty:
 * <script> i podobne elementy, atrybuty on*, srcdoc oraz adresy javascript:/vbscript:/
 * data: (poza obrazami data:image). Nagłówki kolumn tabel dostają scope="col" (WCAG 1.3.1).
 */
final class MarkdownRenderer
{
    private const DROP_ELEMENTS = ['script', 'noscript', 'object', 'embed', 'applet', 'base', 'meta', 'link', 'frame', 'frameset', 'template'];

    private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'xlink:href', 'poster', 'background', 'data', 'cite'];

    private static ?MarkdownConverter $converter = null;

    public static function toHtml(?string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", (string) $markdown);
        if (trim($markdown) === '') {
            return '';
        }

        $html = (string) self::converter()->convert($markdown);

        return self::sanitize($html);
    }

    /** Liczba słów i szacowany czas czytania (jak BlogArticle::readingMinutes()). */
    public static function stats(string $html): array
    {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);

        return ['words' => $words, 'minutes' => max(1, (int) ceil($words / \Modules\Blog\Models\BlogArticle::WORDS_PER_MINUTE))];
    }

    private static function converter(): MarkdownConverter
    {
        if (self::$converter === null) {
            $environment = new Environment([
                'html_input'         => 'allow',
                'allow_unsafe_links' => false,
                'max_nesting_level'  => 50,
                'table'              => ['wrap' => ['enabled' => false]],
            ]);
            $environment->addExtension(new CommonMarkCoreExtension());
            $environment->addExtension(new TableExtension());
            $environment->addExtension(new StrikethroughExtension());
            $environment->addExtension(new AutolinkExtension());

            self::$converter = new MarkdownConverter($environment);
        }

        return self::$converter;
    }

    /** Usuwa elementy i atrybuty wykonujące skrypty; reszta HTML zostaje bez zmian. */
    public static function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="md-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('md-root');
        if (! $root) {
            return e(strip_tags($html));
        }

        self::walk($root);

        foreach ((new DOMXPath($doc))->query('//thead//th[not(@scope)]', $root) ?: [] as $th) {
            /** @var DOMElement $th */
            $th->setAttribute('scope', 'col');
        }

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->tagName), self::DROP_ELEMENTS, true)) {
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                if (str_starts_with($name, 'on') || $name === 'srcdoc' || $name === 'formaction') {
                    $child->removeAttribute($attr->nodeName);

                    continue;
                }
                if (in_array($name, self::URL_ATTRIBUTES, true) && self::isDangerousUrl($attr->nodeValue, $name)) {
                    $child->removeAttribute($attr->nodeName);
                }
            }

            self::walk($child);
        }
    }

    private static function isDangerousUrl(?string $url, string $attribute): bool
    {
        // Usuń znaki sterujące i białe znaki, którymi da się „przemycić" schemat (java\tscript:).
        $normalized = strtolower(preg_replace('/[\x00-\x20\x7f]+/', '', html_entity_decode((string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if (preg_match('/^(javascript|vbscript|livescript):/', $normalized)) {
            return true;
        }

        if (str_starts_with($normalized, 'data:')) {
            return ! ($attribute === 'src' && preg_match('#^data:image/(png|jpe?g|gif|webp|avif);#', $normalized));
        }

        return false;
    }
}
