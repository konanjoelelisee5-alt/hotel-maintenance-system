<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderFactory extends Factory
{
    public function definition(): array
    {
        // On répartit la date de création sur les 90 derniers jours,
        // pour que les rapports (Module I) aient une vraie période à analyser.
        $createdAt = fake()->dateTimeBetween('-90 days', 'now');

        $titles = [
            'Climatiseur en panne', 'Fuite d\'eau salle de bain', 'Téléviseur ne s\'allume plus',
            'Serrure électronique bloquée', 'Ampoule à remplacer', 'Robinet qui goutte',
            'Prise électrique défectueuse', 'Porte qui grince', 'Wifi ne fonctionne pas',
        ];

        return [
            'title' => fake()->randomElement($titles),
            'description' => fake()->sentence(12),
            'room_id' => Room::inRandomOrder()->first()?->id,
            'equipment_id' => Equipment::inRandomOrder()->first()?->id,
            'reported_by' => User::inRandomOrder()->first()->id,
            'type_id' => WorkOrderType::inRandomOrder()->first()->id,
            'priority_id' => WorkOrderPriority::inRandomOrder()->first()->id,
            'status' => 'ouvert',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * État : OT complètement résolu, avec des dates cohérentes
     * (démarré puis terminé après la création).
     */
    public function resolved(): static
    {
        return $this->state(function (array $attributes) {
            $createdAt = $attributes['created_at'];
            $startedAt = fake()->dateTimeBetween($createdAt, '+2 hours');
            $completedAt = fake()->dateTimeBetween($startedAt, '+2 days');

            return [
                'status' => 'ferme',
                'assigned_to' => User::where('role', 'technicien')->inRandomOrder()->first()?->id,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
            ];
        });
    }

    /**
     * État : OT toujours ouvert/en cours, sans date de résolution.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => fake()->randomElement(['ouvert', 'en_cours', 'en_attente']),
            'assigned_to' => fake()->boolean(70)
                ? User::where('role', 'technicien')->inRandomOrder()->first()?->id
                : null,
        ]);
    }
}