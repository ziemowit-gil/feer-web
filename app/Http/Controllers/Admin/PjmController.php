<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PjmVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Panel admin: wideo z tłumaczeniem na PJM przy aktualności lub stronie
 * (osobny formularz pod treścią, jak ETR — nie dotyka treści ani bloków).
 */
class PjmController extends Controller
{
    private const ALLOWED_TYPES = [
        'news' => \App\Models\News::class,
        'podstrona' => \App\Models\Page::class,
    ];

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(array_key_exists($type, self::ALLOWED_TYPES), 404);
        $model = self::ALLOWED_TYPES[$type]::findOrFail($id);

        $data = $request->validate([
            'pjm_video_url' => ['nullable', 'url', 'max:500', function ($attribute, $value, $fail) {
                if (filled($value) && ! PjmVideo::isValid($value)) {
                    $fail('Podaj link do filmu na YouTube lub Vimeo albo adres https pliku MP4/WebM.');
                }
            }],
        ]);

        $model->forceFill(['pjm_video_url' => $data['pjm_video_url'] ?: null])->save();

        return back()->with('status', $data['pjm_video_url'] ? 'Zapisano tłumaczenie na PJM.' : 'Usunięto tłumaczenie na PJM.');
    }
}
