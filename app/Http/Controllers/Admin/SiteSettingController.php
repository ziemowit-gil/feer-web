<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ScheduleChangeMail;
use App\Models\MeetingSignup;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Panel admin: ustawienia serwisu — wygląd, moduły, dane kontaktowe, poczta, dostępność, SEO.
 *
 * Metody: edit(), update(), dev(), overwriteStrefa(), updateAdminPrefix(),
 *         mailTest(), regenerateEmergencyToken().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SiteSettingController extends Controller
{
    /** Wyświetla formularz ustawień serwisu. */
    public function edit()
    {
        return view('admin.settings.edit', [
            'settings' => SiteSetting::current(),
            // Gdy adres /strefa zajmuje inna strona — panel pokaże prośbę o nadpisanie.
            'strefaConflict' => Page::strefaSlugConflict(),
        ]);
    }

    /** Wyświetla zakładkę deweloperską (diagnostyka, narzędzia debugowania). */
    public function dev()
    {
        return view('admin.settings.dev', [
            'settings' => SiteSetting::current(),
        ]);
    }

    /**
     * Nadpisuje stronę spod adresu /strefa jako „Strefę współpracownika"
     * (strona wewnętrzna, logowanie MS365). Wywoływane, gdy administrator
     * potwierdzi komunikat o zajętym adresie. Treść strony jest zachowywana,
     * jeśli istnieje; w przeciwnym razie ustawiana jest treść domyślna.
     */
    public function overwriteStrefa(): RedirectResponse
    {
        $page = Page::query()->where('slug', Page::STREFA_SLUG)->first() ?? new Page(['slug' => Page::STREFA_SLUG]);

        $previousTitle = $page->exists ? $page->title : null;

        $page->forceFill(Page::strefaAttributes());

        if (blank($page->content)) {
            $page->content = Page::STREFA_DEFAULT_CONTENT;
        }

        $page->save();

        $message = $previousTitle
            ? sprintf('Adres /strefa-wspolpracownika-feer nadpisany jako Strefa wspolpracownika (poprzednio: “%s”).', $previousTitle)
            : 'Utworzono Strefe wspolpracownika pod adresem /strefa-wspolpracownika-feer.';

        return redirect()->route('admin.ustawienia.edit', ['tab' => 'login'])->with('status', $message);
    }

    /** Zapisuje wszystkie ustawienia serwisu (wygląd, moduły, kontakt, SEO, poczta, WCAG). */
    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:2000'],
            'brand_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_color_2' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_color_3' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_color_4' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'header_layout' => ['required', Rule::in(array_keys(SiteSetting::HEADER_LAYOUTS))],
            'content_editor' => ['required', Rule::in(array_keys(SiteSetting::EDITORS))],
            'microsoft_login_enabled' => ['sometimes', 'boolean'],
            'microsoft_only_login' => ['sometimes', 'boolean'],
            'microsoft_client_id' => ['nullable', 'string', 'max:255'],
            'microsoft_client_secret' => ['nullable', 'string', 'max:1000'],
            'microsoft_tenant_id' => ['nullable', 'string', 'max:255'],
            'member_login_enabled' => ['sometimes', 'boolean'],
            'member_allowed_domains' => ['nullable', 'string', 'max:500'],
            'szo_api_url' => ['nullable', 'url', 'max:255'],
            'yubico_client_id' => ['nullable', 'string', 'max:255'],
            'yubico_secret_key' => ['nullable', 'string', 'max:1000'],
            'two_factor_required_admins' => ['sometimes', 'boolean'],
            'unsplash_access_key' => ['nullable', 'string', 'max:1000'],
            'cookie_banner_enabled' => ['sometimes', 'boolean'],
            'cookie_banner_text' => ['nullable', 'string', 'max:1000'],
            'show_cms_credit' => ['sometimes', 'boolean'],
            'mail_transport' => ['required', Rule::in(array_keys(SiteSetting::MAIL_TRANSPORTS))],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:1000'],
            'mail_encryption' => ['nullable', Rule::in(['', 'tls', 'ssl'])],
            'show_coordinators' => ['sometimes', 'boolean'],
            'ngo_color'          => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_skip_contrast' => ['sometimes', 'boolean'],
            'ngo_skip_contrast'  => ['nullable', 'boolean'],
            'nav_dark_text'      => ['sometimes', 'boolean'],
            'sub_brands' => ['nullable', 'array'],
            'sub_brands.*.name' => ['nullable', 'string', 'max:60'],
            'sub_brands.*.color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'logo_alt' => ['nullable', 'string', 'max:255'],
            'logo_only' => ['sometimes', 'boolean'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'image', 'max:2048'],
            'remove_og_image' => ['sometimes', 'boolean'],
            'allow_indexing' => ['sometimes', 'boolean'],
            'ga_measurement_id' => ['nullable', 'string', 'max:32', 'regex:/^G-[A-Za-z0-9]+$/'],
            'bip_mode' => ['nullable', 'string', 'in:internal,external'],
            'bip_url' => ['nullable', 'string', 'max:255'],
            'bip_intro' => ['nullable', 'string', 'max:20000'],
            'bip_editor_name' => ['nullable', 'string', 'max:120'],
            'bip_editor_email' => ['nullable', 'email', 'max:255'],
            'bip_gov_url' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'facebook_group_url' => ['nullable', 'string', 'max:255'],
            'twitter_url' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'string', 'max:255'],
            'youtube_url' => ['nullable', 'string', 'max:255'],
            'substack_url' => ['nullable', 'string', 'max:255'],
            'show_topbar_bip' => ['sometimes', 'boolean'],
            'show_topbar_social' => ['sometimes', 'boolean'],
            'contact_address' => ['required', 'string', 'max:255'],
            'contact_city' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_office_hours' => ['nullable', 'string', 'max:255'],
            'contact_edelivery_address' => ['nullable', 'string', 'max:120'],
            'contact_correspondence_title' => ['nullable', 'string', 'max:120'],
            'contact_correspondence_note' => ['nullable', 'string', 'max:1000'],
            'contact_shipping_note' => ['nullable', 'string', 'max:255'],
            'contact_paczkomat_code' => ['nullable', 'string', 'max:30'],
            'contact_paczkomat_address' => ['nullable', 'string', 'max:255'],
            'contact_paczkomat_location' => ['nullable', 'string', 'max:255'],
            'contact_shipping_phone' => ['nullable', 'string', 'max:50'],
            'contact_shipping_visible' => ['sometimes', 'boolean'],
            'contact_intro' => ['nullable', 'string', 'max:5000'],
            'contact_bank_accounts' => ['nullable', 'array'],
            'contact_bank_accounts.*.number' => ['nullable', 'string', 'max:80'],
            'contact_bank_accounts.*.purpose' => ['nullable', 'string', 'max:500'],
            'contact_meeting_title' => ['nullable', 'string', 'max:255'],
            'contact_online_meeting_url' => ['nullable', 'string', 'max:255'],
            'contact_online_meeting_label' => ['nullable', 'string', 'max:100'],
            'contact_online_meeting_text' => ['nullable', 'string', 'max:255'],
            'contact_remote_note' => ['nullable', 'string', 'max:255'],
            'contact_meeting_notify_email' => ['nullable', 'email', 'max:255'],
            'contact_schedule_title' => ['nullable', 'string', 'max:255'],
            'contact_schedule_enabled' => ['sometimes', 'boolean'],
            'contact_no_schedule_note' => ['nullable', 'string', 'max:255'],
            'contact_schedule' => ['nullable', 'array'],
            'contact_schedule.*.type' => ['nullable', Rule::in(['date', 'weekly'])],
            'contact_schedule.*.date' => ['nullable', 'date'],
            'contact_schedule.*.weekday' => ['nullable', 'integer', 'between:1,7'],
            'contact_schedule.*.time' => ['nullable', 'string', 'max:60'],
            'contact_schedule.*.where' => ['nullable', 'string', 'max:255'],
            'contact_schedule.*.note' => ['nullable', 'string', 'max:500'],
            'notify_schedule_change' => ['sometimes', 'boolean'],
            'contact_box_text' => ['nullable', 'string', 'max:1000'],
            'contact_box_link_label' => ['nullable', 'string', 'max:100'],
            'contact_box_link_url' => ['nullable', 'string', 'max:255'],
            'contact_box_visible_from' => ['nullable', 'date'],
            'contact_box_visible_until' => ['nullable', 'date', 'after_or_equal:contact_box_visible_from'],
            'homepage_banner_text' => ['nullable', 'string', 'max:1000'],
            'homepage_banner_link_label' => ['nullable', 'string', 'max:100'],
            'homepage_banner_link_url' => ['nullable', 'string', 'max:255'],
            'homepage_banner_visible_from' => ['nullable', 'date'],
            'homepage_banner_visible_until' => ['nullable', 'date', 'after_or_equal:homepage_banner_visible_from'],
            'krs_number' => ['nullable', 'string', 'max:50'],
            'nip_number' => ['nullable', 'string', 'max:50'],
            'regon_number' => ['nullable', 'string', 'max:50'],
            'accessibility_entity_name' => ['nullable', 'string', 'max:255'],
            'accessibility_status' => ['nullable', Rule::in(array_keys(SiteSetting::ACCESSIBILITY_STATUSES))],
            'accessibility_status_note' => ['nullable', 'string', 'max:5000'],
            'accessibility_page_published_at' => ['nullable', 'date'],
            'accessibility_page_updated_at' => ['nullable', 'date'],
            'accessibility_declaration_date' => ['nullable', 'date'],
            'accessibility_review_method' => ['nullable', Rule::in(array_keys(SiteSetting::ACCESSIBILITY_REVIEW_METHODS))],
            'accessibility_contact_name' => ['nullable', 'string', 'max:255'],
            'accessibility_contact_email' => ['nullable', 'email', 'max:255'],
            'accessibility_contact_phone' => ['nullable', 'string', 'max:50'],
            'accessibility_architectural' => ['nullable', 'string', 'max:5000'],
            'projects_intro' => ['nullable', 'string', 'max:5000'],
            'materials_intro' => ['nullable', 'string', 'max:5000'],
            'materials_notice' => ['nullable', 'string', 'max:5000'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_tax_number' => ['nullable', 'string', 'max:50'],
            'support_intro' => ['nullable', 'string', 'max:5000'],
            'support_quick_transfer_url' => ['nullable', 'string', 'max:255'],
            'support_buycoffee_url' => ['nullable', 'string', 'max:255'],
            'support_wplacam_url' => ['nullable', 'string', 'max:255'],
            'support_method4_title' => ['nullable', 'string', 'max:255'],
            'support_method4_text' => ['nullable', 'string', 'max:500'],
            'support_method4_cta_label' => ['nullable', 'string', 'max:100'],
            'support_show_partners' => ['sometimes', 'boolean'],
            'support_testimonial_quote' => ['nullable', 'string', 'max:500'],
            'support_testimonial_author' => ['nullable', 'string', 'max:120'],
            'support_testimonial_role' => ['nullable', 'string', 'max:120'],
            'support_fundraiser_title' => ['nullable', 'string', 'max:160'],
            'support_fundraiser_text' => ['nullable', 'string', 'max:500'],
            'support_fundraiser_goal' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'support_fundraiser_raised' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'support_fundraiser_url' => ['nullable', 'string', 'max:255'],
            'support_fundraiser_cta_label' => ['nullable', 'string', 'max:60'],
            'support_image' => ['nullable', 'image', 'max:4096'],
            'remove_support_image' => ['sometimes', 'boolean'],
            'support_gallery' => ['nullable', 'array'],
            'support_gallery.*' => ['image', 'max:4096'],
            'remove_support_gallery' => ['nullable', 'array'],
            'remove_support_gallery.*' => ['integer'],
            'news_layout' => ['nullable', 'in:grid,list,cards'],
            'wide_mission_social_1' => ['nullable', Rule::in(array_keys(SiteSetting::SOCIAL_KEYS))],
            'wide_mission_social_2' => ['nullable', Rule::in(array_keys(SiteSetting::SOCIAL_KEYS))],
            'wide_mission_cta_label' => ['nullable', 'string', 'max:80'],
            'wide_mission_cta_url' => ['nullable', 'string', 'max:255'],
            'wide_mission_show_mission' => ['sometimes', 'boolean'],
            'wide_mission_highlight_account' => ['sometimes', 'boolean'],
            'wide_mission_nav_align' => ['nullable', 'in:left,center'],
            'wide_mission_search_in_nav' => ['sometimes', 'boolean'],
            'hero_mission_slide' => ['sometimes', 'boolean'],
            'quick_actions_panel_negative' => ['sometimes', 'boolean'],
            'wide_mission_sidebar' => ['sometimes', 'boolean'],
            'wide_mission_sidebar_style' => ['nullable', 'in:mission,colored,cards'],
            'wide_mission_nav_style' => ['nullable', 'in:brand_bar,icons_white'],
            'volunteer_layout' => ['nullable', 'in:grid,list'],
            'news_default_image' => ['nullable', 'image', 'max:4096'],
            'remove_news_default_image' => ['sometimes', 'boolean'],
            'bip_logo' => ['nullable', 'image', 'max:2048'],
            'remove_bip_logo' => ['sometimes', 'boolean'],
            'support_hero_badge' => ['nullable', 'string', 'max:100'],
            'support_hero_title' => ['nullable', 'string', 'max:255'],
            'support_hero_subtitle' => ['nullable', 'string', 'max:500'],
            'support_hero_cta_label' => ['nullable', 'string', 'max:100'],
            'support_benefits_title' => ['nullable', 'string', 'max:255'],
            'support_benefits_subtitle' => ['nullable', 'string', 'max:500'],
            'support_benefit1_icon' => ['nullable', 'string', 'max:100'],
            'support_benefit1_title' => ['nullable', 'string', 'max:255'],
            'support_benefit1_text' => ['nullable', 'string', 'max:500'],
            'support_benefit2_icon' => ['nullable', 'string', 'max:100'],
            'support_benefit2_title' => ['nullable', 'string', 'max:255'],
            'support_benefit2_text' => ['nullable', 'string', 'max:500'],
            'support_benefit3_icon' => ['nullable', 'string', 'max:100'],
            'support_benefit3_title' => ['nullable', 'string', 'max:255'],
            'support_benefit3_text' => ['nullable', 'string', 'max:500'],
            'support_methods_title' => ['nullable', 'string', 'max:255'],
            'support_method1_title' => ['nullable', 'string', 'max:255'],
            'support_method1_account_label' => ['nullable', 'string', 'max:100'],
            'support_method1_tax_label' => ['nullable', 'string', 'max:100'],
            'support_method1_transfer_label' => ['nullable', 'string', 'max:100'],
            'support_transfer_title' => ['nullable', 'string', 'max:255'],
            'support_method2_title' => ['nullable', 'string', 'max:255'],
            'support_method2_text' => ['nullable', 'string', 'max:500'],
            'support_method2_cta_label' => ['nullable', 'string', 'max:100'],
            'support_method3_title' => ['nullable', 'string', 'max:255'],
            'support_method3_text' => ['nullable', 'string', 'max:500'],
            'support_method3_cta_label' => ['nullable', 'string', 'max:100'],
            'support_outro_title' => ['nullable', 'string', 'max:255'],
            'support_outro_subtitle' => ['nullable', 'string', 'max:500'],
            'enabled_modules' => ['sometimes', 'array'],
            'enabled_modules.*' => ['string', Rule::in(array_keys(SiteSetting::MODULES))],
            'section_order_json' => ['sometimes', 'nullable', 'string'],
            'events_home_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'site_template'                    => ['nullable', Rule::in(array_keys(SiteSetting::SITE_TEMPLATES))],
            'municipality_shortcuts_slug'      => ['nullable', 'string', 'max:255'],
            'municipality_carousel_title'      => ['nullable', 'string', 'max:255'],
            'municipality_weather_lat'         => ['nullable', 'numeric', 'between:-90,90'],
            'municipality_weather_lon'         => ['nullable', 'numeric', 'between:-180,180'],
            'municipality_show_google_translate' => ['sometimes', 'boolean'],
        ]);

        $data['allow_indexing'] = $request->boolean('allow_indexing');
        $data['logo_only'] = $request->boolean('logo_only');
        $data['maintenance_mode'] = $request->boolean('maintenance_mode');
        $data['municipality_show_google_translate'] = $request->boolean('municipality_show_google_translate');
        $data['site_url'] = filled($data['site_url'] ?? null) ? rtrim($data['site_url'], '/') : null;
        $data['microsoft_login_enabled'] = $request->boolean('microsoft_login_enabled');
        $data['microsoft_only_login'] = $request->boolean('microsoft_only_login');
        $data['member_login_enabled'] = $request->boolean('member_login_enabled');
        $data['two_factor_required_admins'] = $request->boolean('two_factor_required_admins');

        // Puste pole sekretu = zostaw zapisany (nie renderujemy go w formularzu).
        if (blank($data['microsoft_client_secret'] ?? null)) {
            unset($data['microsoft_client_secret']);
        }
        // Puste pole klucza Yubico = zostaw zapisane (analogicznie do sekretu Microsoft).
        if (blank($data['yubico_secret_key'] ?? null)) {
            unset($data['yubico_secret_key']);
        }
        // Puste hasło SMTP = zostaw zapisane (analogicznie do sekretu Microsoft).
        if (blank($data['mail_password'] ?? null)) {
            unset($data['mail_password']);
        }
        if (blank($data['unsplash_access_key'] ?? null)) {
            unset($data['unsplash_access_key']);
        }
        $data['mail_encryption'] = filled($data['mail_encryption'] ?? null) ? $data['mail_encryption'] : null;
        $data['show_coordinators'] = $request->boolean('show_coordinators');
        $data['wide_mission_nav_hover_white'] = $request->boolean('wide_mission_nav_hover_white');
        $data['wide_mission_nav_active_white'] = $request->boolean('wide_mission_nav_active_white');
        $data['wide_mission_nav_icons_white'] = $request->boolean('wide_mission_nav_icons_white');
        $data['show_topbar_bip'] = $request->boolean('show_topbar_bip');
        $data['show_topbar_social'] = $request->boolean('show_topbar_social');
        $data['contact_show_form'] = $request->boolean('contact_show_form');
        $data['contact_show_bank_accounts'] = $request->boolean('contact_show_bank_accounts');
        $data['contact_show_coordinators'] = $request->boolean('contact_show_coordinators');
        $data['contact_shipping_visible'] = $request->boolean('contact_shipping_visible');
        $data['contact_schedule_enabled'] = $request->boolean('contact_schedule_enabled');
        $data['support_show_partners'] = $request->boolean('support_show_partners');
        $data['cookie_banner_enabled'] = $request->boolean('cookie_banner_enabled');
        $data['show_cms_credit'] = $request->boolean('show_cms_credit');

        // Rachunki bankowe: przycinamy pola, odrzucamy wiersze bez numeru
        // (pusty wiersz-zalążek z formularza) i przenumerowujemy listę.
        $data['contact_bank_accounts'] = collect($request->input('contact_bank_accounts', []))
            ->map(fn ($row) => [
                'number' => trim((string) ($row['number'] ?? '')),
                'purpose' => trim((string) ($row['purpose'] ?? '')),
            ])
            ->filter(fn ($row) => $row['number'] !== '')
            ->values()
            ->all();

        // Harmonogram stacjonarny: każdy wpis to konkretna data albo cykliczny
        // dzień tygodnia. Przycinamy pola i odrzucamy wiersze bez daty/dnia.
        $data['contact_schedule'] = collect($request->input('contact_schedule', []))
            ->map(function ($row) {
                $type = ($row['type'] ?? 'date') === 'weekly' ? 'weekly' : 'date';

                return [
                    'type' => $type,
                    'date' => $type === 'date' ? trim((string) ($row['date'] ?? '')) : '',
                    'weekday' => $type === 'weekly' ? (int) ($row['weekday'] ?? 0) : null,
                    'time' => trim((string) ($row['time'] ?? '')),
                    'where' => trim((string) ($row['where'] ?? '')),
                    'note' => trim((string) ($row['note'] ?? '')),
                ];
            })
            ->filter(fn ($row) => ($row['type'] === 'date' && $row['date'] !== '')
                || ($row['type'] === 'weekly' && $row['weekday'] >= 1 && $row['weekday'] <= 7))
            ->values()
            ->all();
        $data['disabled_modules'] = $request->has('enabled_modules')
            ? array_values(array_diff(array_keys(SiteSetting::MODULES), $request->input('enabled_modules')))
            : (SiteSetting::current()->disabled_modules ?? []);

        $orderedKeys = json_decode($request->input('section_order_json', '[]'), true) ?? [];
        $defined = array_keys(SiteSetting::HOMEPAGE_SECTIONS);
        $valid = array_values(array_intersect($orderedKeys, $defined));
        $data['homepage_section_order'] = array_values(array_unique(array_merge($valid, $defined)));

        $settings = SiteSetting::current();

        if ($data['header_layout'] === 'wide_mission' && $settings->header_layout !== 'wide_mission') {
            if ($request->input('wide_activation_code') !== $settings->wide_code) {
                return back()
                    ->withErrors(['wide_activation_code' => 'Nieprawidłowy kod aktywacyjny stylu Wide.'])
                    ->withInput();
            }
        }

        $skipContrast = (bool) ($data['brand_skip_contrast'] ?? false);

        $data['brand_color'] = $skipContrast
            ? $data['brand_color']
            : $settings->contrastSafeColor($data['brand_color']);

        // Dodatkowe kolory marki: puste = null, w przeciwnym razie kontrast AA na białym.
        foreach (['brand_color_2', 'brand_color_3', 'brand_color_4'] as $key) {
            $data[$key] = filled($data[$key] ?? null)
                ? ($skipContrast ? $data[$key] : $settings->contrastSafeColor($data[$key]))
                : null;
        }

        $skipNgoContrast = $skipContrast || (bool) ($data['ngo_skip_contrast'] ?? false);

        // Kolor NGO także pilnujemy pod kątem kontrastu (używany jak text-brand na białym).
        $data['ngo_color'] = filled($data['ngo_color'] ?? null)
            ? ($skipNgoContrast ? $data['ngo_color'] : $settings->contrastSafeColor($data['ngo_color']))
            : null;

        // Kolor sekcji wydarzeń na stronie głównej: pusty = kolor marki.
        $data['events_home_color'] = filled($data['events_home_color'] ?? null)
            ? ($skipContrast ? $data['events_home_color'] : $settings->contrastSafeColor($data['events_home_color']))
            : null;

        // Kolory submarek: odrzucamy puste wiersze i pilnujemy kontrastu każdego koloru.
        $data['sub_brands'] = collect($request->input('sub_brands', []))
            ->map(fn ($s) => [
                'name' => trim((string) ($s['name'] ?? '')),
                'color' => trim((string) ($s['color'] ?? '')),
            ])
            ->filter(fn ($s) => $s['name'] !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $s['color']))
            ->map(fn ($s) => [
                'name' => $s['name'],
                'color' => $skipContrast ? $s['color'] : $settings->contrastSafeColor($s['color']),
            ])
            ->values()
            ->all() ?: null;

        unset($data['logo'], $data['remove_logo'], $data['og_image'], $data['remove_og_image'], $data['support_image'], $data['remove_support_image'], $data['support_gallery'], $data['remove_support_gallery'], $data['news_default_image'], $data['remove_news_default_image'], $data['bip_logo'], $data['remove_bip_logo'], $data['enabled_modules'], $data['section_order_json'], $data['notify_schedule_change']);

        $colorWasAdjusted = ! $skipContrast && $data['brand_color'] !== $request->input('brand_color');

        $settings->update($data);

        activity('cms')
            ->causedBy(auth()->user())
            ->performedOn($settings)
            ->withProperty('label', $settings->site_name)
            ->event('settings_updated')
            ->log('SiteSetting settings_updated');

        if ($request->hasFile('logo')) {
            $settings->addMediaFromRequest('logo')->toMediaCollection('logo');
        } elseif ($request->boolean('remove_logo')) {
            $settings->clearMediaCollection('logo');
        }

        if ($request->hasFile('og_image')) {
            $settings->addMediaFromRequest('og_image')->toMediaCollection('og_image');
        } elseif ($request->boolean('remove_og_image')) {
            $settings->clearMediaCollection('og_image');
        }

        if ($request->hasFile('support_image')) {
            $settings->addMediaFromRequest('support_image')->toMediaCollection('support_image');
        } elseif ($request->boolean('remove_support_image')) {
            $settings->clearMediaCollection('support_image');
        }

        if ($request->hasFile('news_default_image')) {
            $settings->addMediaFromRequest('news_default_image')->toMediaCollection('news_default_image');
        } elseif ($request->boolean('remove_news_default_image')) {
            $settings->clearMediaCollection('news_default_image');
        }

        if ($request->hasFile('bip_logo')) {
            $settings->addMediaFromRequest('bip_logo')->toMediaCollection('bip_logo');
        } elseif ($request->boolean('remove_bip_logo')) {
            $settings->clearMediaCollection('bip_logo');
        }

        // Osobna galeria strony „Wesprzyj nas": najpierw usuwamy odznaczone
        // zdjęcia, potem dokładamy nowo wgrane (kolekcja wielozdjęciowa).
        foreach ((array) $request->input('remove_support_gallery', []) as $mediaId) {
            $settings->media()->where('collection_name', 'support_gallery')->where('id', (int) $mediaId)->each->delete();
        }
        foreach ((array) $request->file('support_gallery', []) as $file) {
            $settings->addMedia($file)->toMediaCollection('support_gallery');
        }

        // Opcjonalne powiadomienie o zmianie terminu: do osób zapisanych przez
        // formularz „Daj znać, że przyjdziesz” (BCC — nie ujawniamy adresów), z
        // kopią na adres administracyjny. Błąd wysyłki nie cofa zapisu ustawień.
        $notifyMsg = '';
        if ($request->boolean('notify_schedule_change')) {
            $recipients = MeetingSignup::query()->pluck('email')->filter()->unique()->values()->all();
            $copyTo = $settings->meetingNotifyEmail();

            if ($copyTo || $recipients !== []) {
                $items = array_map(fn ($i) => [
                    'when_label' => $i['when_label'],
                    'where' => $i['where'],
                    'note' => $i['note'],
                    'is_next' => $i['is_next'],
                ], $settings->contactScheduleUpcoming());

                $mail = new ScheduleChangeMail($items, $settings->site_name, $settings->contact_schedule_title ?: 'Kiedy i gdzie jesteśmy');
                $to = $copyTo ?: $recipients[0];
                $bcc = $copyTo ? $recipients : array_slice($recipients, 1);

                try {
                    $pending = Mail::to($to);
                    if ($bcc !== []) {
                        $pending->bcc($bcc);
                    }
                    $pending->send($mail);

                    $notifyMsg = $recipients !== []
                        ? ' Wysłano powiadomienie o zmianie terminu do '.count($recipients).' zapisanych osób (kopia na adres administracyjny).'
                        : ' Powiadomienie o zmianie terminu wysłano na adres administracyjny (brak zapisanych osób).';
                } catch (\Throwable $e) {
                    $notifyMsg = ' Ustawienia zapisano, ale nie udało się wysłać powiadomienia o zmianie terminu: '.$e->getMessage();
                }
            } else {
                $notifyMsg = ' Nie wysłano powiadomienia — brak adresu i zapisanych osób.';
            }
        }

        $status = ($colorWasAdjusted
            ? "Ustawienia zostały zapisane. Kolor przewodni był zbyt jasny dla kontrastu WCAG, więc został automatycznie przyciemniony do {$data['brand_color']}."
            : 'Ustawienia zostały zapisane.').$notifyMsg;

        $redirectTab = $request->input('_redirect_tab');
        $redirectTab = in_array($redirectTab, array_keys(SiteSetting::SETTINGS_TABS), true)
            ? $redirectTab
            : 'general';

        return redirect()->route('admin.ustawienia.edit', ['tab' => $redirectTab])->with('status', $status);
    }

    /**
     * Zmienia prefix URL panelu admina (ADMIN_PREFIX w .env).
     * Po zapisie przekierowuje do nowego adresu, bo stary przestaje działać.
     */
    public function updateAdminPrefix(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_prefix' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9][a-z0-9\-_]*[a-z0-9]$/'],
        ], [
            'admin_prefix.regex' => 'Prefix może zawierać tylko małe litery, cyfry, myślniki i podkreślenia. Musi zaczynać się i kończyć literą lub cyfrą.',
            'admin_prefix.min' => 'Prefix musi mieć co najmniej 3 znaki.',
        ]);

        $newPrefix = $data['admin_prefix'];
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return back()->with('error', 'Plik .env nie istnieje — nie można zmienić prefiksu.');
        }

        $content = file_get_contents($envPath);

        if (preg_match('/^ADMIN_PREFIX=.*/m', $content)) {
            $content = preg_replace('/^ADMIN_PREFIX=.*/m', "ADMIN_PREFIX={$newPrefix}", $content);
        } else {
            $content = rtrim($content)."\nADMIN_PREFIX={$newPrefix}\n";
        }

        file_put_contents($envPath, $content);

        Artisan::call('config:clear');

        $newUrl = url("/{$newPrefix}/ustawienia").'?tab=login';

        return redirect($newUrl)->with('status', "Prefix panelu zmieniony na /{$newPrefix}. Zaktualizuj zakładki.");
    }

    /**
     * Wyślij testową wiadomość na podany adres, korzystając z aktualnie
     * zapisanej konfiguracji poczty (nadpisanej już w AppServiceProvider).
     */
    public function mailTest(Request $request)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            Mail::raw(
                'To jest testowa wiadomość ze strony '.SiteSetting::current()->site_name.'. '
                .'Jeśli ją widzisz, konfiguracja poczty działa poprawnie.',
                fn ($message) => $message->to($data['test_email'])->subject('Test konfiguracji poczty')
            );
        } catch (\Throwable $e) {
            return redirect()->route('admin.ustawienia.edit')
                ->with('error', 'Nie udało się wysłać wiadomości testowej: '.$e->getMessage());
        }

        return redirect()->route('admin.ustawienia.edit')
            ->with('status', 'Wysłano wiadomość testową na adres '.$data['test_email'].'.');
    }

    /** Generuje nowy losowy token furtki awaryjnej i zapisuje w ustawieniach. */
    public function regenerateEmergencyToken(): RedirectResponse
    {
        SiteSetting::current()->update(['emergency_login_token' => Str::random(24)]);

        return redirect()->route('admin.ustawienia.edit', ['tab' => 'login'])
            ->with('status', 'Nowy adres dostępu awaryjnego został wygenerowany.');
    }

    /** Wysyła ręczne powiadomienie push do wszystkich subskrybentów. */
    public function sendPush(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'push_title' => ['required', 'string', 'max:80'],
            'push_body'  => ['required', 'string', 'max:200'],
            'push_url'   => ['nullable', 'url', 'max:500'],
        ]);

        $service = new \App\Services\PushNotificationService();
        $count = $service->send(
            $data['push_title'],
            $data['push_body'],
            $data['push_url'] ?? '/'
        );

        return redirect()->back()->with('status', "Powiadomienie push wysłane do {$count} subskrybentów.");
    }

    public function envEdit()
    {
        $path  = base_path('.env');
        $lines = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];

        return view('admin.settings.env', ['lines' => $lines]);
    }

    public function envUpdate(Request $request): RedirectResponse
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            return back()->with('error', 'Plik .env nie istnieje.');
        }

        $incoming = $request->input('env', []);
        $raw      = file($path, FILE_IGNORE_NEW_LINES);
        $output   = [];

        foreach ($raw as $line) {
            if (preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)/i', $line, $m)) {
                $key = $m[1];
                if (array_key_exists($key, $incoming)) {
                    $val = $incoming[$key];
                    // Re-quote if value contains spaces or special chars and original was quoted
                    if (preg_match('/\s/', $val) || str_contains($val, '#')) {
                        $val = '"' . addcslashes($val, '"\\') . '"';
                    }
                    $output[] = $key . '=' . $val;
                    continue;
                }
            }
            $output[] = $line;
        }

        file_put_contents($path, implode("\n", $output) . "\n");

        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        return back()->with('status', 'Plik .env został zaktualizowany. Konfiguracja wyczyszczona.');
    }
}
