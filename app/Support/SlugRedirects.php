<?php

namespace App\Support;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Przekierowanie 301 po zmianie adresu (sluga) opublikowanej treści — stare linki
 * (z wyszukiwarek, maili, mediów społecznościowych) dalej prowadzą na właściwą stronę.
 *
 * - przekierowania wskazujące na stary adres przepinamy na nowy (bez łańcuchów),
 * - przekierowanie Z nowego adresu usuwamy (inaczej przejęłoby nową stronę — HandleRedirects
 *   działa przed routingiem),
 * - redaktor może z niego zrezygnować polem „skip_slug_redirect” w formularzu edycji.
 *
 * Komunikat dla redaktora trafia do sesji (slug_redirect) i pokazuje go układ panelu.
 */
class SlugRedirects
{
    /** Wywoływane z RedirectsOldSlug po zapisie modelu. */
    public static function afterUpdate(Model $model): void
    {
        if (! $model->wasChanged('slug') || blank($model->getOriginal('slug'))) {
            return;
        }
        if (request()->boolean('skip_slug_redirect') || ! self::wasPublic($model)) {
            return;
        }

        try {
            if (! Schema::hasTable('redirects')) {
                return;
            }
            $newUrl = $model->redirectTargetUrl();
            if (! $newUrl) {
                return;
            }
            $newPath = Redirect::normalizePath((string) parse_url($newUrl, PHP_URL_PATH));
            $oldPath = self::oldPath($newPath, (string) $model->slug, (string) $model->getOriginal('slug'));
            if (! $oldPath || $oldPath === $newPath) {
                return;
            }

            // Nowy adres nie może być źródłem przekierowania — inaczej strona byłaby nieosiągalna.
            Redirect::where('from_path', $newPath)->delete();
            // Dotychczasowe przekierowania na stary adres prowadzą teraz od razu na nowy.
            Redirect::where('to_url', $oldPath)->orWhere('to_url', url($oldPath))->update(['to_url' => $newPath]);
            Redirect::updateOrCreate(['from_path' => $oldPath], ['to_url' => $newPath, 'is_active' => true]);

            session()->flash('slug_redirect', "Adres zmienił się z {$oldPath} na {$newPath} — dodaliśmy przekierowanie 301, więc stare linki dalej działają.");
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Stary adres: nowy z podmienionym ostatnim członem (prefiks witryny i sekcji zostaje). */
    private static function oldPath(string $newPath, string $newSlug, string $oldSlug): ?string
    {
        $suffix = '/'.$newSlug;
        if (! str_ends_with($newPath, $suffix)) {
            return null;
        }

        return Redirect::normalizePath(substr($newPath, 0, -strlen($suffix)).'/'.$oldSlug);
    }

    /** Przekierowanie ma sens tylko wtedy, gdy stary adres był publiczny. */
    private static function wasPublic(Model $model): bool
    {
        return ! array_key_exists('is_published', $model->getAttributes()) || (bool) $model->getOriginal('is_published');
    }
}
