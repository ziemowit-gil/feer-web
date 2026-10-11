<?php

namespace App\Http\Controllers;

use App\Models\BrandAccessUser;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\SzoKomunikaty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Wyświetlanie podstron z kontrolą dostępu (hasło / MS365), obsługą podglądu szkicu
 * oraz formularzem odblokowywania chronionych treści.
 *
 * Metody: show(), unlock().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class PageController extends Controller
{
    /** Slug automatycznie zakładanej strony „Strefa współpracownika". */
    private const STREFA_SLUG = 'strefa-wspolpracownika-feer';

    /** Wyświetla podstronę z kontrolą dostępu (hasło / MS365) i obsługą podglądu szkicu. */
    public function show(Request $request, Page $page)
    {
        $preview = $this->isPreviewRequest($request);
        $isLive = $page->is_published && ($page->publish_at === null || $page->publish_at->isPast());
        abort_unless($isLive || $preview, 404);

        // Strona typu „Przekierowanie” w trybie automatycznym kieruje od razu (podgląd z panelu pokazuje stronę z informacją).
        if ($page->isRedirect() && ! $preview && ($page->typeData()['redirect_mode'] ?? 'notice') === 'auto' && ($target = $page->redirectTarget())) {
            return redirect()->away($target, 301);
        }

        // Strona typu „Kontakt” ma stały układ i własny kontroler.
        if ($page->type === 'contact') {
            return app(ContactController::class)->render();
        }

        // Strona z ograniczonym dostępem (typ wewnętrzny albo poziom dostępu ustawiony na stronie lub jej przodku).
        if ($page->requiresAccess() && ! $page->accessGranted()) {
            if (! $page->isAccessRestricted()) {
                $level = $page->effectiveAccessLevel();
                if ($level === 'member') {
                    session(['url.intended' => url()->current()]);

                    return redirect()->route('member.login');
                }
                if (in_array($level, ['panel', 'groups'], true) && ! auth('web')->check()) {
                    session(['url.intended' => url()->current()]);

                    return redirect()->route('login');
                }
                if ($level === 'password') {
                    return response()->view('page.locked', compact('page'), 403);
                }

                return response()->view('page.locked-role', compact('page'), 403);
            }

            if ($page->isBrandAssets()) {
                return redirect()->route('page.brand-login', $page);
            }

            if ($page->access_mode === 'microsoft') {
                // Osobne logowanie do strefy wewnętrznej (MS365, guard „member").
                session(['url.intended' => url()->current()]);

                return redirect()->route('member.login');
            }

            return response()->view('page.locked', compact('page'), 403);
        }

        $data = ['page' => $page, 'preview' => $preview];

        // W strefie współpracownika dokładamy komunikaty z systemu SZO. Tu jest już
        // po kontroli dostępu, więc trafią tylko do zalogowanego współpracownika.
        if ($page->slug === self::STREFA_SLUG) {
            $settings = SiteSetting::current();
            $szoUrl = $settings->szoKomunikatyUrl();
            $data['szoKomunikaty'] = $szoUrl
                ? SzoKomunikaty::fetch($szoUrl)
                : ['ok' => false, 'items' => []];
            // Adres pełnego Panelu Współpracownika (SZO) do komunikatu na stronie.
            $data['szoPanelUrl'] = $settings->szoPanelUrl();
        }

        if ($page->isCooperation() && SiteSetting::current()->site_template === 'federation') {
            return response()->view('templates.federation.cooperation', $data);
        }

        // Szablon „Strona działu": aktualności (opcjonalnie z jednej kategorii) do sekcji pod sliderem.
        if ($page->page_template === 'section' && $page->usesStandardLayout()) {
            $data['sectionPosts'] = $this->sectionPosts($page);
            $data['sectionNewsletter'] = $this->sectionNewsletter($page);
        }

        $view = match ($page->page_template) {
            'wide'    => 'page.show-wide',
            'hero'    => 'page.show-hero',
            'landing' => 'page.show-landing',
            'portal'  => 'page.show-portal',
            // Strona działu ma sens tylko dla stron z treścią; typy z własnym układem zostają przy domyślnym.
            'section' => $page->usesStandardLayout() ? 'page.show-section' : 'page.show',
            'minimal' => 'page.show-minimal',
            default   => 'page.show',
        };

        return response()->view($view, $data);
    }

    /**
     * Wpisy dla szablonu „Strona działu" (layout_data): źródło `news` (aktualności,
     * opcjonalnie z kategorii) albo `blog` (Wiem FEER — osobna baza, bez zdjęć).
     * Zwraca ujednolicone wiersze, żeby widok nie zależał od źródła.
     *
     * @return array{items: \Illuminate\Support\Collection, heading: string, allUrl: ?string, allLabel: string}
     */
    private function sectionPosts(Page $page): array
    {
        $settings = $page->layout_data ?? [];
        $count = min((int) ($settings['news_count'] ?? 4), 13);
        $source = ($settings['source'] ?? 'news') === 'blog' ? 'blog' : 'news';
        $site = SiteSetting::current();
        $empty = ['items' => collect(), 'heading' => '', 'allUrl' => null, 'allLabel' => ''];

        if ($count <= 0) {
            return $empty;
        }

        if ($source === 'blog') {
            if (! $site->isModuleEnabled('blog') || ! class_exists(\Modules\Blog\Models\BlogArticle::class)) {
                return $empty;
            }
            try {
                $articles = \Modules\Blog\Models\BlogArticle::query()->published()->where('is_disabled', false)
                    ->latest('published_at')->limit($count)->get();
            } catch (\Illuminate\Database\QueryException) {
                // Baza bloga (osobny plik SQLite) może nie istnieć w danym środowisku.
                return $empty;
            }

            return [
                'items' => $articles->map(fn ($a) => [
                    'title' => $a->title, 'url' => route('blog.show', $a), 'excerpt' => $a->excerpt,
                    'date' => $a->published_at ?? $a->created_at, 'image' => null, 'meta' => $a->author_name,
                ]),
                'heading' => 'Wiem FEER', 'allUrl' => route('blog.index'), 'allLabel' => 'Wszystkie wpisy Wiem FEER',
            ];
        }

        if (! $site->isModuleEnabled('news')) {
            return $empty;
        }
        $categoryId = $settings['news_category_id'] ?? null;
        $news = \App\Models\News::query()->published()->forCurrentSite()
            ->when($categoryId, fn ($q, $id) => $q->where('news_category_id', $id))
            ->with('category')->latest('published_at')->limit($count)->get();
        $category = $categoryId ? \App\Models\NewsCategory::find($categoryId) : null;

        return [
            'items' => $news->map(fn ($n) => [
                'title' => $n->title, 'url' => route('news.show', $n), 'excerpt' => $n->excerpt,
                'date' => $n->published_at, 'image' => $n->imageUrlOrDefault(), 'meta' => $n->category?->name,
            ]),
            'heading' => 'Aktualności',
            'allUrl' => route('news.index', $category ? ['kategoria' => $category->slug] : []),
            'allLabel' => 'Wszystkie aktualności'.($category ? ': '.$category->name : ''),
        ];
    }

    /**
     * Panel „Newsletter" szablonu „Strona działu": teksty ze strony (layout_data),
     * a gdy puste — z firmowego, domyślnego formularza modułu Newsletter.
     * Null, gdy panel wyłączony, moduł nieaktywny albo brak aktywnego formularza.
     *
     * @return array{title: string, text: ?string, button: string, url: string}|null
     */
    public static function sectionNewsletterDefaults(): ?array
    {
        $site = SiteSetting::current();
        if (! $site->isModuleEnabled('newsletter') || ! class_exists(\Modules\Newsletter\Models\NewsletterForm::class)) {
            return null;
        }
        try {
            $form = \Modules\Newsletter\Models\NewsletterForm::defaultFor($site->id);
        } catch (\Illuminate\Database\QueryException) {
            return null;
        }
        if (! $form || ! $form->is_active) {
            return null;
        }

        return [
            'title' => $form->heading ?: ($form->eyebrow ?: 'Newsletter'),
            'text' => $form->lead,
            // „Zapisz się" z formularza dopowiadamy do „Zapisz się na newsletter" — przycisk stoi sam w kolumnie.
            'button' => match (trim((string) $form->button_label)) {
                '', 'Zapisz się', 'Zapisz' => 'Zapisz się na newsletter',
                default => $form->button_label,
            },
            'url' => $form->is_default ? route('newsletter.show') : route('newsletter.form', ['form' => $form->slug]),
        ];
    }

    private function sectionNewsletter(Page $page): ?array
    {
        $settings = $page->layout_data ?? [];
        if (! ($settings['newsletter'] ?? true) || ! ($defaults = self::sectionNewsletterDefaults())) {
            return null;
        }

        return [
            'title' => filled($settings['newsletter_title'] ?? null) ? $settings['newsletter_title'] : $defaults['title'],
            'text' => filled($settings['newsletter_text'] ?? null) ? $settings['newsletter_text'] : $defaults['text'],
            'button' => filled($settings['newsletter_button'] ?? null) ? $settings['newsletter_button'] : $defaults['button'],
            'url' => $defaults['url'],
        ];
    }

    /** Formularz logowania dla strony z zasobami marki (indywidualny login+hasło). */
    public function brandLogin(Request $request, Page $page)
    {
        $isLive = $page->is_published && ($page->publish_at === null || $page->publish_at->isPast());
        abort_unless($isLive && $page->isBrandAssets(), 404);

        if ($page->accessGranted()) {
            return redirect()->route('page.show', $page);
        }

        return response()->view('page.brand-locked', compact('page'));
    }

    /** Weryfikacja loginu i hasła do strony z zasobami marki. */
    public function brandLoginPost(Request $request, Page $page)
    {
        $isLive = $page->is_published && ($page->publish_at === null || $page->publish_at->isPast());
        abort_unless($isLive && $page->isBrandAssets(), 404);

        $data = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = BrandAccessUser::where('page_id', $page->id)
            ->where('login', $data['login'])
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['login' => 'Nieprawidłowy login lub hasło.'])
                ->withInput(['login' => $data['login']]);
        }

        session(["brand_access_{$page->id}" => $user->id]);
        $user->update(['last_login_at' => now()]);

        return redirect()->route('page.show', $page);
    }

    /** Wylogowanie ze strony z zasobami marki. */
    public function brandLogout(Request $request, Page $page)
    {
        session()->forget("brand_access_{$page->id}");

        return redirect()->route('page.brand-login', $page);
    }

    /** Wyświetla stronę osoby pod adresem /{parentSlug}/osoba/{personSlug}. */
    public function showPerson(Request $request, string $parentSlug, string $personSlug): mixed
    {
        $page = Page::where('slug', "{$parentSlug}/osoba/{$personSlug}")->firstOrFail();

        return $this->show($request, $page);
    }

    /** Odblokowanie strony wewnętrznej hasłem (zapis w sesji). */
    public function unlock(Request $request, Page $page)
    {
        $source = $page->isAccessRestricted() ? ($page->access_mode === 'password' ? $page : null) : $page->accessSource();
        abort_unless($source && ($page->isAccessRestricted() || $source->access_level === 'password'), 404);

        $request->validate(['access_password' => ['required', 'string']]);

        if (! Hash::check($request->input('access_password'), (string) $source->access_password)) {
            return back()->withErrors(['access_password' => 'Nieprawidłowe hasło.']);
        }

        $unlocked = session('unlocked_pages', []);
        $unlocked[] = $source->id;
        session(['unlocked_pages' => array_values(array_unique($unlocked))]);

        return redirect()->route('page.show', $page);
    }
}
