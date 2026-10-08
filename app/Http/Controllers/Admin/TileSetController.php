<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TileSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel admin (JSON): zestawy kafelków tworzone w kreatorze edytora treści.
 *
 * Metody: index(), show(), store(), update().
 */
class TileSetController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(TileSet::forCurrentSite()->orderBy('name')->get(['id', 'name']));
    }

    public function show(TileSet $tileSet): JsonResponse
    {
        abort_unless($tileSet->site_id === \App\Models\SiteSetting::current()->id, 404);

        return response()->json(['id' => $tileSet->id, 'name' => $tileSet->name, 'tiles' => $tileSet->tiles ?? []]);
    }

    public function store(Request $request): JsonResponse
    {
        $set = TileSet::create($this->validated($request) + ['created_by' => $request->user()?->id]);

        return response()->json(['id' => $set->id, 'name' => $set->name], 201);
    }

    public function update(Request $request, TileSet $tileSet): JsonResponse
    {
        abort_unless($tileSet->site_id === \App\Models\SiteSetting::current()->id, 404);
        $tileSet->update($this->validated($request));

        return response()->json(['id' => $tileSet->id, 'name' => $tileSet->name]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'tiles' => ['required', 'array', 'min:1', 'max:40'],
            'tiles.*.label' => ['required', 'string', 'max:120'],
            'tiles.*.url' => ['required', 'string', 'max:2048', 'regex:~^(https?://|mailto:|tel:|/|#)~i'],
            'tiles.*.icon' => ['nullable', 'string', 'max:100'],
            'tiles.*.color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tiles.*.cols' => ['nullable', 'integer', 'in:1,2,3'],
            'tiles.*.strip' => ['nullable', 'boolean'],
            'tiles.*.is_negative' => ['nullable', 'boolean'],
        ]);

        $data['tiles'] = collect($data['tiles'])->map(fn ($t) => [
            'label' => trim($t['label']),
            'url' => trim($t['url']),
            'icon' => trim((string) ($t['icon'] ?? '')) ?: 'bi-lightning',
            'image' => null,
            'color' => $t['color'] ?? null,
            'is_negative' => (bool) ($t['is_negative'] ?? false),
            'cols' => (int) ($t['cols'] ?? 1),
            'strip' => (bool) ($t['strip'] ?? false),
        ])->values()->all();

        return $data;
    }
}
