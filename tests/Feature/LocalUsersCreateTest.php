<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalUsersCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_admin_and_editor_with_default_password(): void
    {
        $this->artisan('users:lokalne')->assertSuccessful();

        $admin = User::where('email', 'admin@local')->firstOrFail();
        $editor = User::where('email', 'edytor@local')->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame(User::ROLE_CONTENT_EDITOR, $editor->role);
        foreach ([$admin, $editor] as $user) {
            $this->assertTrue(Hash::check('123456', $user->password));
            $this->assertTrue($user->local_login_allowed);
            $this->assertTrue($user->isDemoAccount());
        }
    }

    public function test_refreshes_existing_account_without_duplicates(): void
    {
        $existing = User::factory()->create([
            'email' => 'edytor@local',
            'name' => 'Moja nazwa',
            'role' => User::ROLE_EDITOR,
            'active_until' => now()->subDay(),
        ]);

        $this->artisan('users:lokalne', ['--haslo' => 'inne-haslo'])->assertSuccessful();

        $this->assertSame(1, User::where('email', 'edytor@local')->count());
        $existing->refresh();
        $this->assertSame('Moja nazwa', $existing->name);
        $this->assertSame(User::ROLE_CONTENT_EDITOR, $existing->role);
        $this->assertNull($existing->active_until);
        $this->assertTrue(Hash::check('inne-haslo', $existing->password));
    }

    public function test_editor_can_log_in_with_password(): void
    {
        $this->artisan('users:lokalne')->assertSuccessful();

        $this->post('/login', ['email' => 'edytor@local', 'password' => '123456']);

        $this->assertAuthenticatedAs(User::where('email', 'edytor@local')->first());
    }
}
