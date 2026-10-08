<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use App\Notifications\DeclaracionRegistradaNotification;
use App\Notifications\UserWelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'operador']);

        $this->actingAs($admin);

        $response = $this->getJson('/api/usuarios');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_new_user_with_role(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/usuarios', [
            'name' => 'Carlos Mendoza',
            'email' => 'carlos@example.test',
            'password' => 'SecurePass123',
            'role' => 'operador',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Carlos Mendoza')
            ->assertJsonPath('data.role', 'operador');

        $this->assertDatabaseHas('users', [
            'email' => 'carlos@example.test',
            'role' => 'operador',
        ]);

        Notification::assertSentTo(
            User::where('email', 'carlos@example.test')->first(),
            UserWelcomeNotification::class
        );
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $operador = User::factory()->create(['role' => 'operador']);
        $this->actingAs($operador);

        $this->getJson('/api/usuarios')
            ->assertStatus(403);

        $this->postJson('/api/usuarios', [
            'name' => 'Fake User',
            'email' => 'fake@example.test',
            'password' => 'SecurePass123',
            'role' => 'operador',
        ])->assertStatus(403);
    }

    public function test_registering_declaration_creates_in_app_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $client = Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Notificacion',
            'cedula' => '0923456784',
            'email' => 'notif@example.test',
            'activo' => true,
        ]);

        $response = $this->postJson('/api/declaraciones/con-pago', [
            'cliente_id' => $client->id,
            'numero_declaracion' => 'DA-888',
            'tipo' => 'Importacion',
            'valor_mercancia' => 1200,
            'monto' => 1200,
            'metodo_pago' => 'transferencia',
        ]);

        $response->assertCreated();

        $notificationsResponse = $this->getJson('/api/notificaciones');

        $notificationsResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonPath('data.notifications.0.data.numero_declaracion', 'DA-888');
    }

    public function test_artisan_command_notifica_declaraciones_pendientes(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $client = Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Pendiente',
            'cedula' => '0923456784',
            'email' => 'pend@example.test',
            'activo' => true,
        ]);

        \App\Models\Declaracion::create([
            'cliente_id' => $client->id,
            'numero_declaracion' => 'DA-889',
            'tipo' => 'Importacion',
            'valor_mercancia' => 1500,
            'estado' => 'pendiente',
            'fecha_declaracion' => now()->toDateString(),
        ]);

        $this->artisan('aduanas:notificar-pendientes')
            ->assertExitCode(0);
    }
}
