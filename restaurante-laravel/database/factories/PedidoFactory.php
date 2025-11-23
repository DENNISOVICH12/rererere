<?php

namespace Database\Factories;

use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'restaurant_id' => Restaurant::factory(),
            'total' => $this->faker->randomFloat(2, 10000, 200000),
        ];
    }
}
