<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dawne adresy subwitryn (adres skrócony albo własna domena sprzed zmiany), z których ResolveCurrentSite robi
     * przekierowanie 301 na obecny adres witryny.
     *
     * Kolumna nie nazywa się site_id celowo: SiteRemoval traktuje każdą tabelę z site_id jako treść witryny, więc
     * witryna z samymi przekierowaniami wymagałaby „Wymuś usunięcie”. Bez klucza obcego, jak reszta tabel witryn —
     * wiersze znikają razem z witryną w SiteSetting::deleted.
     */
    public function up(): void
    {
        if (Schema::hasTable('site_address_redirects')) {
            return;
        }

        Schema::create('site_address_redirects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('target_site_id')->index();
            $table->string('type', 16);
            $table->string('value');
            $table->timestamps();

            $table->unique(['type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_address_redirects');
    }
};
