<?php

namespace App\Mcp\Tools;

use App\Models\Page;
use App\Support\UniqueSlug;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Tworzy szkic strony (podstrony) — nieopublikowany, ukryty w menu. Opcjonalnie pod stroną nadrzędną (parent_slug). Treść jako zwykły tekst. Publikacja wyłącznie w panelu.')]
class CreatePageDraftTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('mcp:write')) {
            return Response::error('Token nie ma uprawnienia mcp:write.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'content' => ['required', 'string', 'max:50000'],
            'parent_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $parent = null;
        if (filled($data['parent_slug'] ?? null)) {
            $parent = Page::where('slug', $data['parent_slug'])->first();
            if (! $parent) {
                return Response::error('Nie znaleziono strony nadrzędnej o tym slugu.');
            }
        }

        $page = Page::create([
            'title' => $data['title'],
            'slug' => UniqueSlug::page($data['title']),
            'content' => PlainText::toHtml($data['content']),
            'parent_id' => $parent?->id,
            'is_published' => false,
            'show_in_menu' => false,
            'created_by' => $user->id,
        ]);

        return Response::json([
            'id' => $page->id,
            'slug' => $page->slug,
            'status' => 'szkic (nieopublikowany)',
            'edit_url' => route('admin.podstrony.edit', $page),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Tytuł strony.')->required(),
            'content' => $schema->string()->description('Treść jako zwykły tekst; akapity rozdziel pustą linią.')->required(),
            'parent_slug' => $schema->string()->description('Slug strony nadrzędnej (opcjonalnie).'),
        ];
    }
}
