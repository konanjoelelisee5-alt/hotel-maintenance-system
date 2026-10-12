<?php

namespace Tests\Feature;

use App\Models\InterventionSession;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Support\WorkOrderJourney;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le parcours commun d'un OT (Signalé → Pris en charge → En réparation → Réparé →
 * Contrôlé → Confirmé) et la prochaine étape de chacun sur la fiche.
 */
class WorkOrderJourneyTest extends TestCase
{
    use RefreshDatabase;

    private User $technician;

    private User $housekeeper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->technician = User::factory()->technicien()->create(['name' => 'Kouassi Tech']);
        $this->housekeeper = User::factory()->housekeeping()->create();
    }

    private function workOrder(array $attributes = [], ?User $reporter = null): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Fuite lavabo',
            'room_id' => Room::firstOrCreate(['number' => '101'], ['floor' => 'Étage 1'])->id,
            'reported_by' => ($reporter ?? $this->housekeeper)->id,
            'assigned_to' => $this->technician->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
    }

    /** @return array<string, string> clé d'étape => état */
    private function states(WorkOrder $workOrder): array
    {
        return array_column(WorkOrderJourney::steps($workOrder->fresh()), 'state', 'key');
    }

    public function test_steps_follow_the_work_order_from_report_to_confirmation(): void
    {
        $workOrder = $this->workOrder(['assigned_to' => null]);
        $this->assertSame(['signale' => 'current', 'pris_en_charge' => 'todo', 'en_reparation' => 'todo', 'repare' => 'todo', 'controle' => 'todo', 'confirme' => 'todo'], $this->states($workOrder));

        $workOrder->update(['assigned_to' => $this->technician->id]);
        $this->assertSame('current', $this->states($workOrder)['pris_en_charge']);

        $workOrder->update(['status' => 'en_cours', 'started_at' => now()]);
        $this->assertSame('current', $this->states($workOrder)['en_reparation']);

        $workOrder->update(['status' => 'resolu', 'completed_at' => now()]);
        $states = $this->states($workOrder);
        $this->assertSame(['done', 'current', 'todo'], [$states['en_reparation'], $states['repare'], $states['controle']]);

        // Le demandeur confirme avant le contrôle qualité : les deux se font en parallèle.
        $workOrder->update(['requester_confirmed_at' => now(), 'requester_confirmed_by' => $this->housekeeper->id]);
        $states = $this->states($workOrder);
        $this->assertSame(['current', 'todo', 'done'], [$states['repare'], $states['controle'], $states['confirme']]);

        $workOrder->update(['status' => 'ferme']);
        $this->assertSame(['done'], array_values(array_unique($this->states($workOrder))));
    }

    public function test_a_stopped_step_is_labelled_and_coloured(): void
    {
        $workOrder = $this->workOrder(['status' => 'en_attente', 'started_at' => now()]);
        $current = collect(WorkOrderJourney::steps($workOrder))->firstWhere('state', 'current');
        $this->assertSame(['en_reparation', 'En attente'], [$current['key'], $current['alert'][0]]);

        $workOrder->update(['status' => 'rejete']);
        $current = collect(WorkOrderJourney::steps($workOrder->fresh()))->firstWhere('state', 'current');
        $this->assertSame(['en_reparation', 'Correction demandée'], [$current['key'], $current['alert'][0]]);
    }

    public function test_confirmation_step_only_for_requester_services_and_skipped_after_the_window(): void
    {
        $byManager = $this->workOrder([], User::factory()->manager()->create());
        $this->assertArrayNotHasKey('confirme', $this->states($byManager));

        $old = $this->workOrder(['status' => 'ferme', 'started_at' => now()->subDays(30), 'completed_at' => now()->subDays(20)]);
        $states = $this->states($old);
        $this->assertSame(['done', 'skipped'], [$states['controle'], $states['confirme']]);
    }

    public function test_technician_gets_one_next_step_button_per_state(): void
    {
        $workOrder = $this->workOrder();
        $page = fn () => $this->actingAs($this->technician)->get(route('work-orders.show', $workOrder))->assertOk();

        $page()->assertSee('À vous')->assertSee("J'ai vu, je m'en occupe", false);

        $workOrder->update(['acknowledged_at' => now()]);
        $page()->assertSee('démarrez le chrono', false)->assertDontSee("J'ai vu, je m'en occupe", false);

        $workOrder->update(['status' => 'en_cours', 'started_at' => now()]);
        InterventionSession::create(['work_order_id' => $workOrder->id, 'technician_id' => $this->technician->id, 'started_at' => now()]);
        $page()->assertSee('Chrono en marche depuis')->assertSee('étape 2 sur 4')
            ->assertSee("mission-step', 2", false)
            ->assertSee('La panne est-elle réparée ?')
            ->assertSee("Oui, c'est réparé", false)
            ->assertSee('Non, je suis bloqué');

        $workOrder->update(['status' => 'resolu', 'completed_at' => now()]);
        $page()->assertSee('Rien à faire pour l\'instant', false)->assertDontSee('La panne est-elle réparée ?');
    }

    public function test_intervention_is_a_four_step_wizard_like_the_housekeeping_report(): void
    {
        $workOrder = $this->workOrder(['acknowledged_at' => now()]);

        $this->actingAs($this->technician)->get(route('work-orders.show', $workOrder))->assertOk()
            ->assertSee('étape 1 sur 4')
            ->assertSeeInOrder(['Préparer', 'Sur place', 'Pièces utilisées', 'Rapport et signature'])
            ->assertSee('Avez-vous besoin de pièces ?')
            // « Continuer » grisé tant que l'étape n'est pas complète, la raison dessous.
            ->assertSee(':disabled="! stepValid"', false)
            ->assertSee('form="report-form"', false);
    }

    public function test_being_blocked_puts_the_order_on_hold_and_stops_the_chrono(): void
    {
        $workOrder = $this->workOrder(['status' => 'en_cours', 'started_at' => now()]);
        InterventionSession::create(['work_order_id' => $workOrder->id, 'technician_id' => $this->technician->id, 'started_at' => now()->subMinutes(20)]);

        $this->actingAs($this->technician)
            ->patch(route('work-orders.status.update', $workOrder), ['status' => 'en_attente', 'note' => 'Client dans la chambre : revient à 15 h'])
            ->assertRedirect();

        $this->assertSame('en_attente', $workOrder->fresh()->status);
        $this->assertNull($workOrder->fresh()->activeSession(), 'Le chrono doit être arrêté.');
        $this->actingAs($this->technician)->get(route('work-orders.show', $workOrder))
            ->assertSee('Vous êtes bloqué')->assertSee('Reprendre la réparation');
    }

    public function test_signing_ends_on_a_sent_screen_then_the_technician_waits_for_verification(): void
    {
        $workOrder = $this->workOrder(['status' => 'en_cours', 'started_at' => now(), 'acknowledged_at' => now()]);
        $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        // Brouillon : retour à l'étape « Rapport et signature ».
        $this->actingAs($this->technician)
            ->post(route('work-orders.report.store', $workOrder), ['work_performed' => 'Joint changé'])
            ->assertRedirect(route('work-orders.show', $workOrder).'#etape-4');

        // Signé : l'écran « Réparation envoyée », d'où l'on sort.
        $this->actingAs($this->technician)
            ->post(route('work-orders.report.store', $workOrder), ['work_performed' => 'Joint changé', 'signature' => $signature, 'signed_by_name' => 'M. Kouamé'])
            ->assertRedirect(route('work-orders.report.sent', $workOrder));
        $this->actingAs($this->technician)->get(route('work-orders.report.sent', $workOrder))->assertOk()
            ->assertSee('Réparation envoyée')
            ->assertSee('vérifiée', false)
            ->assertSee('La vérification')
            ->assertSee('Retour à ma journée');
        // Dans l'assistant : en attente de vérification, et la sortie.
        $this->actingAs($this->technician)->get(route('work-orders.show', $workOrder))
            ->assertSee('En attente de vérification')->assertSee('Retour à ma journée');

        // Le contrôle qualité valide : le technicien est prévenu.
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager)->post(route('quality-controls.store', $workOrder));
        $qualityControl = $workOrder->qualityControls()->latest('id')->first();
        $this->actingAs($manager)->post(route('quality-controls.review', $qualityControl), ['decision' => 'approuve']);

        $this->assertSame('ferme', $workOrder->fresh()->status);
        $this->assertTrue($this->technician->notifications()->where('data->decision', 'approuve')->exists());
    }

    public function test_sent_screen_is_only_for_a_signed_report(): void
    {
        $workOrder = $this->workOrder(['status' => 'en_cours']);

        $this->actingAs($this->technician)->get(route('work-orders.report.sent', $workOrder))
            ->assertRedirect(route('work-orders.show', $workOrder));
    }

    public function test_missing_part_request_stops_the_chrono_too(): void
    {
        $workOrder = $this->workOrder(['status' => 'en_cours', 'started_at' => now()]);
        InterventionSession::create(['work_order_id' => $workOrder->id, 'technician_id' => $this->technician->id, 'started_at' => now()->subMinutes(10)]);

        $this->actingAs($this->technician)
            ->post(route('work-orders.part-requests.store', $workOrder), ['description' => 'Mitigeur Grohe 1/2', 'quantity' => 1, 'suspend' => 1])
            ->assertRedirect();

        $this->assertSame('en_attente', $workOrder->fresh()->status);
        $this->assertNull($workOrder->fresh()->activeSession());
    }

    public function test_observers_read_the_next_step_under_the_journey(): void
    {
        $workOrder = $this->workOrder(['assigned_to' => null]);
        $reception = User::factory()->reception()->create();
        $workOrder->update(['reported_by' => $reception->id]);

        $this->actingAs($reception)->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Pris en charge')
            ->assertSee('Prochaine étape :')
            ->assertSee('La maintenance va affecter un technicien.')
            ->assertDontSee('À vous');
    }

    public function test_housekeeping_detail_shows_the_same_journey(): void
    {
        $workOrder = $this->workOrder(['status' => 'en_cours', 'started_at' => now()]);

        $this->actingAs($this->housekeeper)->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSeeInOrder(['Signalé', 'Pris en charge', 'En réparation', 'Réparé', 'Contrôlé', 'Confirmé']);
    }
}
