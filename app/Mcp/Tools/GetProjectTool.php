<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Zwraca treść opublikowanego działania (tekst bez znaczników HTML) na podstawie sluga.')]
#[IsReadOnly]
class GetProjectTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate(['slug' => ['required', 'string', 'max:255']]);

        $project = Project::query()->where('is_published', true)->where('slug', $data['slug'])->first();

        if (! $project) {
            return Response::error('Nie znaleziono opublikowanego działania o tym slugu.');
        }

        return Response::json([
            'slug' => $project->slug,
            'title' => $project->title,
            'url' => url('/dzialania/'.$project->slug),
            'excerpt' => $project->excerpt,
            'for_whom' => $project->for_whom,
            'text' => trim(html_entity_decode(strip_tags((string) $project->content))),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug działania.')->required(),
        ];
    }
}
