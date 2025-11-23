<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition()
    {
        return [
            'nombre_cliente' => $this->faker->name(),
            'telefono' => $this->faker->numerify('3#########'),
            'direccion' => $this->faker->address(),
        ];
    }
}
