<?php

namespace Tests\Feature;

use App\Http\Middleware\ConvertHeicUploads;
use App\Support\HeicConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Zdjęcia HEIC/HEIF są zamieniane na WebP przed walidacją (ConvertHeicUploads + HeicConverter). */
class HeicUploadTest extends TestCase
{
    use RefreshDatabase;

    private function fakeConverter(): void
    {
        // Lokalnie zwykle brak Imagick z HEIC — atrapa zwraca prawdziwy plik WebP z GD.
        $this->app->instance(HeicConverter::class, new class extends HeicConverter {
            public function toWebp(string $path): ?string
            {
                $out = tempnam(sys_get_temp_dir(), 'heic_test_').'.webp';
                imagewebp(imagecreatetruecolor(4, 4), $out);

                return $out;
            }
        });
    }

    public function test_middleware_jest_w_grupie_web(): void
    {
        $this->assertContains(ConvertHeicUploads::class, app('router')->getMiddlewareGroups()['web']);
    }

    public function test_heic_trafia_do_kontrolera_jako_webp_takze_w_tablicy(): void
    {
        $this->fakeConverter();
        Route::middleware('web')->post('/_test/heic', function (Request $request) {
            $request->validate(['image' => 'image', 'photos.*' => 'image']);

            return [
                'one' => $request->file('image')->getClientOriginalName(),
                'mime' => $request->file('image')->getMimeType(),
                'many' => collect($request->file('photos'))->map->getClientOriginalName()->all(),
            ];
        })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $heic = fn ($name) => UploadedFile::fake()->createWithContent($name, 'nie-prawdziwy-heic');
        $this->post('/_test/heic', ['image' => $heic('IMG_0001.HEIC'), 'photos' => [$heic('a.heif'), UploadedFile::fake()->image('b.jpg')]])
            ->assertOk()
            ->assertJson(['one' => 'IMG_0001.webp', 'mime' => 'image/webp', 'many' => ['a.webp', 'b.jpg']]);
    }

    public function test_bez_konwertera_plik_zostaje_i_odrzuca_go_walidacja(): void
    {
        $this->app->instance(HeicConverter::class, new class extends HeicConverter {
            public function toWebp(string $path): ?string { return null; }
        });
        Route::middleware('web')->post('/_test/heic2', fn (Request $r) => $r->validate(['image' => 'image']))
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $this->post('/_test/heic2', ['image' => UploadedFile::fake()->createWithContent('a.heic', 'x')])->assertSessionHasErrors('image');
    }
}
