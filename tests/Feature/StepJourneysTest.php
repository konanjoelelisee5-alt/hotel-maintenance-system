<?php

namespace Tests\Feature;

use App\Models\RoomInspection;
use App\Models\Room;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les formulaires à plusieurs parties se remplissent en étapes, comme le signalement HK :
 * une étape à la fois, barre d'étapes commune (x-wizard.progress), « Continuer » qui
 * attend que l'étape soit complète (x-wizard.actions), navigation masquée (focus).
 */
class StepJourneysTest extends TestCase
{
    use RefreshDatabase;

    private function assertStepJourney($response, array $steps): void
    {
        $response->assertOk()
            ->assertSee('aria-label="Étapes"', false)
            ->assertSee(':disabled="!stepValid"', false)
            ->assertDontSee('data-bottom-nav', false)
            ->assertDontSee('@click="menuOpen = true"', false);

        foreach ($steps as $step) {
            $response->assertSee($step);
        }
    }

    public function test_reception_reports_a_fault_in_the_same_four_steps_as_housekeeping(): void
    {
        Room::create(['number' => '305', 'floor' => 'Étage 3']);
        $reception = User::factory()->reception()->create();

        $response = $this->actingAs($reception)->get(route('quick-reports.create'));
        $this->assertStepJourney($response, ['Lieu', 'Problème', 'Précisions', 'Vérifier et envoyer']);
        $response->assertSee('Il y a un client ?')->assertSee("Réclamation d'un client", false)->assertSee('Ce que dit le client');

        // Le HK garde son parcours, avec la même barre d'étapes.
        $this->assertStepJourney(
            $this->actingAs(User::factory()->housekeeping()->create())->get(route('quick-reports.create')),
            ['Lieu', 'Problème', 'Message vocal', 'Photo et envoi'],
        );
    }

    public function test_technician_report_uses_the_same_steps_without_guest_complaint(): void
    {
        $response = $this->actingAs(User::factory()->technicien()->create())->get(route('quick-reports.create'));

        $this->assertStepJourney($response, ['Lieu', 'Problème', 'Précisions', 'Vérifier et envoyer']);
        $response->assertSee('Ce que vous avez constaté')->assertDontSee("Réclamation d'un client", false);
    }

    public function test_purchase_order_is_created_in_three_steps(): void
    {
        Supplier::factory()->create();

        $this->assertStepJourney(
            $this->actingAs(User::factory()->manager()->create())->get(route('purchase-orders.create')),
            ['Fournisseur et livraison', 'Articles', 'Vérifier et créer', 'Créer le bon de commande'],
        );
    }

    public function test_room_inspection_goes_zone_by_zone(): void
    {
        Room::create(['number' => '214', 'floor' => 'Étage 2']);
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);
        $this->actingAs($head)->post(route('inspections.store'), ['room_number' => '214']);

        $this->assertStepJourney(
            $this->actingAs($head)->get(route('inspections.show', RoomInspection::firstOrFail())),
            ['Entrée', 'Chambre', 'Salle de bain', 'Remarques et fin', "Terminer l'inspection"],
        );
    }
}
