<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Ekran wyboru witryny po zalogowaniu („Nad którą witryną chcesz dziś pracować?”) oraz „Przenieś ustawienia”:
 * kopiowanie wybranych grup ustawień (nagłówek, kolory, moduły) z jednej witryny sieci do innych.
 *
 * Wybór zapisuje `admin_active_site_id` (jak przełącznik w pasku bocznym) i znacznik `admin_site_chosen`, który
 * znika razem z sesją, więc ekran pojawia się po każdym nowym logowaniu.
 */
class SiteChooserController extends Controller
{
    /**
     * Grupy ustawień, które można przenieść między witrynami: kolumny `site_settings` i kolekcje plików.
     * Logo, dane kontaktowe, hasła, klucze i integracje nigdy nie są przenoszone.
     */
    public const GROUPS = [
        'header' => [
            'label' => 'Nagłówek',
            'hint' => 'Układ paska, widoczność BIP i mediów społecznościowych, pasek informacyjny, przycisk CTA, logotypy partnerów w nagłówku.',
            'fields' => [
                'header_layout', 'logo_alt', 'logo_only', 'show_topbar_bip', 'show_topbar_social',
                'infobar_show_date', 'infobar_show_nameday', 'office_show_account', 'office_show_search',
                'wide_mission_social_1', 'wide_mission_social_2', 'wide_mission_cta_label', 'wide_mission_cta_url',
                'vm_header_badge_alt',
            ],
            'media' => ['header_partners', 'vm_header_badge'],
        ],
        'colors' => [
            'label' => 'Kolory marki',
            'hint' => 'Cztery kolory marki, kolor podświetlenia menu i ustawienia kontrastu.',
            'fields' => ['brand_color', 'brand_color_2', 'brand_color_3', 'brand_color_4', 'brand_skip_contrast', 'ngo_skip_contrast', 'nav_dark_text', 'menu_highlight_color'],
            'media' => [],
        ],
        'modules' => [
            'label' => 'Moduły i strona główna',
            'hint' => 'Włączone moduły, kolejność i ukrycie sekcji strony głównej.',
            'fields' => ['disabled_modules', 'homepage_section_order', 'homepage_sections_hidden'],
            'media' => [],
        ],
    ];

    /** Witryny, do których użytkownik może się przełączyć (poza koszem). */
    public static function sites()
    {
        return SiteSetting::query()->notTrashed()->orderByRaw('parent_site_id is not null')->orderBy('site_name')->get();
    }

    public function index(Request $request)
    {
        return view('admin.site-chooser', ['sites' => self::sites(), 'current' => SiteSetting::current()]);
    }

    public function choose(Request $request, SiteSetting $site): RedirectResponse
    {
        abort_unless(SiteSetting::query()->notTrashed()->whereKey($site->id)->exists(), 404);

        session(['admin_active_site_id' => $site->id, 'admin_site_chosen' => true]);

        return redirect()->route('admin.dashboard')->with('status', 'Pracujesz teraz na witrynie „'.($site->site_name ?: $site->slug).'”.');
    }

    public function settingsForm()
    {
        return view('admin.site-settings-transfer', ['sites' => self::sites(), 'current' => SiteSetting::current(), 'groups' => self::GROUPS]);
    }

    public function copySettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source_site_id' => ['required', 'integer', 'exists:site_settings,id'],
            'target_site_ids' => ['required', 'array', 'min:1'],
            'target_site_ids.*' => ['integer', 'exists:site_settings,id'],
            'groups' => ['required', 'array', 'min:1'],
            'groups.*' => ['string', 'in:'.implode(',', array_keys(self::GROUPS))],
        ], [
            'target_site_ids.required' => 'Zaznacz co najmniej jedną witrynę docelową.',
            'target_site_ids.min' => 'Zaznacz co najmniej jedną witrynę docelową.',
            'groups.required' => 'Zaznacz, co przenieść.',
            'groups.min' => 'Zaznacz, co przenieść.',
        ]);

        $source = SiteSetting::query()->notTrashed()->findOrFail($data['source_site_id']);
        $targets = SiteSetting::query()->notTrashed()
            ->whereIn('id', $data['target_site_ids'])->where('id', '!=', $source->id)->get();

        if ($targets->isEmpty()) {
            return back()->withInput()->with('error', 'Wybierz witryny inne niż źródłowa.');
        }

        $fields = [];
        $collections = [];
        foreach ($data['groups'] as $group) {
            $fields = [...$fields, ...self::GROUPS[$group]['fields']];
            $collections = [...$collections, ...self::GROUPS[$group]['media']];
        }
        $fields = array_values(array_filter(array_unique($fields), fn ($f) => Schema::hasColumn('site_settings', $f)));
        $values = collect($fields)->mapWithKeys(fn ($f) => [$f => $source->getAttribute($f)])->all();

        foreach ($targets as $target) {
            $target->forceFill($values)->save();

            foreach ($collections as $collection) {
                $target->clearMediaCollection($collection);
                foreach ($source->getMedia($collection) as $media) {
                    if (is_file($media->getPath())) {
                        $media->copy($target, $collection);
                    }
                }
            }
        }

        $names = $targets->map(fn ($s) => $s->site_name ?: $s->slug)->implode(', ');

        $labels = collect($data['groups'])->map(fn ($g) => mb_strtolower(self::GROUPS[$g]['label']))->implode(', ');

        return redirect()->route('admin.witryny.ustawienia')
            ->with('status', 'Przeniesiono ustawienia ('.$labels.') z witryny „'.($source->site_name ?: $source->slug).'” do: '.$names.'. Logo, dane kontaktowe i integracje zostają bez zmian.');
    }
}
