<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel nie ma publicznej rejestracji — konta zakłada administrator
 * (Użytkownicy) albo powstają przez logowanie Microsoft 365 / Google.
 * Test pilnuje, żeby trasa rejestracji nie wróciła przypadkiem (np. z
 * domyślnego scaffoldingu Breeze).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();

        // POST trafia w trasę „{page}" (tylko GET), więc 405; ważne, że nie powstaje konto.
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $this->assertContains($response->getStatusCode(), [404, 405]);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertGuest();
    }
}
