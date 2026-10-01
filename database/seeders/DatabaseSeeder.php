<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
use App\Models\EscalationRule;
use App\Models\PurchaseOrder;
use App\Models\Room;
use App\Models\Skill;
use App\Models\SlaPolicy;
use App\Models\Supplier;
use App\Models\TechnicianAvailability;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Les comptes de démo ont tous le mot de passe "password" : sur un vrai
        // serveur, ils ouvriraient l'application à n'importe qui. Seule une instance
        // déclarée démo (SEED_DEMO_DATA=true, cf. render.yaml) peut les charger.
        if (app()->isProduction() && ! config('app.demo')) {
            $this->command->error('Refusé : données de démo interdites en production (SEED_DEMO_DATA n\'est pas activé).');
            $this->command->line('Pour créer le premier administrateur : php artisan admin:create <email> "<nom>"');

            return;
        }

        $this->command->info('Création des utilisateurs...');
        $this->seedUsers();

        $this->command->info('Création des compétences et disponibilités...');
        $this->seedSkillsAndAvailabilities();

        $this->command->info('Création des chambres et équipements...');
        $this->seedRoomsAndEquipment();

        $this->command->info('Création des politiques SLA et règles d\'escalade...');
        $this->seedSlaAndEscalation();

        $this->command->info('Création des checklists qualité...');
        $this->seedChecklistTemplates();

        $this->command->info('Création des ordres de travail...');
        $this->seedWorkOrders();

        $this->command->info('Création des fournisseurs et commandes...');
        $this->seedPurchasing();

        $this->command->info('Base de données peuplée avec succès !');
    }

    private function seedUsers(): void
    {
        // Organisation cible : deux admins qui se contrôlent mutuellement
        // (chef de maintenance + responsable informatique), et des responsables
        // de service HK / réception qui voient leur équipe sans paramétrer.
        // Le chef de maintenance est d'astreinte la nuit ; le responsable
        // informatique ne reçoit pas les alertes de maintenance.
        User::factory()->admin()->create([
            'name' => 'Admin Principal',
            'email' => 'admin@hotel-test.com',
            'phone' => '+2250700000001',
        ]);
        User::factory()->admin()->create([
            'name' => 'Responsable Informatique',
            'email' => 'informatique@hotel-test.com',
            'receives_maintenance_alerts' => false,
        ]);
        User::factory()->housekeeping()->create([
            'name' => 'Responsable Housekeeping',
            'email' => 'resp.housekeeping@hotel-test.com',
            'is_department_head' => true,
        ]);
        User::factory()->reception()->create([
            'name' => 'Responsable Réception',
            'email' => 'resp.reception@hotel-test.com',
            'is_department_head' => true,
        ]);

        User::factory()->manager()->create([
            'name' => 'Manager Principal',
            'email' => 'manager@hotel-test.com',
            'phone' => '+2250700000002',
        ]);
        // Volontairement sans téléphone : la page Astreinte doit le signaler.
        User::factory()->manager()->count(2)->create();

        User::factory()->technicien()->count(6)->create();
        User::factory()->housekeeping()->count(3)->create();
        User::factory()->reception()->count(3)->create();
    }

    private function seedSkillsAndAvailabilities(): void
    {
        $skillNames = ['Électricité', 'Plomberie', 'Climatisation', 'Menuiserie', 'Peinture', 'Informatique/Réseau', 'Sécurité incendie'];

        foreach ($skillNames as $name) {
            Skill::firstOrCreate(['name' => $name]);
        }

        $skills = Skill::all();
        $technicians = User::where('role', 'technicien')->get();

        foreach ($technicians as $technician) {
            // Chaque technicien reçoit 1 à 3 compétences aléatoires
            $technician->skills()->sync(
                $skills->random(fake()->numberBetween(1, 3))->pluck('id')
            );

            // Disponibilité récurrente : du lundi au vendredi, 8h-17h
            foreach (range(1, 5) as $dayOfWeek) {
                TechnicianAvailability::create([
                    'user_id' => $technician->id,
                    'type' => 'disponible',
                    'day_of_week' => $dayOfWeek,
                    'start_time' => '08:00:00',
                    'end_time' => '17:00:00',
                ]);
            }
        }
    }

    private function seedRoomsAndEquipment(): void
    {
        Room::factory()->count(30)->create();

        \App\Models\Equipment::factory()->count(40)->create();

        // Espaces communs d'un hôtel et leurs équipements : une grande partie des
        // pannes (chaudière, pompe de piscine, ascenseur) ne concerne aucune chambre.
        $commonAreas = [
            ['CHAUF', 'Chaufferie', 'Sous-sol', [['Chaudière principale', 'Chaudière'], ['Ballon d\'eau chaude 1000 L', 'Chauffe-eau']]],
            ['CUIS', 'Cuisine', 'Rez-de-chaussée', [['Chambre froide positive', 'Chambre froide'], ['Four mixte', 'Four'], ['Hotte d\'extraction', 'Ventilation']]],
            ['PISC', 'Piscine', 'Extérieur', [['Pompe de filtration', 'Pompe'], ['Électrolyseur au sel', 'Traitement de l\'eau']]],
            ['HALL', 'Hall d\'accueil', 'Rez-de-chaussée', [['Porte automatique', 'Porte'], ['Climatisation centrale du hall', 'Climatiseur']]],
            ['ASC', 'Ascenseurs', 'Tous étages', [['Ascenseur clients A', 'Ascenseur'], ['Ascenseur service', 'Ascenseur']]],
            ['BUAN', 'Buanderie', 'Sous-sol', [['Lave-linge industriel 1', 'Lave-linge'], ['Séchoir industriel', 'Séchoir']]],
            ['REST', 'Restaurant', 'Rez-de-chaussée', [['Climatisation du restaurant', 'Climatiseur']]],
            ['PARK', 'Parking', 'Extérieur', [['Barrière d\'entrée', 'Barrière'], ['Éclairage du parking', 'Éclairage']]],
        ];

        foreach ($commonAreas as [$code, $name, $floor, $equipment]) {
            $area = Room::create(['type' => Room::TYPE_COMMON_AREA, 'number' => $code, 'name' => $name, 'floor' => $floor]);

            foreach ($equipment as [$equipmentName, $type]) {
                $area->equipment()->create(['name' => $equipmentName, 'type' => $type]);
            }
        }
    }

    private function seedSlaAndEscalation(): void
    {
        SlaPolicy::create([
            'name' => 'Politique par défaut',
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 480,
        ]);

        SlaPolicy::create([
            'name' => 'Priorité urgente',
            'priority' => 'urgente',
            'response_time_minutes' => 15,
            'resolution_time_minutes' => 120,
        ]);

        SlaPolicy::create([
            'name' => 'Priorité haute',
            'priority' => 'haute',
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 240,
        ]);

        EscalationRule::create([
            'name' => 'Résolution dépassée - Manager',
            'trigger_type' => 'resolution_depassee',
            'offset_minutes' => 0,
            'notify_target' => 'manager',
        ]);

        // Décalage positif : 3 h APRÈS l'échéance (une valeur négative déclencherait
        // la règle avant même que le délai soit dépassé).
        EscalationRule::create([
            'name' => 'Résolution très en retard - Admin',
            'trigger_type' => 'resolution_depassee',
            'offset_minutes' => 180,
            'notify_target' => 'admin',
        ]);

        // Filet de sécurité de l'alerte immédiate : personne n'a pris l'OT en charge
        // dans le délai de réponse → on relance l'équipe de garde (téléphone si urgent/haut).
        EscalationRule::create([
            'name' => 'Réponse dépassée - Astreinte',
            'trigger_type' => 'reponse_depassee',
            'offset_minutes' => 0,
            'notify_target' => 'astreinte',
        ]);
    }

    private function seedChecklistTemplates(): void
    {
        $template = ChecklistTemplate::create([
            'name' => 'Checklist générale de vérification',
        ]);

        $points = [
            'Zone de travail nettoyée après intervention',
            'Fonctionnement testé et validé',
            'Aucun dommage collatéral constaté',
            'Client/occupant informé de la résolution',
        ];

        foreach ($points as $index => $label) {
            $template->items()->create(['label' => $label, 'position' => $index + 1]);
        }
    }

    private function seedWorkOrders(): void
    {
        // 70 % des OT sont résolus et fermés (historique), 30 % encore actifs
        WorkOrder::factory()->count(70)->resolved()->create();
        WorkOrder::factory()->count(30)->open()->create();
    }

    private function seedPurchasing(): void
    {
        Supplier::factory()->count(8)->create();

        PurchaseOrder::factory()->count(20)->create()->each(function (PurchaseOrder $purchaseOrder) {
            \App\Models\PurchaseOrderItem::factory()
                ->count(fake()->numberBetween(1, 5))
                ->for($purchaseOrder)
                ->create();
        });
    }
}