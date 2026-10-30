<?php

namespace App\Mcp\Tools;

use App\Models\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Zwraca treść opublikowanej strony (tekst bez znaczników HTML) na podstawie jej sluga.')]
#[IsReadOnly]
class GetPageTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate(['slug' => ['required', 'string', 'max:255']]);

        $page = Page::query()->live()->where('slug', $data['slug'])->first();

        if (! $page) {
            return Response::error('Nie znaleziono opublikowanej strony o tym slugu.');
        }

        return Response::json([
            'slug' => $page->slug,
            'title' => $page->title,
            'url' => url('/'.$page->slug),
            'meta_description' => $page->meta_description,
            'text' => trim(html_entity_decode(strip_tags((string) $page->content))),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug strony, np. „fundacja”.')->required(),
        ];
    }
}
