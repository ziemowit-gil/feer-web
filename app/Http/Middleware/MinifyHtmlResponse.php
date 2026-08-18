<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Minifikuje odpowiedzi HTML: usuwa komentarze, zbędne wcięcia i wielokrotne spacje.
 * Pomija streamy, odpowiedzi binarne i bloki <pre>/<textarea>/<script>/<style>.
 */
class MinifyHtmlResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $response instanceof StreamedResponse
            || ! str_contains($response->headers->get('Content-Type', ''), 'text/html')
        ) {
            return $response;
        }

        $html = $response->getContent();
        if ($html === false || strlen($html) === 0) {
            return $response;
        }

        $response->setContent($this->minify($html));

        return $response;
    }

    private function minify(string $html): string
    {
        // Wyciągnij i zastąp placeholderami bloki, których nie wolno ruszać.
        $preserved = [];
        $i = 0;

        $html = preg_replace_callback(
            '#(<(pre|textarea|script|style)[^>]*>)(.*?)(</\2>)#si',
            function (array $m) use (&$preserved, &$i): string {
                $key = "\x02PRESERVE_{$i}\x03";
                $preserved[$key] = $m[0];
                $i++;
                return $key;
            },
            $html
        );

        // Usuń komentarze HTML (poza warunkowymi IE i no-js).
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+\]|<!\[endif\]|--))\s*.*?\s*-->/si', '', $html);

        // Usuń wcięcia i wielokrotne białe znaki między tagami.
        $html = preg_replace('/>\s{2,}</s', '><', $html);

        // Spakuj wielokrotne spacje/taby/newline poza tagami do jednej spacji.
        $html = preg_replace('/\s{2,}/', ' ', $html);

        // Przywróć zachowane bloki.
        return strtr($html, $preserved);
    }
}
