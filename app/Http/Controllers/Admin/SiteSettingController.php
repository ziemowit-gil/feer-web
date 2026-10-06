<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'site_name_genitive' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:2000'],
            'brand_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_color_2' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_color_3' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_color_4' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'header_layout' => [
                'required',
                Rule::in(array_keys(SiteSetting::HEADER_LAYOUTS)),
                // Wartość już zapisana pozostaje dozwolona, choćby została zablokowana
                // później — inaczej samo zapisanie reszty ustawień by się wywalało.
                Rule::notIn(array_diff(SiteSetting::current()->blocked_options['header_layouts'] ?? [], [SiteSetting::current()->header_layout])),
            ],
            'content_editor' => ['required', Rule::in(array_keys(SiteSetting::EDITORS))],
            'microsoft_login_enabled' => ['sometimes', 'boolean'],
            'microsoft_only_login' => ['sometimes', 'boolean'],
            'microsoft_client_id' => ['nullable', 'string', 'max:255'],
            'microsoft_client_secret' => ['nullable', 'string', 'max:1000'],
            'microsoft_tenant_id' => ['nullable', 'string', 'max:255'],
            'google_login_enabled' => ['sometimes', 'boolean'],
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:1000'],
            'member_login_enabled' => ['sometimes', 'boolean'],
            'member_allowed_domains' => ['nullable', 'string', 'max:500'],
            'szo_api_url' => ['nullable', 'url', 'max:255'],
            'cleantalk_enabled' => ['sometimes', 'boolean'],
            'cleantalk_access_key' => ['nullable', 'string', 'max:255'],
            'szo_enabled' => ['sometimes', 'boolean'],
            'szo_token' => ['nullable', 'string', 'max:1000'],
            'szo_default_form' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9\-_]*$/i'],
            'szo_donation_form' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9\-_]*$/i'],
            'szo_timeout' => ['nullable', 'integer', 'min:1', 'max:60'],
            'yubico_client_id' => ['nullable', 'string', 'max:255'],
            'yubico_secret_key' => ['nullable', 'string', 'max:1000'],
            'two_factor_required_admins' => ['sometimes', 'boolean'],
            'przelewy24_sandbox' => ['nullable', 'boolean'],
            'przelewy24_merchant_id' => ['nullable', 'string', 'max:255'],
            'przelewy24_pos_id' => ['nullable', 'string', 'max:255'],
            'przelewy24_crc' => ['nullable', 'string', 'max:1000'],
            'przelewy24_api_key' => ['nullable', 'string', 'max:1000'],
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
            'msgraph_tenant_id' => ['nullable', 'string', 'max:100'],
            'msgraph_client_id' => ['nullable', 'string', 'max:100'],
            'msgraph_client_secret' => ['nullable', 'string', 'max:1000'],
            'msgraph_sender' => ['nullable', 'email', 'max:255'],
            'msgraph_save_to_sent' => ['sometimes', 'boolean'],
            'forms_mail_via_msgraph' => ['sometimes', 'boolean'],
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
            'infobar_show_date' => ['sometimes', 'boolean'],
            'infobar_show_nameday' => ['sometimes', 'boolean'],
            'office_show_account' => ['sometimes', 'boolean'],
            'office_show_search' => ['sometimes', 'boolean'],
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
            'donation_amounts' => ['nullable', 'string', 'max:100', 'regex:/^[0-9\s,;]*$/'],
            'donation_intro' => ['nullable', 'string', 'max:2000'],
            'donation_headline' => ['nullable', 'string', 'max:160'],
            'donation_impacts' => ['nullable', 'string', 'max:1500'],
            'donation_use_note' => ['nullable', 'string', 'max:1500'],
            'donation_reports_url' => ['nullable', 'string', 'max:255'],
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
            'wide_mission_social_3' => ['nullable', Rule::in(array_keys(SiteSetting::SOCIAL_KEYS))],
            'wide_mission_layout' => ['nullable', Rule::in(array_keys(SiteSetting::WIDE_MISSION_LAYOUTS))],
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
            'wide_mission_nav_style' => ['nullable', 'in:brand_bar,icons_white,pills'],
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
            'support_faq' => ['nullable', 'string', 'max:4000'],
            'enabled_modules' => ['sometimes', 'array'],
            'enabled_modules.*' => ['string', Rule::in(array_keys(SiteSetting::MODULES))],
            'section_order_json' => ['sometimes', 'nullable', 'string'],
            'events_home_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'site_template' => [
                'nullable',
                Rule::in(array_keys(SiteSetting::SITE_TEMPLATES)),
                // Wartość już zapisana pozostaje dozwolona, choćby została zablokowana
                // później — inaczej samo zapisanie reszty ustawień by się wywalało.
                Rule::notIn(array_diff(SiteSetting::current()->blocked_options['site_templates'] ?? [], [SiteSetting::current()->site_template])),
            ],
            'municipality_shortcuts_slug'      => ['nullable', 'string', 'max:255'],
            'municipality_carousel_title'      => ['nullable', 'string', 'max:255'],
            'municipality_weather_lat'         => ['nullable', 'numeric', 'between:-90,90'],
            'municipality_weather_lon'         => ['nullable', 'numeric', 'between:-180,180'],
            'municipality_show_google_translate' => ['sometimes', 'boolean'],
            'federation_colorful_nav' => ['sometimes', 'boolean'],
            'federation_colorful_nav_items' => ['nullable', 'array'],
            'federation_colorful_nav_items.*' => ['integer', 'between:0,3'],
            'federation_show_org_spotlight' => ['sometimes', 'boolean'],
            'federation_show_members_banner' => ['sometimes', 'boolean'],
            'federation_hero_heading' => ['nullable', 'string', 'max:255'],
            'federation_hero_intro' => ['nullable', 'string'],
            'federation_hero_tiles' => ['nullable', 'array'],
            'federation_hero_tiles.*.title' => ['nullable', 'string', 'max:100'],
            'federation_hero_tiles.*.value' => ['nullable', 'string', 'max:20'],
            'federation_hero_tiles.*.icon' => ['nullable', 'string', 'max:60'],
            'federation_hero_tiles.*.color' => ['nullable', 'integer', 'between:1,4'],
            'federation_hero_tiles.*.wide' => ['sometimes', 'boolean'],
            'federation_join_benefits' => ['nullable', 'array'],
            'federation_join_benefits.*.title' => ['nullable', 'string', 'max:100'],
            'federation_join_benefits.*.text' => ['nullable', 'string', 'max:255'],
            'federation_join_benefits.*.icon' => ['nullable', 'string', 'max:60'],
            'ngo_3_stats' => ['nullable', 'array'],
            'ngo_3_stats.*.value' => ['nullable', 'string', 'max:20'],
            'ngo_3_stats.*.label' => ['nullable', 'string', 'max:100'],
            'ngo_3_stats.*.icon' => ['nullable', 'string', 'max:60'],
            'vm_intro_heading' => ['nullable', 'string', 'max:255'],
            'vm_intro_text' => ['nullable', 'string'],
            'vm_intro_buttons' => ['nullable', 'array', 'max:6'],
            'vm_intro_buttons.*.label' => ['nullable', 'string', 'max:60'],
            'vm_intro_buttons.*.url' => ['nullable', 'string', 'max:255', 'not_regex:/^\s*(javascript|data):/i'],
            'vm_knowledge_page_id' => ['nullable', 'integer', Rule::exists('pages', 'id')],
            'vm_newsletter_text' => ['nullable', 'string', 'max:500'],
            'vm_header_badge_alt' => ['nullable', 'string', 'max:255'],
            'vm_footer_note' => ['nullable', 'string', 'max:1000'],
            'vm_header_badge' => ['nullable', 'image', 'max:2048'],
            'vm_knowledge_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['allow_indexing'] = $request->boolean('allow_indexing');
        $data['logo_only'] = $request->boolean('logo_only');
        $data['maintenance_mode'] = $request->boolean('maintenance_mode');
        $data['municipality_show_google_translate'] = $request->boolean('municipality_show_google_translate');
        $data['federation_colorful_nav'] = $request->boolean('federation_colorful_nav');
        $data['federation_colorful_nav_items'] = $request->input('federation_colorful_nav_items') ?: null;
        $data['federation_show_org_spotlight'] = $request->boolean('federation_show_org_spotlight');
        $data['federation_show_members_banner'] = $request->boolean('federation_show_members_banner');
        $data['site_url'] = filled($data['site_url'] ?? null) ? rtrim($data['site_url'], '/') : null;
        $data['microsoft_login_enabled'] = $request->boolean('microsoft_login_enabled');
        $data['microsoft_only_login'] = $request->boolean('microsoft_only_login');
        $data['google_login_enabled'] = $request->boolean('google_login_enabled');
        $data['member_login_enabled'] = $request->boolean('member_login_enabled');
        $data['two_factor_required_admins'] = $request->boolean('two_factor_required_admins');
        // '' (nierozstrzygnięte w selekcie) = dziedzicz z .env — patrz SiteSetting::przelewy24Config().
        $data['przelewy24_sandbox'] = $request->filled('przelewy24_sandbox') ? $request->boolean('przelewy24_sandbox') : null;

        // Puste pole sekretu = zostaw zapisany (nie renderujemy go w formularzu).
        if (blank($data['microsoft_client_secret'] ?? null)) {
            unset($data['microsoft_client_secret']);
        }
        if (blank($data['google_client_secret'] ?? null)) {
            unset($data['google_client_secret']);
        }
        // Puste pole klucza Yubico = zostaw zapisane (analogicznie do sekretu Microsoft).
        if (blank($data['yubico_secret_key'] ?? null)) {
            unset($data['yubico_secret_key']);
        }
        // Puste hasło SMTP = zostaw zapisane (analogicznie do sekretu Microsoft).
        if (blank($data['mail_password'] ?? null)) {
            unset($data['mail_password']);
        }
        // Puste pola CRC/API key Przelewy24 = zostaw zapisane (analogicznie do sekretu Microsoft).
        // Pusty klucz CleanTalk = zostaw zapisany (jak hasło SMTP).
        if (blank($data['cleantalk_access_key'] ?? null)) {
            unset($data['cleantalk_access_key']);
        }
        $data['cleantalk_enabled'] = $request->boolean('cleantalk_enabled');
        // Pusty token SZO = zostaw zapisany (jak sekret Microsoft).
        if (blank($data['szo_token'] ?? null)) {
            unset($data['szo_token']);
        }
        $data['szo_enabled'] = $request->boolean('szo_enabled');
        if (blank($data['szo_timeout'] ?? null)) {
            $data['szo_timeout'] = null;
        }
        if (blank($data['przelewy24_crc'] ?? null)) {
            unset($data['przelewy24_crc']);
        }
        if (blank($data['przelewy24_api_key'] ?? null)) {
            unset($data['przelewy24_api_key']);
        }
        if (blank($data['unsplash_access_key'] ?? null)) {
            unset($data['unsplash_access_key']);
        }
        $data['mail_encryption'] = filled($data['mail_encryption'] ?? null) ? $data['mail_encryption'] : null;
        // Pusty sekret Graph = zostaw zapisany (jak hasło SMTP).
        if (blank($data['msgraph_client_secret'] ?? null)) {
            unset($data['msgraph_client_secret']);
        }
        $data['msgraph_save_to_sent'] = $request->boolean('msgraph_save_to_sent');
        $data['forms_mail_via_msgraph'] = $request->boolean('forms_mail_via_msgraph');
        $data['wide_mission_nav_hover_white'] = $request->boolean('wide_mission_nav_hover_white');
        $data['wide_mission_nav_active_white'] = $request->boolean('wide_mission_nav_active_white');
        $data['wide_mission_nav_icons_white'] = $request->boolean('wide_mission_nav_icons_white');
        $data['show_topbar_bip'] = $request->boolean('show_topbar_bip');
        $data['show_topbar_social'] = $request->boolean('show_topbar_social');
        $data['infobar_show_date'] = $request->boolean('infobar_show_date');
        $data['infobar_show_nameday'] = $request->boolean('infobar_show_nameday');
        $data['office_show_account'] = $request->boolean('office_show_account');
        $data['office_show_search'] = $request->boolean('office_show_search');
        $data['contact_show_form'] = $request->boolean('contact_show_form');
        $data['contact_show_bank_accounts'] = $request->boolean('contact_show_bank_accounts');
        $data['contact_show_coordinators'] = $request->boolean('contact_show_coordinators');
        $data['support_show_partners'] = $request->boolean('support_show_partners');
        $data['cookie_banner_enabled'] = $request->boolean('cookie_banner_enabled');
        // Poza domeną feer.org.pl kredyt CMS w stopce nie może zostać ukryty
        // (niezależnie od tego, co przyszło w żądaniu).
        $data['show_cms_credit'] = str_contains($request->getHost(), 'feer.org.pl')
            ? $request->boolean('show_cms_credit')
            : true;

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

        // Kafelki hero (szablon "federation"): odrzucamy wiersze bez tytułu.
        $data['federation_hero_tiles'] = collect($request->input('federation_hero_tiles', []))
            ->map(fn ($t) => [
                'title' => trim((string) ($t['title'] ?? '')),
                'value' => filled($t['value'] ?? null) ? trim((string) $t['value']) : null,
                'icon' => filled($t['icon'] ?? null) ? trim((string) $t['icon']) : null,
                'color' => (int) ($t['color'] ?? 1),
                'wide' => filled($t['wide'] ?? null) && $t['wide'] !== '0',
            ])
            ->filter(fn ($t) => $t['title'] !== '')
            ->values()
            ->all() ?: null;

        // Kafelki „Dlaczego warto?" (Dołącz do nas, szablon "federation"): odrzucamy wiersze bez tytułu.
        $data['federation_join_benefits'] = collect($request->input('federation_join_benefits', []))
            ->map(fn ($b) => [
                'title' => trim((string) ($b['title'] ?? '')),
                'text' => trim((string) ($b['text'] ?? '')),
                'icon' => filled($b['icon'] ?? null) ? trim((string) $b['icon']) : 'fa-circle-check',
            ])
            ->filter(fn ($b) => $b['title'] !== '')
            ->values()
            ->all() ?: null;

        // Pasek statystyk (strona główna, szablon "ngo_3"): odrzucamy wiersze bez wartości.
        $data['ngo_3_stats'] = collect($request->input('ngo_3_stats', []))
            ->map(fn ($s) => [
                'value' => trim((string) ($s['value'] ?? '')),
                'label' => trim((string) ($s['label'] ?? '')),
                'icon' => filled($s['icon'] ?? null) ? trim((string) $s['icon']) : null,
            ])
            ->filter(fn ($s) => $s['value'] !== '')
            ->values()
            ->all() ?: null;

        // Przyciski bloku powitalnego (szablon "vm"): tylko wiersze z etykietą i adresem.
        $data['vm_intro_buttons'] = collect($request->input('vm_intro_buttons', []))
            ->map(fn ($b) => [
                'label' => trim((string) ($b['label'] ?? '')),
                'url' => trim((string) ($b['url'] ?? '')),
            ])
            ->filter(fn ($b) => $b['label'] !== '' && $b['url'] !== '')
            ->values()
            ->all() ?: null;

        unset($data['vm_header_badge'], $data['remove_vm_header_badge'], $data['vm_knowledge_image'], $data['remove_vm_knowledge_image']);
        unset($data['logo'], $data['remove_logo'], $data['og_image'], $data['remove_og_image'], $data['support_image'], $data['remove_support_image'], $data['support_gallery'], $data['remove_support_gallery'], $data['news_default_image'], $data['remove_news_default_image'], $data['bip_logo'], $data['remove_bip_logo'], $data['enabled_modules'], $data['section_order_json']);

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

        foreach (['vm_header_badge', 'vm_knowledge_image'] as $vmCollection) {
            if ($request->hasFile($vmCollection)) {
                $settings->addMediaFromRequest($vmCollection)->toMediaCollection($vmCollection);
            } elseif ($request->boolean('remove_' . $vmCollection)) {
                $settings->clearMediaCollection($vmCollection);
            }
        }

        // Osobna galeria strony „Wesprzyj nas": najpierw usuwamy odznaczone
        // zdjęcia, potem dokładamy nowo wgrane (kolekcja wielozdjęciowa).
        foreach ((array) $request->input('remove_support_gallery', []) as $mediaId) {
            $settings->media()->where('collection_name', 'support_gallery')->where('id', (int) $mediaId)->each->delete();
        }
        foreach ((array) $request->file('support_gallery', []) as $file) {
            $settings->addMedia($file)->toMediaCollection('support_gallery');
        }

        $status = ($colorWasAdjusted
            ? "Ustawienia zostały zapisane. Kolor przewodni był zbyt jasny dla kontrastu WCAG, więc został automatycznie przyciemniony do {$data['brand_color']}."
            : 'Ustawienia zostały zapisane.');

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
            'via' => ['nullable', Rule::in(['default', 'msgraph'])],
        ]);

        $settings = SiteSetting::current();
        $viaGraph = ($data['via'] ?? 'default') === 'msgraph';

        if ($viaGraph && ! $settings->msGraphConfigured()) {
            return redirect()->route('admin.ustawienia.edit', ['tab' => 'mail'])
                ->with('error', 'Microsoft Graph nie jest skonfigurowany: uzupełnij ID tenanta (nie „common”), ID aplikacji, sekret i skrzynkę nadawczą, a następnie zapisz ustawienia.');
        }

        try {
            $mailer = $viaGraph ? Mail::mailer('msgraph') : Mail::mailer();
            $mailer->raw(
                'To jest testowa wiadomość ze strony '.$settings->site_name.' '
                .($viaGraph ? '(wysłana przez Microsoft Graph). ' : '. ')
                .'Jeśli ją widzisz, konfiguracja poczty działa poprawnie.',
                fn ($message) => $message->to($data['test_email'])->subject('Test konfiguracji poczty'.($viaGraph ? ' (Microsoft Graph)' : ''))
            );
        } catch (\Throwable $e) {
            return redirect()->route('admin.ustawienia.edit', ['tab' => 'mail'])
                ->with('error', 'Nie udało się wysłać wiadomości testowej: '.$e->getMessage());
        }

        return redirect()->route('admin.ustawienia.edit', ['tab' => 'mail'])
            ->with('status', 'Wysłano wiadomość testową'.($viaGraph ? ' przez Microsoft Graph' : '').' na adres '.$data['test_email'].'.');
    }

    /** Sprawdza klucz CleanTalk (wiadomość testowa, którą usługa zawsze ocenia jako spam). */
    public function cleantalkCheck(Request $request)
    {
        $data = $request->validate(['key' => ['nullable', 'string', 'max:255']]);
        $key = filled($data['key'] ?? null) ? $data['key'] : (string) config('cleantalk.apikey');

        return response()->json(\App\Support\CleanTalkGuard::diagnose($key));
    }

    /**
     * Sprawdza połączenie z SZO: adres, token i uprawnienie do listy formularzy
     * (GET /api/v1/forms.php). Puste pola uzupełniane są zapisaną konfiguracją,
     * więc można testować dane jeszcze niezapisane.
     */
    public function szoCheck(Request $request)
    {
        $data = $request->validate([
            'url' => ['nullable', 'string', 'max:255'],
            'token' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json(\App\Services\SzoClient::diagnose(
            filled($data['url'] ?? null) ? $data['url'] : (string) config('szo.url'),
            filled($data['token'] ?? null) ? $data['token'] : (string) config('szo.token'),
        ));
    }

    /** Autokonfigurator poczty: rozpoznaje dostawcę po adresie e-mail i zwraca podpowiedź ustawień (JSON). */
    public function mailDetect(Request $request, \App\Services\MailProviderDetector $detector)
    {
        // Walidacja ręczna: odpowiedź zawsze jest JSON-em 422 (zwykłe validate()
        // przy braku nagłówka Accept zwróciłoby przekierowanie HTML, którego
        // skrypt kreatora nie umie odczytać).
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), ['email' => ['required', 'email', 'max:255']], [
            'email.required' => 'Wpisz adres e-mail skrzynki nadawczej.',
            'email.email' => 'Wpisz poprawny adres e-mail.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        return response()->json($detector->detect($request->input('email')));
    }

    /**
     * Sprawdza połączenie z Microsoft Graph (token + uprawnienie Mail.Send)
     * bez wysyłania wiadomości. Puste pola formularza uzupełniane są zapisaną
     * konfiguracją, więc można sprawdzić dane jeszcze niezapisane.
     */
    public function mailGraphCheck(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'string', 'max:100'],
            'client_id' => ['nullable', 'string', 'max:100'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
            'sender' => ['nullable', 'string', 'max:255'],
        ]);

        $saved = SiteSetting::current()->msGraphConfig();
        $config = [];
        foreach (['tenant_id', 'client_id', 'client_secret', 'sender'] as $key) {
            $config[$key] = filled($data[$key] ?? null) ? $data[$key] : ($saved[$key] ?? null);
        }

        return response()->json(\App\Mail\Transport\MicrosoftGraphTransport::diagnose($config));
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
