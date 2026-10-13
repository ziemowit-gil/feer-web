<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ContentReview;
use Illuminate\Http\Request;

/**
 * Panel admin: przegląd treści — opublikowane strony, aktualności i działania bez zmian od N miesięcy;
 * „Sprawdzone, aktualne” odnotowuje przegląd bez zmiany treści.
 */
class ContentReviewController extends Controller
{
    public const MONTHS = [6, 12, 24];

    public function index(Request $request)
    {
        $months = in_array($request->integer('miesiace'), self::MONTHS, true) ? $request->integer('miesiace') : ContentReview::DEFAULT_MONTHS;
        $user = $request->user();

        return view('admin.content-review.index', [
            'rows' => ContentReview::stale($months, fn ($module) => $user->canAccessModule($module)),
            'months' => $months,
            'available' => ContentReview::available(),
        ]);
    }

    /** „Sprawdzone, aktualne” — jedna lub wiele treści (items[] = "typ:id"). */
    public function reviewed(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['string', 'regex:/^(pages|news|projects):\d+$/'],
        ]);

        $count = 0;
        foreach ($data['items'] as $item) {
            [$key, $id] = explode(':', $item);
            $source = ContentReview::SOURCES[$key];
            abort_unless($request->user()->canAccessModule($source['module']), 403);
            $model = $source['model']::query()->forCurrentSite()->find((int) $id);
            if ($model) {
                ContentReview::markReviewed($model);
                $count++;
            }
        }

        return back()->with('status', $count === 1 ? 'Oznaczono jako sprawdzone — wraca do przeglądu za rok.' : "Oznaczono jako sprawdzone: {$count}.");
    }
}
