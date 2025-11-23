<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Cliente;

class ClienteControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_crear_un_cliente_correctamente()
    {
        $payload = [
            'nombre_cliente' => 'Juan Pérez',
            'telefono' => '3001234567',
            'direccion' => 'Calle 10 #5-20',
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => ['id', 'nombre_cliente', 'telefono', 'direccion']
                 ]);

        $this->assertDatabaseHas('clientes', ['nombre_cliente' => 'Juan Pérez']);
    }

    /** @test */
    public function valida_campos_obligatorios_al_crear_cliente()
    {
        $payload = [
            'nombre_cliente' => '',
            'telefono' => '',
            'direccion' => ''
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nombre_cliente']);
    }

    /** @test */
    public function evita_duplicar_cliente_con_nombre_existente()
    {
        Cliente::factory()->create(['nombre_cliente' => 'Juan Pérez']);

        $payload = [
            'nombre_cliente' => 'Juan Pérez',
            'telefono' => '3105559999',
            'direccion' => 'Carrera 15 #45-10'
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nombre_cliente']);
    }
}
