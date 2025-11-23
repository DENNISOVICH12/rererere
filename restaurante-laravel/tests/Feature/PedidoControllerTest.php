<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Restaurant;
use App\Models\MenuItem;

class PedidoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_listar_pedidos_correctamente()
    {
        $restaurant = Restaurant::factory()->create();
        $cliente = Cliente::factory()->create();
        Pedido::factory()->count(3)->create([
            'restaurant_id' => $restaurant->id,
            'cliente_id' => $cliente->id
        ]);

        $response = $this->getJson('/api/pedidos');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'meta' => ['total'],
                     'data'
                 ]);
    }

    /** @test */
    public function puede_crear_un_pedido_correctamente()
    {
        $restaurant = Restaurant::factory()->create();
        $cliente = Cliente::factory()->create();
        $menuItem = MenuItem::factory()->create(['precio' => 10]);

        $payload = [
            'cliente_id' => $cliente->id,
            'detalle' => [
                ['menu_item_id' => $menuItem->id, 'cantidad' => 2, 'precio' => 10],
            ]
        ];

        $response = $this->postJson('/api/pedidos', $payload);
        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'data' => ['id', 'detalle']]);

        $this->assertDatabaseHas('pedidos', ['cliente_id' => $cliente->id]);
    }

    /** @test */
    public function no_permite_crear_pedido_con_cliente_inexistente()
    {
        $menuItem = MenuItem::factory()->create();

        $payload = [
            'cliente_id' => 999,
            'detalle' => [
                ['menu_item_id' => $menuItem->id, 'cantidad' => 1, 'precio' => 20]
            ]
        ];

        $response = $this->postJson('/api/pedidos', $payload);
        $response->assertStatus(422);
    }

    /** @test */

    public function puede_actualizar_un_pedido_existente()
{
    $restaurant = Restaurant::factory()->create();
    $cliente = Cliente::factory()->create();
    $menuItem = MenuItem::factory()->create();

    $pedido = Pedido::factory()->create([
        'restaurant_id' => $restaurant->id,
        'cliente_id' => $cliente->id,
        'total' => 100,
    ]);

    $payload = [
    'total' => 150,
    'restaurant_id' => $pedido->restaurant_id,
    ];

    $response = $this->putJson("/api/pedidos/{$pedido->id}", $payload);

    $response->assertStatus(200)
             ->assertJsonFragment(['total' => 150]);

    $this->assertDatabaseHas('pedidos', [
        'id' => $pedido->id,
        'total' => 150
    ]);
}


    /** @test */
    public function devuelve_error_si_se_actualiza_pedido_inexistente()
    {
        $response = $this->putJson('/api/pedidos/999', ['total' => 100]);
        $response->assertStatus(404);
    }

    /** @test */
    public function puede_eliminar_pedido_existente()
    {
        $pedido = Pedido::factory()->create(['restaurant_id' => 1]);


        $response = $this->deleteJson("/api/pedidos/{$pedido->id}");
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Pedido eliminado correctamente']);

        $this->assertDatabaseMissing('pedidos', ['id' => $pedido->id]);
    }

    /** @test */
    public function devuelve_error_si_el_pedido_no_existe_al_eliminar()
    {
        $response = $this->deleteJson('/api/pedidos/999');
        $response->assertStatus(404);
    }
}
