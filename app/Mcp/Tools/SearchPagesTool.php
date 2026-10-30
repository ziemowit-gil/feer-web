<?php

namespace App\Mcp\Tools;

use App\Models\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Szuka opublikowanych stron (w panelu: Strony) po tytule i treści. Zwraca slug, tytuł i adres.')]
#[IsReadOnly]
class SearchPagesTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $term = '%'.addcslashes($data['query'], '%_\\').'%';

        $pages = Page::query()->live()
            ->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('content', 'like', $term))
            ->orderBy('title')
            ->limit($data['limit'] ?? 10)
            ->get(['id', 'title', 'slug', 'type']);

        return Response::json($pages->map(fn (Page $p) => [
            'slug' => $p->slug,
            'title' => $p->title,
            'type' => $p->type,
            'url' => url('/'.$p->slug),
        ])->all());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Szukana fraza (min. 2 znaki).')->required(),
            'limit' => $schema->integer()->description('Maksymalna liczba wyników (1–25, domyślnie 10).'),
        ];
    }
}
