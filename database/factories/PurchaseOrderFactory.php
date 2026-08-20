<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    /**
     * Compteur partagé entre toutes les instances générées par cette Factory,
     * pour éviter les doublons de numéro même lors d'une création en lot
     * (Factory::count()->create() construit tous les enregistrements en mémoire
     * avant de les sauvegarder, donc on ne peut pas se fier à la base de données
     * pour connaître "le dernier numéro" pendant cette phase).
     */
    protected static ?int $lastNumber = null;

    public function definition(): array
    {
        $orderDate = fake()->dateTimeBetween('-90 days', 'now');

        if (static::$lastNumber === null) {
            static::$lastNumber = PurchaseOrder::where('number', 'like', 'BC-' . now()->year . '-%')
                ->get()
                ->map(fn ($po) => (int) substr($po->number, -4))
                ->max() ?? 0;
        }

        static::$lastNumber++;

        return [
            'number' => sprintf('BC-%d-%04d', now()->year, static::$lastNumber),
            'supplier_id' => Supplier::inRandomOrder()->first()->id,
            'created_by' => User::where('role', 'manager')->inRandomOrder()->first()?->id
                ?? User::inRandomOrder()->first()->id,
            'status' => fake()->randomElement(['brouillon', 'envoyee', 'confirmee', 'receptionnee']),
            'order_date' => $orderDate,
            'expected_delivery_date' => fake()->dateTimeBetween($orderDate, '+2 weeks'),
        ];
    }
}