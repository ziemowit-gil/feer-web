<?php

namespace Modules\Blog\Console;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Console\Command;

/**
 * Zakłada (idempotentnie) witrynę sieci „Wiem FEER" (slug z config('blog.site_slug')),
 * w której oprawie renderuje się blog: podwitryna witryny głównej, z jej kolorami,
 * szablonem i logo, oraz z własnym menu (wpisy, newsletter, powrót do witryny głównej).
 * Ponowne uruchomienie niczego nie dubluje — istniejącej witryny ani jej menu nie zmienia.
 */
class CreateBlogSiteCommand extends Command
{
    protected $signature = 'blog:witryna';

    protected $description = 'Zakłada witrynę sieci „Wiem FEER" (/wiem) dla bloga — idempotentnie';

    /** Ustawienia wyglądu kopiowane z witryny głównej. */
    private const COPIED = [
        'brand_color', 'brand_color_2', 'brand_color_3', 'brand_color_4', 'brand_skip_contrast',
        'ngo_color', 'site_template', 'contact_email',
    ];

    public function handle(): int
    {
        $slug = (string) config('blog.site_slug', 'wiem');
        $prefix = '/' . trim((string) config('blog.prefix', 'wiem'), '/');

        if ($slug === '') {
            $this->error('Brak sluga witryny (config blog.site_slug).');

            return self::FAILURE;
        }

        if (Page::query()->where('slug', $slug)->exists()) {
            $this->warn("Uwaga: istnieje strona o adresie /{$slug} — blog i tak ma pierwszeństwo, ale strona będzie niedostępna.");
        }

        $site = SiteSetting::query()->where('slug', $slug)->first();

        if ($site) {
            $this->info("Witryna „{$site->site_name}\" (/{$slug}) już istnieje — bez zmian.");
        } else {
            $main = SiteSetting::query()->whereNull('parent_site_id')->orderBy('id')->first() ?? SiteSetting::current();

            $data = ['site_name' => 'Wiem FEER', 'slug' => $slug, 'parent_site_id' => $main->id,
                'tagline' => 'Blog o dostępności, edukacji i empatii'];
            foreach (self::COPIED as $column) {
                if (array_key_exists($column, $main->getAttributes())) {
                    $data[$column] = $main->getAttribute($column);
                }
            }
            $data['brand_color'] = $data['brand_color'] ?? SiteSetting::DEFAULT_BRAND_COLOR;

            $site = SiteSetting::create($data);

            try {
                $main->getFirstMedia('logo')?->copy($site, 'logo');
            } catch (\Throwable $e) {
                $this->warn('Nie udało się skopiować logo: ' . $e->getMessage());
            }

            $this->info("Założono witrynę „Wiem FEER\" (/{$slug}, id {$site->id}).");
        }

        if (NavItem::query()->where('site_id', $site->id)->exists()) {
            $this->info('Menu witryny już istnieje — bez zmian.');

            return self::SUCCESS;
        }

        $items = [
            ['main', 'Wiem FEER', $prefix],
            ['main', 'Autorzy', $prefix . '/autorzy'],
            ['main', 'Newsletter', '/newsletter'],
            ['main', 'Fundacja FEER', '/'],
            ['footer', 'Wszystkie wpisy', $prefix],
            ['footer', 'Strona główna Fundacji FEER', '/'],
            ['footer', 'Kanał RSS', $prefix . '/rss.xml'],
            ['footer', 'Kontakt', '/kontakt'],
        ];

        foreach ($items as $order => [$location, $label, $url]) {
            NavItem::create([
                'site_id'   => $site->id,
                'label'     => $label,
                'url'       => $url,
                'type'      => 'link',
                'location'  => $location,
                'is_active' => true,
                'order'     => $order,
            ]);
        }

        $this->info('Dodano menu witryny (' . count($items) . ' pozycji).');

        return self::SUCCESS;
    }
}
