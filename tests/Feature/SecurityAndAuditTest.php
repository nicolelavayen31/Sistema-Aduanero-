<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Declaracion;
use App\Models\Pago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_include_security_headers(): void
    {
        $response = $this->get('/up');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-XSS-Protection', '1; mode=block')
            ->assertHeader('Referrer-Policy', 'no-referrer-when-downgrade')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_health_check_endpoint_returns_system_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'HEALTHY')
            ->assertJsonPath('data.services.database', 'connected')
            ->assertJsonPath('data.services.storage', 'writable');
    }

    public function test_audit_fields_and_soft_deletes_on_cliente_model(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $cliente = Cliente::create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '0923456784',
            'email' => 'juan@example.test',
            'activo' => true,
        ]);

        $this->assertEquals($user->id, $cliente->created_by);
        $this->assertEquals($user->id, $cliente->updated_by);

        // Test Soft Delete
        $cliente->delete();

        $this->assertSoftDeleted('clientes', ['id' => $cliente->id]);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
        $this->assertEquals(0, Cliente::count());
        $this->assertEquals(1, Cliente::withTrashed()->count());
    }

    public function test_audit_fields_and_soft_deletes_on_declaraciones_and_pagos(): void
    {
        $user = User::factory()->create(['role' => 'operador']);
        $this->actingAs($user);

        $cliente = Cliente::create([
            'nombres' => 'María',
            'apellidos' => 'Gómez',
            'cedula' => '0923456784',
            'email' => 'maria@example.test',
            'activo' => true,
        ]);

        $declaracion = Declaracion::create([
            'cliente_id' => $cliente->id,
            'numero_declaracion' => 'DA-999',
            'tipo' => 'Importacion',
            'valor_mercancia' => 500,
            'estado' => 'pendiente',
            'fecha_declaracion' => now()->toDateString(),
        ]);

        $pago = Pago::create([
            'declaracion_id' => $declaracion->id,
            'monto' => 500,
            'metodo_pago' => 'transferencia',
            'estado' => 'pagado',
            'fecha_pago' => now(),
        ]);

        $this->assertEquals($user->id, $declaracion->created_by);
        $this->assertEquals($user->id, $pago->created_by);

        $declaracion->delete();
        $this->assertSoftDeleted('declaraciones', ['id' => $declaracion->id]);
    }

    public function test_user_role_methods_and_helpers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $operador = User::factory()->create(['role' => 'operador']);
        $auditor = User::factory()->create(['role' => 'auditor']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isOperador());
        $this->assertTrue($operador->isOperador());
        $this->assertTrue($auditor->isAuditor());
        $this->assertTrue($admin->hasRole('admin', 'operador'));
    }

    public function test_unhandled_api_validation_errors_return_standardized_json(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/clientes', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422)
            ->assertJsonStructure(['success', 'code', 'message', 'errors']);
    }
}
