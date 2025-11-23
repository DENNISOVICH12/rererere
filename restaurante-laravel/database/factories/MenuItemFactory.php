<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\MenuItem;

class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->word(),
            'descripcion' => $this->faker->sentence(),
            'categoria' => $this->faker->randomElement(['bebida', 'plato', 'postre']),
            'precio' => $this->faker->randomFloat(2, 5, 30),
            'imagen' => null,
            'disponible' => true,
            'restaurant_id' => 1
        ];
    }
}
