<?php

namespace App\Mcp\Tools;

use App\Models\News;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lista najnowszych opublikowanych aktualności (tytuł, slug, data, zajawka), opcjonalnie filtrowana frazą.')]
#[IsReadOnly]
class ListNewsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'query' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $news = News::query()->published()
            ->when($data['query'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like));
            })
            ->orderByDesc('published_at')
            ->limit($data['limit'] ?? 10)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at']);

        return Response::json($news->map(fn (News $n) => [
            'slug' => $n->slug,
            'title' => $n->title,
            'excerpt' => $n->excerpt,
            'published_at' => $n->published_at?->toIso8601String(),
        ])->all());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Fraza szukana w tytule i zajawce.'),
            'limit' => $schema->integer()->description('Maksymalna liczba wyników (1–25, domyślnie 10).'),
        ];
    }
}
