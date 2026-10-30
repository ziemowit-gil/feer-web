<?php

namespace App\Mcp\Tools;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Tworzy szkic działania (nieopublikowany) w pierwszej kategorii. Treść jako zwykły tekst — akapity rozdziel pustą linią. Publikacja wyłącznie w panelu.')]
class CreateProjectDraftTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('mcp:write')) {
            return Response::error('Token nie ma uprawnienia mcp:write.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:50000'],
        ]);

        $category = Category::orderBy('id')->first();
        if (! $category) {
            return Response::error('Brak kategorii działań — dodaj kategorię w panelu.');
        }

        $base = Str::slug($data['title']) ?: 'dzialanie';
        $slug = $base;
        for ($i = 2; Project::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        $project = Project::create([
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => PlainText::toHtml($data['content']),
            'category_id' => $category->id,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        return Response::json([
            'id' => $project->id,
            'slug' => $project->slug,
            'status' => 'szkic (nieopublikowany)',
            'edit_url' => route('admin.projekty.edit', $project),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Tytuł działania.')->required(),
            'excerpt' => $schema->string()->description('Krótka zajawka (do 255 znaków).'),
            'content' => $schema->string()->description('Treść jako zwykły tekst; akapity rozdziel pustą linią.')->required(),
        ];
    }
}
