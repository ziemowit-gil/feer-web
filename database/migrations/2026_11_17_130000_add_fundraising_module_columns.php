<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł „Fundraising i darowizny”: rozbudowa istniejących tabel zamiast nowych,
 * równoległych rejestrów.
 *
 *  - campaigns: status, wyróżnienie, progi kwot, wpłaty cykliczne, tryb płatności
 *    (Przelewy24 / wplacam.ngo.pl / zewnętrzny link / bez wpłat), kwota zebrana poza systemem,
 *    nadpisania tekstów (JSON) i opis alternatywny zdjęcia.
 *  - donations: powiązanie z kampanią, typ (jednorazowa/cykliczna), dane firmy,
 *    dedykacja, źródło (online/ręczna), data wysyłki podziękowania; unikalny
 *    numer transakcji P24 jako druga zapora przed podwójnym zaksięgowaniem.
 *  - site_settings: teksty modułu (JSON) — ustawienia globalne każdej witryny.
 *
 * Dotychczasowe „Zebrano dotychczas” (collected_amount, wpisywane ręcznie)
 * przechodzi do external_amount; collected_amount staje się sumą wyliczaną
 * z opłaconych wpłat + external_amount (Campaign::refreshCollected()).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Przeniesienie danych tylko przy pierwszym przebiegu (gdy kolumny dopiero powstają).
        $freshStatus = ! Schema::hasColumn('campaigns', 'status');
        $freshExternal = ! Schema::hasColumn('campaigns', 'external_amount');
        $freshMode = ! Schema::hasColumn('campaigns', 'payment_mode');

        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'status')) {
                $table->string('status', 20)->default('draft')->after('slug');
            }
            if (! Schema::hasColumn('campaigns', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
            }
            if (! Schema::hasColumn('campaigns', 'external_amount')) {
                $table->unsignedInteger('external_amount')->default(0)->after('collected_amount');
            }
            if (! Schema::hasColumn('campaigns', 'amount_options')) {
                $table->string('amount_options', 100)->nullable()->after('external_amount');
            }
            if (! Schema::hasColumn('campaigns', 'allow_custom_amount')) {
                $table->boolean('allow_custom_amount')->default(true)->after('amount_options');
            }
            if (! Schema::hasColumn('campaigns', 'allow_recurring')) {
                $table->boolean('allow_recurring')->default(false)->after('allow_custom_amount');
            }
            if (! Schema::hasColumn('campaigns', 'payment_mode')) {
                $table->string('payment_mode', 20)->default('online')->after('allow_recurring');
            }
            if (! Schema::hasColumn('campaigns', 'wplacam_url')) {
                $table->string('wplacam_url', 500)->nullable()->after('payment_mode');
            }
            if (! Schema::hasColumn('campaigns', 'wplacam_display')) {
                $table->string('wplacam_display', 20)->default('redirect')->after('wplacam_url');
            }
            if (! Schema::hasColumn('campaigns', 'show_recent_donations')) {
                $table->boolean('show_recent_donations')->default(true)->after('wplacam_display');
            }
            if (! Schema::hasColumn('campaigns', 'image_alt')) {
                $table->string('image_alt', 255)->nullable()->after('show_recent_donations');
            }
            if (! Schema::hasColumn('campaigns', 'texts')) {
                $table->json('texts')->nullable()->after('image_alt');
            }
        });

        // Dotychczasowe kampanie: status z flagi publikacji, ręczna kwota → kwota spoza systemu,
        // zewnętrzny link płatności → tryb „external”.
        if ($freshExternal) {
            DB::table('campaigns')->update(['external_amount' => DB::raw('collected_amount')]);
        }
        if ($freshStatus) {
            DB::table('campaigns')->update(['status' => DB::raw("CASE WHEN is_published = 1 THEN 'active' ELSE 'draft' END")]);
        }
        if ($freshMode) {
            // Dotychczasowe zbiórki zachowują się jak dawniej: z linkiem → link zewnętrzny,
            // bez linku → bez wpłat na stronie (formularz P24 włącza się świadomie w panelu).
            DB::table('campaigns')->update(['payment_mode' => DB::raw("CASE WHEN donation_url IS NOT NULL AND donation_url <> '' THEN 'external' ELSE 'none' END")]);
        }

        Schema::table('donations', function (Blueprint $table) {
            if (! Schema::hasColumn('donations', 'campaign_id')) {
                $table->foreignId('campaign_id')->nullable()->after('site_id')->index();
            }
            if (! Schema::hasColumn('donations', 'type')) {
                $table->string('type', 20)->default('one_time')->after('currency');
            }
            if (! Schema::hasColumn('donations', 'source')) {
                $table->string('source', 20)->default('online')->after('type');
            }
            if (! Schema::hasColumn('donations', 'company_name')) {
                $table->string('company_name', 200)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('donations', 'nip')) {
                $table->string('nip', 20)->nullable()->after('company_name');
            }
            if (! Schema::hasColumn('donations', 'dedication')) {
                $table->text('dedication')->nullable()->after('nip');
            }
            if (! Schema::hasColumn('donations', 'dedication_public')) {
                $table->boolean('dedication_public')->default(false)->after('dedication');
            }
            if (! Schema::hasColumn('donations', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('dedication_public');
            }
            if (! Schema::hasColumn('donations', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('admin_note');
            }
            if (! Schema::hasColumn('donations', 'thanked_at')) {
                $table->timestamp('thanked_at')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('donations', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('thanked_at');
            }
        });

        if (! Schema::hasIndex('donations', 'donations_p24_order_id_unique')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->unique('p24_order_id', 'donations_p24_order_id_unique');
            });
        }

        if (! Schema::hasColumn('site_settings', 'fundraising_texts')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->json('fundraising_texts')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropUnique('donations_p24_order_id_unique');
            $table->dropIndex('donations_campaign_id_index');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['campaign_id', 'type', 'source', 'company_name', 'nip', 'dedication',
                'dedication_public', 'admin_note', 'created_by', 'thanked_at', 'refunded_at']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_featured', 'external_amount', 'amount_options', 'allow_custom_amount',
                'allow_recurring', 'payment_mode', 'wplacam_url', 'wplacam_display', 'show_recent_donations',
                'image_alt', 'texts']);
        });

        if (Schema::hasColumn('site_settings', 'fundraising_texts')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('fundraising_texts');
            });
        }
    }
};
