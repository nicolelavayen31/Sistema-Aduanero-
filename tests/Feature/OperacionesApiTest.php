<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Declaracion;
use App\Models\Pago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperacionesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_catalogos_api_returns_the_configured_declaration_types_and_payment_methods(): void
    {
        $response = $this->getJson('/api/catalogos');

        $response->assertOk()
            ->assertJsonPath('tipos_declaracion.0.value', 'Importacion')
            ->assertJsonPath('metodos_pago.0.value', 'transferencia')
            ->assertJsonPath('estados_pago.0.value', 'pendiente')
            ->assertJsonPath('estados_pago.2.value', 'rechazado');
    }

    public function test_client_creation_accepts_valid_spanish_data_and_normalizes_name_spacing(): void
    {
        $response = $this->postJson('/api/clientes', $this->clientPayload([
            'nombres' => '  Ana   María ',
            'apellidos' => 'García López',
            'direccion' => 'Av. 9 de Octubre 123 y Boyacá',
        ]));

        $response->assertCreated()
            ->assertJsonPath('nombres', 'Ana María')
            ->assertJsonPath('apellidos', 'García López')
            ->assertJsonPath('cedula', '0923456784')
            ->assertJsonPath('direccion', 'Av. 9 de Octubre 123 y Boyacá');
    }

    public function test_client_creation_rejects_names_with_numbers_symbols_or_emojis(): void
    {
        $this->postJson('/api/clientes', $this->clientPayload([
            'nombres' => 'Ana123',
            'apellidos' => 'García😀',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['nombres', 'apellidos']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'nombres' => 'Ana@',
            'apellidos' => 'García López',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['nombres']);
    }

    public function test_client_creation_checks_ecuadorian_cedula_format_checksum_and_uniqueness(): void
    {
        foreach (['093456789', '09345678901', '09345678AB', '09-34567890', '093456789😀', '0934567890', ' 0923456784'] as $cedula) {
            $this->postJson('/api/clientes', $this->clientPayload(['cedula' => $cedula]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['cedula']);
        }

        $this->postJson('/api/clientes', $this->clientPayload())
            ->assertCreated();

        $this->postJson('/api/clientes', $this->clientPayload([
            'email' => 'otra@example.test',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['cedula']);
    }

    public function test_client_creation_validates_optional_phone_email_and_address_characters(): void
    {
        $this->postJson('/api/clientes', $this->clientPayload([
            'telefono' => '099ABC4567',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['telefono']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'telefono' => '099123456😀',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['telefono']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'email' => 'ana gmail@gmail.com',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'email' => 'test😀@gmail.com',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'email' => 'ana@gmail',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'telefono' => '099123456',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['telefono']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'telefono' => '09912345678',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['telefono']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'direccion' => 'Av. 9 de Octubre 😀',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['direccion']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'nombres' => "Ana' OR 1=1 --",
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['nombres']);

        $this->postJson('/api/clientes', $this->clientPayload([
            'telefono' => null,
            'email' => 'ana.martinez@gmail.com',
        ]))->assertCreated();
    }

    public function test_client_creation_rejects_duplicate_email(): void
    {
        $this->postJson('/api/clientes', $this->clientPayload())->assertCreated();

        $this->postJson('/api/clientes', $this->clientPayload([
            'cedula' => '0923456792',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unvalidated_client_properties_are_not_mass_assigned(): void
    {
        $response = $this->postJson('/api/clientes', $this->clientPayload([
            'id' => 900,
            'created_at' => '2000-01-01',
        ]));

        $response->assertCreated()
            ->assertJsonMissing(['created_at' => '2000-01-01'])
            ->assertJsonPath('id', 1);
    }

    public function test_creating_a_declaration_and_payment_validates_and_returns_the_related_records(): void
    {
        $client = $this->createClient();

        $response = $this->postJson('/api/declaraciones/con-pago', [
            'cliente_id' => $client->id,
            'numero_declaracion' => 'DA-001',
            'tipo' => 'Importacion',
            'valor_mercancia' => 2000.00,
            'monto' => 2000.00,
            'metodo_pago' => 'transferencia',
        ]);

        $response->assertCreated()
            ->assertJsonPath('declaracion.estado', 'pagado')
            ->assertJsonPath('pago.estado', 'pagado')
            ->assertJsonPath('pago.metodo_pago', 'transferencia');

        $this->assertDatabaseHas('declaraciones', [
            'numero_declaracion' => 'DA-001',
            'estado' => 'pagado',
        ]);
        $this->assertDatabaseHas('pagos', [
            'metodo_pago' => 'transferencia',
            'estado' => 'pagado',
        ]);
    }

    public function test_declaration_and_payment_creation_rejects_inactive_clients_and_unknown_catalog_values(): void
    {
        $client = $this->createClient(false);

        $this->postJson('/api/declaraciones/con-pago', [
            'cliente_id' => $client->id,
            'numero_declaracion' => 'DA-002',
            'tipo' => 'TipoInventado',
            'valor_mercancia' => 100,
            'monto' => 100,
            'metodo_pago' => 'metodo-inventado',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['cliente_id', 'tipo', 'metodo_pago']);

        $this->assertDatabaseMissing('declaraciones', ['numero_declaracion' => 'DA-002']);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_declaration_and_payment_creation_rejects_non_positive_amounts_and_duplicate_numbers(): void
    {
        $client = $this->createClient();
        $this->createDeclaration($client, 'DA-003', 'pendiente');

        $this->postJson('/api/declaraciones/con-pago', [
            'cliente_id' => $client->id,
            'numero_declaracion' => 'DA-003',
            'tipo' => 'Importacion',
            'valor_mercancia' => 0,
            'monto' => -10,
            'metodo_pago' => 'transferencia',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['numero_declaracion', 'valor_mercancia', 'monto']);

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_declaration_and_payment_creation_checks_number_format_decimals_emojis_and_status(): void
    {
        $client = $this->createClient();

        foreach ([
            [['numero_declaracion' => 'DA-001 '], 'numero_declaracion'],
            [['numero_declaracion' => 'DA-00😀'], 'numero_declaracion'],
            [['valor_mercancia' => '1500.999'], 'valor_mercancia'],
            [['valor_mercancia' => '1500😀'], 'valor_mercancia'],
            [['monto' => '-500'], 'monto'],
            [['monto' => '100.999'], 'monto'],
            [['monto' => '100😀'], 'monto'],
            [['metodo_pago' => 'cheque'], 'metodo_pago'],
            [['estado' => 'completado'], 'estado'],
        ] as $invalidFields) {
            $payload = array_merge([
                'cliente_id' => $client->id,
                'numero_declaracion' => 'DA-123',
                'tipo' => 'Importacion',
                'valor_mercancia' => '1500.50',
                'monto' => '1500.50',
                'metodo_pago' => 'transferencia',
                'estado' => 'pagado',
            ], $invalidFields[0]);

            $this->postJson('/api/declaraciones/con-pago', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors([$invalidFields[1]]);
        }

        $this->assertDatabaseCount('declaraciones', 0);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_payment_status_is_limited_to_configured_values_and_partial_payments_do_not_complete_declaration(): void
    {
        $client = $this->createClient();

        $response = $this->postJson('/api/declaraciones/con-pago', [
            'cliente_id' => $client->id,
            'numero_declaracion' => 'DA-124',
            'tipo' => 'Importacion',
            'valor_mercancia' => '2000.00',
            'monto' => '1000.00',
            'metodo_pago' => 'tarjeta',
            'estado' => 'pagado',
        ]);

        $response->assertCreated()
            ->assertJsonPath('declaracion.estado', 'pendiente')
            ->assertJsonPath('pago.estado', 'pagado');

        $response = $this->postJson('/api/declaraciones/con-pago', [
            'cliente_id' => $client->id,
            'numero_declaracion' => 'DA-125',
            'tipo' => 'Exportacion',
            'valor_mercancia' => '100.00',
            'monto' => '100.00',
            'metodo_pago' => 'efectivo',
            'estado' => 'rechazado',
        ]);

        $response->assertCreated()
            ->assertJsonPath('declaracion.estado', 'pendiente')
            ->assertJsonPath('pago.estado', 'rechazado')
            ->assertJsonPath('pago.fecha_pago', null);

        $response = $this->postJson('/api/declaraciones/con-pago', [
            'cliente_id' => $client->id,
            'numero_declaracion' => 'DA-126',
            'tipo' => 'Exportacion',
            'valor_mercancia' => '100.00',
            'monto' => '100.00',
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
        ]);

        $response->assertCreated()
            ->assertJsonPath('declaracion.estado', 'pendiente')
            ->assertJsonPath('pago.estado', 'pendiente')
            ->assertJsonPath('pago.fecha_pago', null);
    }

    public function test_client_and_declaration_lists_support_optional_search_and_pagination(): void
    {
        $client = $this->createClient();
        Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Secundario',
            'cedula' => '0923456792',
            'email' => 'secundario@example.test',
            'activo' => true,
        ]);
        Cliente::create([
            'nombres' => 'Otra',
            'apellidos' => 'Persona',
            'cedula' => '0923456800',
            'email' => 'otra@example.test',
            'activo' => true,
        ]);

        $this->getJson('/api/clientes')
            ->assertOk()
            ->assertJsonCount(3);

        $this->getJson('/api/clientes?search=Cliente&page=1&per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 1)
            ->assertJsonCount(1, 'data');

        $first = $this->createDeclaration($client, 'DA-201', 'pendiente');
        $second = $this->createDeclaration($client, 'DA-202', 'pendiente');
        $this->createDeclaration($client, 'DA-203', 'pendiente');

        $this->getJson('/api/declaraciones?search=DA-202&page=1&per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.0.numero_declaracion', 'DA-202')
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/declaraciones?page=1&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $first->id + 2);

        $this->getJson('/api/clientes?per_page=101')->assertUnprocessable();
        $this->getJson('/api/declaraciones?per_page=101')->assertUnprocessable();
    }

    public function test_dashboard_and_declaration_list_report_real_payment_state_and_details(): void
    {
        $client = $this->createClient();
        $pendingDeclaration = $this->createDeclaration($client, 'DA-004', 'pendiente');
        $legacyDeclaration = $this->createDeclaration($client, 'DA-005', 'pendiente');
        $this->createPayment($legacyDeclaration);
        $partialDeclaration = $this->createDeclaration($client, 'DA-006', 'pendiente', 200);
        $this->createPayment($partialDeclaration);

        $this->getJson('/api/dashboard/estadisticas')
            ->assertOk()
            ->assertJsonPath('clientes_total', 1)
            ->assertJsonPath('declaraciones_total', 3)
            ->assertJsonPath('declaraciones_pendientes', 2)
            ->assertJsonPath('declaraciones_pagadas', 1)
            ->assertJsonPath('pagos_total', 200)
            ->assertJsonPath('operaciones_procesadas', 1)
            ->assertJsonPath('distribucion_tipo.Importacion', 3)
            ->assertJsonPath('actividad_mensual.5.importaciones', 3)
            ->assertJsonPath('declaraciones_recientes.0.estado', 'pendiente')
            ->assertJsonPath('declaraciones_recientes.1.estado', 'pagado');

        $this->getJson('/api/declaraciones')
            ->assertOk()
            ->assertJsonPath('0.estado', 'pendiente')
            ->assertJsonPath('1.estado', 'pagado')
            ->assertJsonPath('2.estado', 'pendiente')
            ->assertJsonPath('1.pagos.0.metodo_pago', 'transferencia');

        $this->assertSame('pendiente', $pendingDeclaration->fresh()->estado);
    }

    private function createClient(bool $active = true): Cliente
    {
        return Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Prueba',
            'cedula' => $active ? '0923456784' : '0923456792',
            'email' => $active ? 'cliente@example.test' : 'inactivo@example.test',
            'activo' => $active,
        ]);
    }

    private function clientPayload(array $overrides = []): array
    {
        return array_merge([
            'nombres' => 'Ana',
            'apellidos' => 'Martínez',
            'cedula' => '0923456784',
            'email' => 'ana@example.test',
            'telefono' => '0991234567',
            'direccion' => 'Av. 9 de Octubre 123',
            'activo' => true,
        ], $overrides);
    }

    private function createDeclaration(Cliente $client, string $number, string $status, float $amount = 100): Declaracion
    {
        return Declaracion::create([
            'cliente_id' => $client->id,
            'numero_declaracion' => $number,
            'tipo' => 'Importacion',
            'valor_mercancia' => $amount,
            'estado' => $status,
            'fecha_declaracion' => now()->toDateString(),
        ]);
    }

    private function createPayment(Declaracion $declaration): Pago
    {
        return Pago::create([
            'declaracion_id' => $declaration->id,
            'monto' => 100,
            'metodo_pago' => 'transferencia',
            'estado' => 'pagado',
            'fecha_pago' => now(),
        ]);
    }
}
