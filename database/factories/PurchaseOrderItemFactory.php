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
            // Franc CFA, montants ronds (de 500 à 100 000 FCFA l'unité).
            'unit_price' => fake()->numberBetween(10, 2000) * 50,
        ];
    }
}