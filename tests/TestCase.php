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

    /**
     * Wyłącza moduł edytora blokowego — z włączonym aktualności edytuje się tylko w nim,
     * więc testy klasycznego formularza aktualności muszą go wyłączyć.
     */
    protected function useClassicNewsForm(): void
    {
        // Pamięć podręczna ustawień może trzymać rekord z poprzedniego testu (inna baza) — czyścimy przed i po.
        $forget = \Closure::bind(function () {
            static::$cached = null;
        }, null, \App\Models\SiteSetting::class);
        $forget();
        $settings = \App\Models\SiteSetting::current();
        $settings->update(['disabled_modules' => array_values(array_unique(array_merge((array) $settings->disabled_modules, ['block_editor'])))]);
        $forget();
    }
}
