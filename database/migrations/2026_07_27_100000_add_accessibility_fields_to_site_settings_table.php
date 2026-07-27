<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zmienne części deklaracji dostępności (ustawa o dostępności cyfrowej).
     * Stały, prawny szkielet deklaracji jest w widoku; tutaj trzymamy tylko to,
     * co podmiot uzupełnia: status zgodności, daty, dane kontaktowe do zgłoszeń
     * oraz opis dostępności architektonicznej.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('accessibility_entity_name')->nullable()->after('regon_number');
            $table->string('accessibility_status')->default('partially')->after('accessibility_entity_name'); // compliant|partially|none
            $table->text('accessibility_status_note')->nullable()->after('accessibility_status');
            $table->date('accessibility_page_published_at')->nullable()->after('accessibility_status_note');
            $table->date('accessibility_page_updated_at')->nullable()->after('accessibility_page_published_at');
            $table->date('accessibility_declaration_date')->nullable()->after('accessibility_page_updated_at');
            $table->string('accessibility_review_method')->default('self')->after('accessibility_declaration_date'); // self|external
            $table->string('accessibility_contact_name')->nullable()->after('accessibility_review_method');
            $table->string('accessibility_contact_email')->nullable()->after('accessibility_contact_name');
            $table->string('accessibility_contact_phone')->nullable()->after('accessibility_contact_email');
            $table->text('accessibility_architectural')->nullable()->after('accessibility_contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'accessibility_entity_name', 'accessibility_status', 'accessibility_status_note',
                'accessibility_page_published_at', 'accessibility_page_updated_at', 'accessibility_declaration_date',
                'accessibility_review_method', 'accessibility_contact_name', 'accessibility_contact_email',
                'accessibility_contact_phone', 'accessibility_architectural',
            ]);
        });
    }
};
