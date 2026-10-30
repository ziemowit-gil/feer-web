<?php

namespace App\Mcp\Tools;

use App\Models\News;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Zwraca treść opublikowanej aktualności (tekst bez znaczników HTML) na podstawie sluga.')]
#[IsReadOnly]
class GetNewsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate(['slug' => ['required', 'string', 'max:255']]);

        $news = News::query()->published()->where('slug', $data['slug'])->first();

        if (! $news) {
            return Response::error('Nie znaleziono opublikowanej aktualności o tym slugu.');
        }

        return Response::json([
            'slug' => $news->slug,
            'title' => $news->title,
            'published_at' => $news->published_at?->toIso8601String(),
            'excerpt' => $news->excerpt,
            'text' => trim(html_entity_decode(strip_tags((string) $news->content))),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug aktualności.')->required(),
        ];
    }
}
