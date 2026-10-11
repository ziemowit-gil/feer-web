<?php

namespace App\Support;

use App\Models\BipDocument;
use App\Models\NavItem;
use App\Models\SiteSetting;

/**
 * Zakładanie witryny sieci rodzaju „bip" (Admin\SiteController, BipSiteSeeder):
 * ustawienia BIP przejęte z witryny nadrzędnej i przeniesienie jej treści BIP.
 */
class BipSite
{
    /** Pola ustawień BIP przejmowane z witryny nadrzędnej przy zakładaniu witryny BIP. */
    public const INHERITED_FIELDS = ['bip_intro', 'bip_editor_name', 'bip_editor_email', 'bip_gov_url', 'bip_show_reports'];

    /** Atrybuty startowe witryny BIP — ustawienia BIP witryny nadrzędnej, tryb wbudowany. */
    public static function inheritedAttributes(SiteSetting $parent): array
    {
        $data = [];
        foreach (self::INHERITED_FIELDS as $field) {
            $data[$field] = $parent->{$field};
        }
        $data['bip_mode'] = 'internal';

        return $data;
    }

    /** Kopiuje logo BIP witryny nadrzędnej, gdy witryna BIP nie ma własnego. */
    public static function copyLogo(SiteSetting $parent, SiteSetting $site): void
    {
        if (! $site->getFirstMedia('bip_logo')) {
            $parent->getFirstMedia('bip_logo')?->copy($site, 'bip_logo');
        }
    }

    /**
     * Przenosi dokumenty BIP (także z kosza) i menu boczne BIP witryny nadrzędnej
     * do witryny BIP — dotychczasowy BIP staje się jej treścią.
     */
    public static function moveContent(SiteSetting $site): void
    {
        if (! $site->parent_site_id) {
            return;
        }

        BipDocument::withTrashed()->where('site_id', $site->parent_site_id)->update(['site_id' => $site->id]);
        NavItem::where('site_id', $site->parent_site_id)->where('location', 'bip')->update(['site_id' => $site->id]);
    }
}
