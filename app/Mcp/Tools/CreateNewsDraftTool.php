<?php

namespace App\Mcp\Tools;

use App\Models\News;
use App\Support\UniqueSlug;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Tworzy szkic aktualności (nieopublikowany). Treść podaj jako zwykły tekst — akapity rozdziel pustą linią. Publikacja odbywa się wyłącznie w panelu.')]
class CreateNewsDraftTool extends Tool
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

        $html = collect(preg_split('/\R{2,}/', trim($data['content'])))
            ->filter(fn ($p) => trim($p) !== '')
            ->map(fn ($p) => '<p>'.nl2br(e(trim($p))).'</p>')
            ->implode("\n");

        $news = News::create([
            'title' => $data['title'],
            'slug' => UniqueSlug::news($data['title']),
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $html,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        return Response::json([
            'id' => $news->id,
            'slug' => $news->slug,
            'status' => 'szkic (nieopublikowany)',
            'edit_url' => route('admin.newsy.edit', $news),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Tytuł aktualności.')->required(),
            'excerpt' => $schema->string()->description('Krótka zajawka (do 255 znaków).'),
            'content' => $schema->string()->description('Treść jako zwykły tekst; akapity rozdziel pustą linią.')->required(),
        ];
    }
}
