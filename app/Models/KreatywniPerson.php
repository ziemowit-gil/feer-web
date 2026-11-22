<?php

namespace App\Models;

use App\Support\KreatywniTexts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Osoba z mikro modułu „Osoby – Kreatywni” (panel: Treści → Strona główna → Osoby – Kreatywni). Pokazywana w sekcji
 * „Kto prowadzi warsztaty” na stronie „O nas” i w sekcji Zespół na stronie głównej szablonu „kreatywni”.
 * Bez tabeli (kod przed migracją) albo bez żadnej osoby witryny szablon bierze osoby z tekstów (teamRows()).
 */
class KreatywniPerson extends Model implements HasMedia
{
    use InteractsWithMedia;
    use Concerns\BelongsToSite;
    use Concerns\LogsActivity;

    protected $table = 'kreatywni_people';

    protected $fillable = ['site_id', 'name', 'role', 'bio', 'photo_alt', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('avatar')->width(480)->format('webp')->quality(85)->nonQueued();
    }

    /** Czy tabela modułu istnieje (push wdraża kod przed migracją). */
    public static function supported(): bool
    {
        return once(fn () => Schema::hasTable('kreatywni_people'));
    }

    public function photoUrl(): ?string
    {
        $media = $this->getFirstMedia('photo');

        return $media ? ($media->getAvailableUrl(['avatar']) ?: $media->getUrl()) : null;
    }

    public function initials(): string
    {
        $words = array_slice(preg_split('/[\s-]+/u', trim((string) $this->name)) ?: [], 0, 2);

        return mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), $words)));
    }

    /**
     * Osoby do sekcji zespołu: widoczne osoby modułu bieżącej witryny, a gdy modułu nie ma albo jest pusty — wiersze
     * z tekstów szablonu ($key: about_team albo team_items, „Imię | rola | opis”).
     *
     * @return list<array{name: string, role: string, bio: string, photo: ?string, alt: string, initials: string}>
     */
    public static function teamRows(KreatywniTexts $texts, string $key): array
    {
        if (self::supported() && self::forCurrentSite()->exists()) {
            return self::forCurrentSite()->where('is_active', true)->orderBy('order')->orderBy('id')->get()
                ->map(fn (self $p) => [
                    'name' => $p->name,
                    'role' => (string) $p->role,
                    'bio' => (string) $p->bio,
                    'photo' => $p->photoUrl(),
                    'alt' => (string) $p->photo_alt,
                    'initials' => $p->initials(),
                ])->all();
        }

        return array_map(function (array $row) {
            [$name, $role, $bio] = $row;

            return ['name' => $name, 'role' => $role, 'bio' => $bio, 'photo' => null, 'alt' => '', 'initials' => (new self(['name' => $name]))->initials()];
        }, $texts->rows($key, 3));
    }
}
