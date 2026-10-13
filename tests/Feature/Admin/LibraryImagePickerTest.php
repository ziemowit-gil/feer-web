<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\MediaLibrary;
use App\Models\Partner;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryImagePickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_logo_can_be_chosen_from_library(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        Storage::fake('public');
        /** @var Media $library */
        $library = MediaLibrary::instance()->addMedia(UploadedFile::fake()->image('logo-co.png', 300, 120))->toMediaCollection('files');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.partnerzy.create'))->assertOk()->assertSee('Wybierz z biblioteki');

        $this->actingAs($admin)->post(route('admin.partnerzy.store'), ['name' => 'Centrum Obywatelskie', 'logo_library_id' => $library->id])
            ->assertSessionHasNoErrors()->assertRedirect();

        $partner = Partner::where('name', 'Centrum Obywatelskie')->firstOrFail();
        $this->assertNotNull($partner->getFirstMedia('logo'));
        $this->assertNotSame($library->id, $partner->getFirstMedia('logo')->id, 'Do partnera trafia kopia, oryginał zostaje w bibliotece.');
        $this->assertNotNull(Media::find($library->id));

        $this->actingAs($admin)->post(route('admin.partnerzy.store'), ['name' => 'Bez logo'])->assertSessionHasErrors('logo');
    }
}
