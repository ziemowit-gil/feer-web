<?php

namespace App\Mcp\Tools;

/** Zwykły tekst z MCP → bezpieczny HTML (akapity rozdzielone pustą linią, znaczniki escapowane). */
class PlainText
{
    public static function toHtml(string $text): string
    {
        return collect(preg_split('/\R{2,}/', trim($text)))
            ->filter(fn ($p) => trim($p) !== '')
            ->map(fn ($p) => '<p>'.nl2br(e(trim($p))).'</p>')
            ->implode("\n");
    }
}
