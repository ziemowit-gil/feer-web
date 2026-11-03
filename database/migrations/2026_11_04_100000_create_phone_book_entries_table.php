<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Moduł „Książka telefoniczna”: osoby (kto, numer komórkowy, w jakiej sprawie) i ustawienia strony publicznej. */
    public function up(): void
    {
        if (! Schema::hasTable('phone_book_entries')) {
            Schema::create('phone_book_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('site_id')->nullable()->index();
                $table->string('group', 120)->nullable();   // dział (tabela na stronie)
                $table->string('name', 120);
                $table->string('role', 160)->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('email', 190)->nullable();
                $table->text('topic')->nullable();          // w jakiej sprawie się kontaktować
                $table->string('hours', 160)->nullable();   // kiedy dzwonić
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('site_settings', 'phonebook_title')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->string('phonebook_title', 160)->nullable();
                $table->text('phonebook_lead')->nullable();
                $table->string('phonebook_layout', 10)->default('table');
                $table->string('phonebook_sort', 10)->default('manual');
                $table->boolean('phonebook_hide_numbers')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_book_entries');

        if (Schema::hasColumn('site_settings', 'phonebook_title')) {
            Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['phonebook_title', 'phonebook_lead', 'phonebook_layout', 'phonebook_sort', 'phonebook_hide_numbers']));
        }
    }
};
