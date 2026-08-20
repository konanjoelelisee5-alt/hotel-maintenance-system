<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        $types = ['Climatiseur', 'Chauffe-eau', 'Téléviseur', 'Réfrigérateur', 'Ascenseur', 'Éclairage', 'Serrure électronique'];
        $type = fake()->randomElement($types);

        return [
            'name' => $type . ' ' . fake()->bothify('##??'),
            'type' => $type,
            'room_id' => Room::inRandomOrder()->first()?->id,
            'status' => fake()->randomElement(['operationnel', 'operationnel', 'operationnel', 'maintenance']),
        ];
    }
}