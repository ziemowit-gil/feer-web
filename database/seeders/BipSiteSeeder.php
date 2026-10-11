<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Support\BipSite;
use Illuminate\Database\Seeder;

/**
 * Zakłada BIP głównej witryny jako witrynę sieci (rodzaj „bip") pod adresem /bip
 * i przenosi do niej dotychczasowe dokumenty i menu boczne BIP. Idempotentny —
 * gdy witryna BIP już jest, tylko dociąga treść, która została na głównej witrynie.
 *
 *   php artisan db:seed --class=BipSiteSeeder
 *
 * Opcjonalnie BIP_SITE_DOMAIN=bip.przyklad.pl ustawia witrynie BIP własną domenę.
 */
class BipSiteSeeder extends Seeder
{
    public function run(): void
    {
        $parent = SiteSetting::whereNull('parent_site_id')->orderBy('id')->firstOrFail();
        $site = $parent->bipSite();

        if (! $site) {
            $slugTaken = SiteSetting::where('slug', 'bip')->exists();

            $site = SiteSetting::create([
                'site_name' => 'Biuletyn Informacji Publicznej',
                'tagline' => $parent->site_name,
                'slug' => $slugTaken ? null : 'bip',
                'domain' => env('BIP_SITE_DOMAIN') ?: null,
                'parent_site_id' => $parent->id,
                'site_kind' => 'bip',
                'site_template' => $parent->site_template,
                'brand_color' => $parent->brand_color ?: SiteSetting::DEFAULT_BRAND_COLOR,
            ] + BipSite::inheritedAttributes($parent));

            $this->command?->info('Założono witrynę BIP „'.$site->site_name.'" ('.current_site_url($site).').');
        } else {
            $this->command?->info('Witryna BIP już istnieje: „'.$site->site_name.'" ('.current_site_url($site).').');
        }

        BipSite::copyLogo($parent, $site);
        BipSite::moveContent($site);

        $this->command?->info('Dokumentów BIP w witrynie BIP: '.\App\Models\BipDocument::withTrashed()->where('site_id', $site->id)->count().'.');
    }
}
