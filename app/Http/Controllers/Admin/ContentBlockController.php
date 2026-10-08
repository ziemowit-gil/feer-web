<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel admin (JSON): bloki treści z kreatora w edytorze — przyciski CTA i akordeon.
 *
 * Metody: index(), show(), store(), update().
 */
class ContentBlockController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(ContentBlock::forCurrentSite()->orderBy('name')->get(['id', 'type', 'name']));
    }

    public function show(ContentBlock $block): JsonResponse
    {
        abort_unless($block->site_id === SiteSetting::current()->id, 404);

        return response()->json(['id' => $block->id, 'type' => $block->type, 'name' => $block->name, 'data' => $block->data ?? []]);
    }

    public function store(Request $request): JsonResponse
    {
        $block = ContentBlock::create($this->validated($request) + ['created_by' => $request->user()?->id]);

        return response()->json(['id' => $block->id, 'type' => $block->type, 'name' => $block->name], 201);
    }

    public function update(Request $request, ContentBlock $block): JsonResponse
    {
        abort_unless($block->site_id === SiteSetting::current()->id, 404);
        $payload = $this->validated($request, $block->type);
        $block->update($payload);

        return response()->json(['id' => $block->id, 'type' => $block->type, 'name' => $block->name]);
    }

    private function validated(Request $request, ?string $fixedType = null): array
    {
        $type = $fixedType ?? $request->input('type');
        abort_unless(array_key_exists((string) $type, ContentBlock::TYPES), 422, 'Nieznany typ bloku.');

        $request->validate(['name' => ['required', 'string', 'max:120']]);

        if ($type === 'callout') {
            $d = $request->validate([
                'data.title' => ['nullable', 'string', 'max:160'],
                'data.text' => ['required', 'string', 'max:1500'],
                'data.variant' => ['required', 'in:'.implode(',', array_keys(ContentBlock::CALLOUT_VARIANTS))],
                'data.negative' => ['nullable', 'boolean'],
                'data.icon' => ['required', 'in:'.implode(',', array_keys(ContentBlock::CALLOUT_ICONS))],
            ])['data'];
            $data = [
                'title' => trim((string) ($d['title'] ?? '')),
                'text' => trim($d['text']),
                'variant' => $d['variant'],
                'negative' => (bool) ($d['negative'] ?? false),
                'icon' => $d['icon'],
            ];
        } elseif ($type === 'cta') {
            $d = $request->validate([
                'data.align' => ['nullable', 'in:left,center,right'],
                'data.buttons' => ['required', 'array', 'min:1', 'max:6'],
                'data.buttons.*.label' => ['required', 'string', 'max:80'],
                'data.buttons.*.url' => ['required', 'string', 'max:2048', 'regex:~^(https?://|mailto:|tel:|/|#)~i'],
                'data.buttons.*.color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'data.buttons.*.filled' => ['nullable', 'boolean'],
                'data.buttons.*.new_tab' => ['nullable', 'boolean'],
            ])['data'];
            $data = [
                'align' => $d['align'] ?? 'left',
                'buttons' => collect($d['buttons'])->map(fn ($b) => [
                    'label' => trim($b['label']), 'url' => trim($b['url']), 'color' => $b['color'] ?? '#1e6dff',
                    'filled' => (bool) ($b['filled'] ?? true), 'new_tab' => (bool) ($b['new_tab'] ?? false),
                ])->values()->all(),
            ];
        } else {
            $d = $request->validate([
                'data.title' => ['nullable', 'string', 'max:160'],
                'data.exclusive' => ['nullable', 'boolean'],
                'data.first_open' => ['nullable', 'boolean'],
                'data.items' => ['required', 'array', 'min:1', 'max:40'],
                'data.items.*.q' => ['required', 'string', 'max:300'],
                'data.items.*.a' => ['required', 'string', 'max:5000'],
            ])['data'];
            $data = [
                'title' => trim((string) ($d['title'] ?? '')),
                'exclusive' => (bool) ($d['exclusive'] ?? false),
                'first_open' => (bool) ($d['first_open'] ?? false),
                'items' => collect($d['items'])->map(fn ($i) => ['q' => trim($i['q']), 'a' => trim($i['a'])])->values()->all(),
            ];
        }

        return ['type' => $type, 'name' => trim($request->input('name')), 'data' => $data];
    }
}
