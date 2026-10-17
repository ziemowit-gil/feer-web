<?php

namespace Tests\Feature;

use App\Jobs\ShrinkLargeImages;
use App\Models\User;
use App\Services\Media\MediaOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;
use Tests\TestCase;

/** Multimedia: za duże obrazy zmniejszane w tle po otwarciu biblioteki — ten sam adres, poprzednia wersja zostaje. */
class MediaShrinkTest extends TestCase
{
    use RefreshDatabase;

    private function bigJpeg(): Media
    {
        $path = sys_get_temp_dir().'/duzy-'.uniqid().'.jpg';
        $img = imagecreatetruecolor(3200, 1800);
        // Szum, żeby JPG był ciężki (jednolity kolor kompresuje się do kilku KB).
        for ($i = 0; $i < 60000; $i++) {
            imagesetpixel($img, random_int(0, 3199), random_int(0, 1799), random_int(0, 0xFFFFFF));
        }
        imagejpeg($img, $path, 100);
        imagedestroy($img);

        $owner = \App\Models\SiteSetting::current();

        return $owner->addMedia($path)->usingFileName('zdjecie-duze.jpg')->toMediaCollection('images_test');
    }

    public function test_otwarcie_multimediow_zleca_zmniejszanie_raz_na_10_minut(): void
    {
        Bus::fake();
        Cache::flush();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.multimedia.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.multimedia.index'))->assertOk();

        Bus::assertDispatchedTimes(ShrinkLargeImages::class, 1);
    }

    public function test_duzy_jpg_zmniejszony_do_2000_px_pod_tym_samym_adresem(): void
    {
        Storage::fake('public');
        $media = $this->bigJpeg();
        $url = $media->getUrl();
        $before = (int) $media->size;

        (new ShrinkLargeImages)->handle(app(MediaOptimizer::class));

        $media->refresh();
        [$w, $h] = getimagesize($media->getPath());
        $this->assertSame(2000, max($w, $h));
        $this->assertLessThan($before, (int) $media->size);
        $this->assertSame($url, $media->getUrl());
        $this->assertSame('zdjecie-duze.jpg', $media->file_name);
        $this->assertSame(1, \App\Models\MediaVersion::where('media_id', $media->id)->count(), 'Poprzednia wersja zostaje w historii.');
        $this->assertFalse(app(MediaOptimizer::class)->needsShrinking($media));
    }
}
