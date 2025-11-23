<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Usuario;

class UsuarioControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_crear_un_usuario_correctamente()
    {
        $payload = [
            'usuario' => 'admin',
            'password' => 'secreto123',
            'nombre' => 'Administrador',
            'apellido' => 'General',
            'correo' => 'admin@example.com',
            'rol' => 'admin',
            'activo' => true,
            'restaurant_id' => 1
        ];

        $response = $this->postJson('/api/usuarios', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'id',
                         'usuario',
                         'nombre',
                         'correo',
                         'rol'
                     ]
                 ]);

        $this->assertDatabaseHas('usuarios', ['usuario' => 'admin']);
    }

    /** @test */
    public function valida_campos_obligatorios_al_crear_usuario()
    {
        $payload = [
            'usuario' => '',
            'password' => '',
            'nombre' => '',
            'correo' => '',
            'rol' => ''
        ];

        $response = $this->postJson('/api/usuarios', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['usuario', 'password', 'nombre', 'correo', 'rol']);
    }

    /** @test */
    public function evita_duplicar_usuario_por_nombre_o_correo()
    {
        Usuario::factory()->create([
            'usuario' => 'admin',
            'correo' => 'admin@example.com'
        ]);

        $payload = [
            'usuario' => 'admin',
            'password' => 'otro123',
            'nombre' => 'Otro Admin',
            'correo' => 'admin@example.com',
            'rol' => 'mesero'
        ];

        $response = $this->postJson('/api/usuarios', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['usuario', 'correo']);
    }

    /** @test */
    public function puede_actualizar_un_usuario_existente()
    {
        $user = Usuario::factory()->create([
            'usuario' => 'original',
            'correo' => 'original@example.com',
            'rol' => 'mesero'
        ]);

        $payload = ['nombre' => 'Modificado', 'correo' => 'nuevo@example.com'];

        $response = $this->putJson("/api/usuarios/{$user->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment(['correo' => 'nuevo@example.com']);

        $this->assertDatabaseHas('usuarios', ['correo' => 'nuevo@example.com']);
    }

    /** @test */
    public function puede_eliminar_un_usuario_existente()
    {
        $user = Usuario::factory()->create();

        $response = $this->deleteJson("/api/usuarios/{$user->id}");

        $response->assertStatus(200)
                 ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('usuarios', ['id' => $user->id]);
    }
}
