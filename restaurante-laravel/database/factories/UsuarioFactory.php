<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use app\Models\Usuario;
use Illuminate\Support\Str;

class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'usuario' => $this->faker->userName(),
            'password' => bcrypt('password'),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'correo' => $this->faker->unique()->safeEmail(),
            'rol' => $this->faker->randomElement(['admin', 'mesero', 'cocinero', 'cliente', 'empleado']),
            'activo' => true,
            'restaurant_id' => 1,
        ];
    }
}
