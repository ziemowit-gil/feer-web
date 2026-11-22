<?php

use App\Http\Controllers\Admin\KreatywniOfferController;
use App\Models\KreatywniOffer;
use App\Models\SiteSetting;
use App\Support\KreatywniTexts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Sekcja oferty na stronie głównej szablonu „kreatywni” ma pokazywać Mikroofertę: witrynom w tym szablonie, które
 * mają pusty moduł „Mikrooferta – Kreatywni”, przenosimy ofertę z tekstów szablonu (jak przycisk „Przenieś ofertę
 * z tekstów” w panelu i KreatywniSiteSeeder). Witryn z ofertami w module nie rusza; teksty szablonu zostają jako zapas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kreatywni_offers') || ! class_exists(KreatywniOffer::class) || ! class_exists(KreatywniTexts::class)) {
            return;
        }

        SiteSetting::query()->where('site_template', 'kreatywni')->get()->each(function (SiteSetting $site) {
            if (method_exists($site, 'trashed') && $site->trashed()) {
                return;
            }
            if (KreatywniOffer::withoutGlobalScopes()->where('site_id', $site->getKey())->exists()) {
                return;
            }
            $texts = KreatywniTexts::for($site);
            if ($texts->rows('offer_items') === [] && $texts->get('offer_price') === '') {
                return;
            }
            KreatywniOffer::withoutGlobalScopes()->create(
                KreatywniOfferController::fromTexts($texts) + ['site_id' => $site->getKey(), 'order' => 1, 'is_active' => true]
            );
        });
    }

    public function down(): void
    {
        // Oferty mogły zostać zmienione w panelu — nie usuwamy ich.
    }
};
