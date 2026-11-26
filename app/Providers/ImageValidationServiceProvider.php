<?php

namespace App\Providers;

use App\Http\Middleware\ConvertHeicUploads;
use App\Support\ImageAwareValidator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

/**
 * Obrazy w formularzach: reguła walidacji `image` przyjmuje też AVIF (Laravel ma tylko jpg, jpeg, png, gif,
 * bmp, webp; miniatury i WebP robi GD — PHP 8.4 na produkcji ma AVIF Support), a zdjęcia HEIC/HEIF
 * są zamieniane na WebP przed walidacją (middleware ConvertHeicUploads w grupie web, Imagick).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ImageValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['router']->pushMiddlewareToGroup('web', ConvertHeicUploads::class);
        Validator::resolver(fn ($translator, $data, $rules, $messages, $attributes) => new ImageAwareValidator($translator, $data, $rules, $messages, $attributes));
    }
}
