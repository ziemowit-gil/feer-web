<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
    App\Providers\ImageValidationServiceProvider::class,
    // Pakiet nie rejestruje się sam (brak auto-discovery): udostępnia config('cleantalk'), widok skryptu i klasy lib.
    CleanTalkLaravel\CleantalkServiceProvider::class,
];
