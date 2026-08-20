<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    public function definition(): array
    {
        $articles = ['Ampoules LED', 'Filtre climatisation', 'Robinetterie', 'Câble électrique', 'Peinture blanche', 'Joint plomberie'];

        return [
            'description' => fake()->randomElement($articles),
            'quantity' => fake()->numberBetween(1, 20),
            'unit_price' => fake()->randomFloat(2, 2, 150),
        ];
    }
}