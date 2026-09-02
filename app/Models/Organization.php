<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Organizacja członkowska federacji (szablon federation) — katalog na
 * podstronie "Organizacje" oraz jej indywidualna wizytówka.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class Organization extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** Limit zdjęć w galerii wizytówki, dodawanych przez organizację samodzielnie. */
    public const MAX_PHOTOS = 6;
    public const TYPES = [
        'Fundacja',
        'Stowarzyszenie',
        'Związek',
        'Towarzystwo',
        'Klub',
        'Koło',
        'Uniwersytet Trzeciego Wieku',
        'Inna forma prawna',
    ];

    /** Sfery pożytku publicznego (ustawa o działalności pożytku publicznego), z ikonami. */
    public const SPHERE_ICONS = [
        'Pomoc społeczna' => 'fa-hand-holding-heart',
        'Działalność charytatywna' => 'fa-hand-holding-dollar',
        'Ochrona i promocja zdrowia' => 'fa-heart-pulse',
        'Działalność na rzecz osób niepełnosprawnych' => 'fa-wheelchair',
        'Działalność na rzecz osób w wieku emerytalnym' => 'fa-person-cane',
        'Nauka, edukacja, oświata i wychowanie' => 'fa-graduation-cap',
        'Kultura, sztuka, ochrona dóbr kultury' => 'fa-masks-theater',
        'Wspieranie rodziny i pieczy zastępczej' => 'fa-people-roof',
        'Przeciwdziałanie uzależnieniom i patologiom społecznym' => 'fa-hand-holding-medical',
        'Wypoczynek dzieci i młodzieży' => 'fa-child-reaching',
        'Turystyka i krajoznawstwo' => 'fa-person-hiking',
        'Ekologia i ochrona zwierząt' => 'fa-paw',
        'Wspomaganie rozwoju wspólnot i społeczności lokalnych' => 'fa-people-group',
    ];

    protected $fillable = [
        'name', 'slug', 'town', 'type', 'spheres', 'description', 'bio',
        'website_url', 'facebook_url', 'instagram_url', 'email', 'phone', 'login', 'password', 'is_test', 'order',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_test' => 'boolean',
        'password' => 'hashed',
        'spheres' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Link do prostej mapy (wyszukiwanie miejscowości), bez fabrykowania precyzyjnych współrzędnych. */
    public function mapUrl(): string
    {
        return 'https://www.openstreetmap.org/search?query='.urlencode($this->town.', Małopolska');
    }

    /** Ikony dla wybranych sfer pożytku publicznego tej organizacji, w tej samej kolejności. */
    public function sphereIcons(): array
    {
        return array_map(fn ($sphere) => self::SPHERE_ICONS[$sphere] ?? 'fa-circle-info', $this->spheres ?? []);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(300)->format('webp')->nonQueued();
    }
}
