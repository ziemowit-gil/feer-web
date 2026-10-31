<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;

/**
 * Wspólne kroki akcji „Kopiuj do witryny” (strony, partnerzy, aktualności, wydarzenia): tylko administrator,
 * walidacja witryny docelowej i kopiowanie pliku z kolekcji media.
 */
trait CopiesToSite
{
    /** Witryna docelowa albo przekierowanie z komunikatem, gdy wybór jest niepoprawny. */
    protected function copyTargetSite(Request $request, Model $model): SiteSetting|RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate(['target_site_id' => ['required', 'integer', 'exists:site_settings,id']]);
        $target = SiteSetting::query()->notTrashed()->find($data['target_site_id']);

        if (! $target || (int) $target->id === (int) $model->site_id) {
            return back()->with('error', 'Wybierz inną witrynę niż ta, do której należy ta pozycja.');
        }

        return $target;
    }

    /** Kopiuje pierwszy plik z kolekcji (logo, zdjęcie) na kopię; brak pliku nie jest błędem. */
    protected function copyMediaTo(HasMedia $from, HasMedia $to, string $collection): void
    {
        $media = $from->getFirstMedia($collection);

        if ($media && is_file($media->getPath())) {
            $media->copy($to, $collection);
        }
    }

    protected function siteLabel(SiteSetting $site): string
    {
        return $site->site_name ?: ($site->slug ?: 'witryny');
    }
}
