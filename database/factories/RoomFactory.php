<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        // On génère directement un numéro de chambre unique à 3 chiffres (ex: 101, 205, 812),
        // où le premier chiffre représente l'étage.
        $roomNumber = fake()->unique()->numberBetween(101, 899);
        $floor = (int) substr((string) $roomNumber, 0, 1);

        return [
            'number' => (string) $roomNumber,
            'floor' => 'Étage ' . $floor,
            'status' => fake()->randomElement(['disponible', 'disponible', 'disponible', 'occupee', 'maintenance']),
        ];
    }
}