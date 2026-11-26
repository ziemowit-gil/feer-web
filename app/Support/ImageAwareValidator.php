<?php

namespace App\Support;

use Illuminate\Validation\Validator;

/**
 * Walidator z regułą `image` rozszerzoną o AVIF (rejestracja: ImageValidationServiceProvider).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ImageAwareValidator extends Validator
{
    public const IMAGE_MIMES = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif'];

    public function validateImage($attribute, $value, $parameters = [])
    {
        $mimes = self::IMAGE_MIMES;
        if (is_array($parameters) && in_array('allow_svg', $parameters)) {
            $mimes[] = 'svg';
        }

        return $this->validateMimes($attribute, $value, $mimes);
    }
}
