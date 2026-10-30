<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Szuka opublikowanych działań (projektów) po tytule, zajawce i treści. Bez frazy zwraca najnowsze.')]
#[IsReadOnly]
class SearchProjectsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'query' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $projects = Project::query()->where('is_published', true)
            ->when($data['query'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('content', 'like', $like));
            })
            ->orderByDesc('id')
            ->limit($data['limit'] ?? 10)
            ->get(['id', 'title', 'slug', 'excerpt']);

        return Response::json($projects->map(fn (Project $p) => [
            'slug' => $p->slug,
            'title' => $p->title,
            'excerpt' => $p->excerpt,
            'url' => url('/dzialania/'.$p->slug),
        ])->all());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Szukana fraza.'),
            'limit' => $schema->integer()->description('Maksymalna liczba wyników (1–25, domyślnie 10).'),
        ];
    }
}
