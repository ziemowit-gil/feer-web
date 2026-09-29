<?php

namespace Tests;

use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Moduły (modules/*) są bootowane w ModuleServiceProvider na podstawie
     * tabeli `plugins`. W testach aplikacja startuje przed migracją bazy
     * in-memory, więc przy starcie żaden moduł nie jest aktywny i ich trasy
     * (/wydarzenia, /faq, /wolontariat, /wsparcie…) nie istnieją. Po migracji
     * (RefreshDatabase) ładujemy statusy ponownie — wbudowane moduły są wtedy
     * auto-aktywowane — i dopiero bootujemy ich providery.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('plugins')) {
            $modules = $this->app->make(ModuleManager::class);
            $modules->loadStatuses();
            $modules->bootActiveProviders();
        }
    }
}
