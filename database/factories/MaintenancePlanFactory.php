<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenancePlanFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'name' => 'Contrôle ' . fake()->randomElement(['mensuel', 'trimestriel', 'annuel']) . ' — ' . fake()->word(),
            'description' => fake()->sentence(10),
            'equipment_id' => Equipment::inRandomOrder()->first()?->id,
            'work_order_type_id' => WorkOrderType::where('code', 'preventif')->first()?->id
                ?? WorkOrderType::inRandomOrder()->first()->id,
            'work_order_priority_id' => WorkOrderPriority::inRandomOrder()->first()->id,
            'estimated_duration_minutes' => fake()->randomElement([30, 60, 90, 120]),
            'frequency_unit' => 'mois',
            'frequency_interval' => 1,
            'lead_time_days' => 0,
            'start_date' => $startDate,
            'next_due_at' => $startDate,
            'is_active' => true,
            'created_by' => User::where('role', 'admin')->inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }

    public function dueToday(): static
    {
        return $this->state(fn () => [
            'next_due_at' => today(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
