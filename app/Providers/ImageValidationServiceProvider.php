<?php

namespace App\Providers;

use App\Support\ImageAwareValidator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

/**
 * Reguła walidacji `image` przyjmuje też AVIF (Laravel ma tylko jpg, jpeg, png, gif, bmp, webp).
 * Dotyczy wszystkich formularzy z obrazkami w panelu. Miniatury i WebP robi GD (PHP 8.4 na produkcji ma AVIF Support).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ImageValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Validator::resolver(fn ($translator, $data, $rules, $messages, $attributes) => new ImageAwareValidator($translator, $data, $rules, $messages, $attributes));
    }
}
