<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AttachesLibraryMedia;
use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\FundraisingTexts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Zbiórki → Teksty i podziękowania: globalne (dla witryny) teksty modułu
 * zbiórek, zdjęcie strony podziękowania i grafika e-maila. Zapisuje tylko
 * kolumnę fundraising_texts i dwie kolekcje mediów — nie przechodzi przez
 * ogólny formularz ustawień.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class FundraisingSettingsController extends Controller
{
    use AttachesLibraryMedia;

    private const IMAGES = [
        'thanks_image' => 'fundraising_thanks_image',
        'thanks_email_image' => 'fundraising_thanks_email_image',
    ];

    public function edit(): View
    {
        $settings = SiteSetting::current();

        return view('admin.fundraising.settings', [
            'settings' => $settings,
            'values' => FundraisingTexts::clean($settings->fundraising_texts ?? []),
            'ready' => Schema::hasColumn('site_settings', 'fundraising_texts'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(FundraisingTexts::rules() + [
            'thanks_image' => ['nullable', 'image', 'max:4096'],
            'thanks_image_library_id' => $this->libraryMediaRule(),
            'thanks_email_image' => ['nullable', 'image', 'max:2048'],
            'thanks_email_image_library_id' => $this->libraryMediaRule(),
        ]);

        $settings = SiteSetting::current();

        // Do czasu migracji kolumny zapis nie może dawać 500.
        if (! Schema::hasColumn('site_settings', 'fundraising_texts')) {
            return back()->with('error', 'Najpierw uruchom migracje (php artisan migrate).');
        }

        $settings->forceFill(['fundraising_texts' => FundraisingTexts::clean($request->input('texts', [])) ?: null])->save();

        foreach (self::IMAGES as $field => $collection) {
            if ($request->hasFile($field)) {
                $settings->addMediaFromRequest($field)->toMediaCollection($collection);
            } elseif (! $this->attachFromLibrary($request, $settings, $field, $collection) && $request->boolean('remove_'.$field)) {
                $settings->clearMediaCollection($collection);
            }
        }

        return redirect()->route('admin.zbiorki-teksty.edit')->with('status', 'Teksty zbiórek zostały zapisane.');
    }
}
