<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\MenuItem;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoServicioGrupoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_actualiza_solo_items_del_grupo_solicitado(): void
    {
        $restaurant = Restaurant::factory()->create();
        $cliente = Cliente::factory()->create(['restaurant_id' => $restaurant->id]);
        $pedido = Pedido::factory()->create([
            'restaurant_id' => $restaurant->id,
            'cliente_id' => $cliente->id,
            'estado' => 'pendiente',
        ]);

        $plato = MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'categoria' => 'plato']);
        $bebida = MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'categoria' => 'bebida']);

        $detallePlato = PedidoDetalle::create([
            'restaurant_id' => $restaurant->id,
            'pedido_id' => $pedido->id,
            'menu_item_id' => $plato->id,
            'cantidad' => 1,
            'precio_unitario' => 10,
            'importe' => 10,
            'grupo_servicio' => 'plato',
            'estado_servicio' => 'pendiente',
        ]);

        $detalleBebida = PedidoDetalle::create([
            'restaurant_id' => $restaurant->id,
            'pedido_id' => $pedido->id,
            'menu_item_id' => $bebida->id,
            'cantidad' => 1,
            'precio_unitario' => 5,
            'importe' => 5,
            'grupo_servicio' => 'bebida',
            'estado_servicio' => 'pendiente',
        ]);

        $response = $this->putJson("/api/pedidos/{$pedido->id}/servicio/plato");

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('grupo', 'plato')
            ->assertJsonCount(1, 'updated_items')
            ->assertJsonPath('updated_items.0.id', $detallePlato->id)
            ->assertJsonPath('updated_items.0.estado_servicio', 'preparando');

        $this->assertDatabaseHas('pedido_detalles', [
            'id' => $detallePlato->id,
            'estado_servicio' => 'preparando',
        ]);

        $this->assertDatabaseHas('pedido_detalles', [
            'id' => $detalleBebida->id,
            'estado_servicio' => 'pendiente',
        ]);
    }

    public function test_avanza_de_preparando_a_listo(): void
    {
        $restaurant = Restaurant::factory()->create();
        $cliente = Cliente::factory()->create(['restaurant_id' => $restaurant->id]);
        $pedido = Pedido::factory()->create([
            'restaurant_id' => $restaurant->id,
            'cliente_id' => $cliente->id,
        ]);
        $menuItem = MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'categoria' => 'plato']);

        $detalle = PedidoDetalle::create([
            'restaurant_id' => $restaurant->id,
            'pedido_id' => $pedido->id,
            'menu_item_id' => $menuItem->id,
            'cantidad' => 1,
            'precio_unitario' => 12,
            'importe' => 12,
            'grupo_servicio' => 'plato',
            'estado_servicio' => 'preparando',
        ]);

        $response = $this->putJson("/api/pedidos/{$pedido->id}/servicio/plato");

        $response->assertOk()
            ->assertJsonPath('updated_items.0.estado_servicio', 'listo');

        $this->assertDatabaseHas('pedido_detalles', [
            'id' => $detalle->id,
            'estado_servicio' => 'listo',
        ]);
    }

    public function test_valida_grupo_invalido(): void
    {
        $response = $this->putJson('/api/pedidos/1/servicio/postre');

        $response->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_devuelve_404_si_pedido_no_existe(): void
    {
        $response = $this->putJson('/api/pedidos/999/servicio/plato');

        $response->assertStatus(404)
            ->assertJsonPath('ok', false);
    }
}
