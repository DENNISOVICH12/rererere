<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        $nombre = $this->faker->company();

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre . '-' . $this->faker->unique()->numberBetween(1, 9999)),
            'direccion' => $this->faker->address(),
            'telefono' => $this->faker->phoneNumber(),
        ];
    }
}
