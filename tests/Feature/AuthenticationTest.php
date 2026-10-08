<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_api_routes(): void
    {
        $this->getJson('/api/clientes')->assertUnauthorized();
    }

    public function test_user_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create([
            'email' => 'aduanas@example.test',
            'password' => bcrypt('correct-horse-battery'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->getJson('/api/clientes')->assertOk();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invalid_credentials_do_not_authenticate_user(): void
    {
        User::factory()->create([
            'email' => 'aduanas@example.test',
            'password' => bcrypt('correct-horse-battery'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'aduanas@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
