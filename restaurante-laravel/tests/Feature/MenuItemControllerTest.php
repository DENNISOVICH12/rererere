<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MenuItem;

class MenuItemControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_crear_un_item_correctamente()
    {
        $payload = [
            'nombre' => 'Limonada',
            'categoria' => 'bebida',
            'precio' => 5.50,
            'descripcion' => 'Natural y fría',
            'restaurant_id' => 1
        ];

        $response = $this->postJson('/api/menu-items', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id', 'nombre', 'categoria', 'precio', 'descripcion', 'restaurant_id'
                 ]);

        $this->assertDatabaseHas('menu_items', ['nombre' => 'Limonada']);
    }

    /** @test */
    public function valida_campos_obligatorios_al_crear_item()
    {
        $payload = [
            'nombre' => '',
            'categoria' => '',
            'precio' => ''
        ];

        $response = $this->postJson('/api/menu-items', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nombre', 'categoria', 'precio']);
    }

    /** @test */
    public function evita_duplicar_items_con_mismo_nombre_y_restaurante()
    {
        MenuItem::factory()->create([
            'nombre' => 'Limonada',
            'restaurant_id' => 1
        ]);

        $payload = [
            'nombre' => 'Limonada',
            'categoria' => 'bebida',
            'precio' => 6.0,
            'restaurant_id' => 1
        ];

        $response = $this->postJson('/api/menu-items', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nombre']);
    }

    /** @test */
    public function puede_actualizar_un_item_existente()
    {
        $item = MenuItem::factory()->create([
            'nombre' => 'Limonada',
            'categoria' => 'bebida',
            'precio' => 5.0
        ]);

        $payload = ['precio' => 6.5];

        $response = $this->putJson("/api/menu-items/{$item->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'precio' => 6.5]);
    }

    /** @test */
    public function puede_eliminar_un_item_correctamente()
    {
        $item = MenuItem::factory()->create();

        $response = $this->deleteJson("/api/menu-items/{$item->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
    }
}
                                                                                                            