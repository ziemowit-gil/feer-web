<?php

namespace Tests\Feature;

use App\Support\ImageAwareValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/** Reguła `image` przyjmuje AVIF w całym panelu (ImageValidationServiceProvider). */
class AvifUploadTest extends TestCase
{
    private function avif(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'avif_').'.avif';
        $img = imagecreatetruecolor(8, 8);
        imageavif($img, $path);

        return new UploadedFile($path, 'zdjecie.avif', 'image/avif', null, true);
    }

    public function test_regula_image_przyjmuje_avif(): void
    {
        if (! function_exists('imageavif')) {
            $this->markTestSkipped('GD bez AVIF.');
        }
        $v = Validator::make(['image' => $this->avif()], ['image' => ['required', 'image', 'max:4096']]);

        $this->assertInstanceOf(ImageAwareValidator::class, $v);
        $this->assertTrue($v->passes(), implode(' ', $v->errors()->all()));
    }

    public function test_regula_image_dalej_odrzuca_inne_pliki_i_svg_bez_zgody(): void
    {
        $txt = UploadedFile::fake()->createWithContent('a.txt', 'tekst');
        $this->assertTrue(Validator::make(['f' => $txt], ['f' => 'image'])->fails());

        $svg = UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $this->assertTrue(Validator::make(['f' => $svg], ['f' => 'image'])->fails());
        $this->assertTrue(Validator::make(['f' => $svg], ['f' => 'image:allow_svg'])->passes());
    }
}
