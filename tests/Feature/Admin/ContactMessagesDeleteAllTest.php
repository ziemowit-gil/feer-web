<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessagesDeleteAllTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function seedMessages(): void
    {
        ContactMessage::create(['name' => 'A', 'email' => 'a@example.com', 'message' => 'Nowa']);
        $read = ContactMessage::create(['name' => 'B', 'email' => 'b@example.com', 'message' => 'Stara']);
        $read->markAsRead();
    }

    public function test_usuwa_wszystkie_wiadomosci(): void
    {
        $this->seedMessages();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->delete(route('admin.wiadomosci-kontaktowe.destroy-all'), ['filtr' => 'wszystkie'])
            ->assertRedirect(route('admin.wiadomosci-kontaktowe.index', ['filtr' => 'wszystkie']));

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_usuwa_tylko_wiadomosci_z_biezacego_filtra(): void
    {
        $this->seedMessages();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->delete(route('admin.wiadomosci-kontaktowe.destroy-all'), ['filtr' => 'nieprzeczytane']);

        $this->assertSame(1, ContactMessage::count());
        $this->assertSame('B', ContactMessage::first()->name);
    }

    public function test_lista_pokazuje_przycisk_usuwania_wszystkich(): void
    {
        $this->seedMessages();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.wiadomosci-kontaktowe.index', ['filtr' => 'nieprzeczytane']))
            ->assertOk()->assertSee('Usuń wszystkie (1)');
    }

    public function test_otwarcie_wiadomosci_oznacza_ja_jako_przeczytana(): void
    {
        $msg = ContactMessage::create(['name' => 'A', 'email' => 'a@example.com', 'message' => 'Treść']);
        $this->assertSame(1, ContactMessage::unreadCount());

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.wiadomosci-kontaktowe.show', $msg))->assertOk();

        $this->assertSame(0, ContactMessage::unreadCount());
        $this->assertNotNull($msg->fresh()->read_at);
    }
}
