<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Pobiera zdjęcie wybrane w wybieraczu Unsplash (partial admin.partials.unsplash-picker) i zapisuje je w kolekcji
 * mediów modelu. Zgodnie z wytycznymi API Unsplash „pinguje" download_location i zapisuje autora w metadanych pliku.
 */
class UnsplashImport
{
    /** Reguły walidacji ukrytych pól wybieraka. */
    public static function rules(): array
    {
        return [
            'unsplash_full_url' => ['nullable', 'url'],
            'unsplash_download_location' => ['nullable', 'url'],
            'unsplash_author' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Czy żądanie niesie wybrane zdjęcie Unsplash. */
    public static function requested(Request $request): bool
    {
        return $request->filled('unsplash_full_url');
    }

    /** Dołącza wybrane zdjęcie do modelu (kolekcja jednozdjęciowa zastępuje poprzednie). */
    public static function attach(Model $model, Request $request, string $collection = 'image'): bool
    {
        if (! self::requested($request)) {
            return false;
        }

        $data = $request->validate(self::rules());

        $accessKey = SiteSetting::current()->unsplashAccessKey();
        if ($accessKey && ! empty($data['unsplash_download_location'])) {
            Http::withHeaders(['Authorization' => "Client-ID {$accessKey}"])->get($data['unsplash_download_location']);
        }

        $model->addMediaFromUrl($data['unsplash_full_url'])
            ->usingFileName(Str::random(20).'.jpg')
            ->withCustomProperties(['unsplash_author' => $data['unsplash_author'] ?? null])
            ->toMediaCollection($collection);

        return true;
    }
}
