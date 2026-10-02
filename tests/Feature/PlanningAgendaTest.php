<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Agenda du planning : les pages chargent l'agenda, et le flux d'interventions
 * donne de quoi afficher une carte complète, limité à ce que chacun peut voir.
 */
class PlanningAgendaTest extends TestCase
{
    use RefreshDatabase;

    private function scheduled(User $technician, string $at, array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Climatisation bruyante',
            'reported_by' => User::factory()->reception()->create()->id,
            'assigned_to' => $technician->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'urgente')->firstOrFail()->id,
            'status' => 'ouvert',
            'scheduled_at' => $at,
            'estimated_duration_minutes' => 90,
            ...$attributes,
        ]);
    }

    private function events(User $user, array $query = [])
    {
        return $this->actingAs($user)->getJson(route('planning.events', [
            'start' => '2026-10-05', 'end' => '2026-10-12', ...$query,
        ]));
    }

    public function test_team_planning_shows_the_agenda_with_technician_filters(): void
    {
        $technician = User::factory()->technicien()->create(['name' => 'Yao Konan']);

        $this->actingAs(User::factory()->manager()->create())->get(route('planning.index'))
            ->assertOk()
            ->assertSee('x-data="agenda(', false)
            ->assertSee("Toute l'équipe", false)
            ->assertSee('Yao Konan')
            ->assertDontSee('fullcalendar');
    }

    public function test_technician_planning_shows_their_agenda_without_filters(): void
    {
        $technician = User::factory()->technicien()->create();

        $this->actingAs($technician)->get(route('planning.technician', $technician))
            ->assertOk()
            ->assertSee('Mon planning')
            ->assertSee('x-data="agenda(', false)
            ->assertDontSee("Toute l'équipe", false)
            ->assertDontSee('Disponibilités');
    }

    public function test_events_carry_what_the_card_displays(): void
    {
        $technician = User::factory()->technicien()->create(['name' => 'Yao Konan']);
        $room = Room::create(['number' => '214', 'floor' => '2']);
        $workOrder = $this->scheduled($technician, '2026-10-07 09:30:00', ['room_id' => $room->id]);
        $this->scheduled($technician, '2026-10-20 09:30:00'); // hors de la semaine demandée

        $this->events(User::factory()->manager()->create())
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.code', $workOrder->code())
            ->assertJsonPath('0.title', 'Climatisation bruyante')
            ->assertJsonPath('0.minutes', 90)
            ->assertJsonPath('0.urgent', true)
            ->assertJsonPath('0.status_label', $workOrder->status_label)
            ->assertJsonPath('0.place', $room->label)
            ->assertJsonPath('0.technician', 'Yao Konan')
            ->assertJsonPath('0.initials', 'YK')
            ->assertJsonPath('0.url', route('work-orders.show', $workOrder));
    }

    public function test_team_events_can_be_filtered_by_technician(): void
    {
        $yao = User::factory()->technicien()->create();
        $awa = User::factory()->technicien()->create();
        $this->scheduled($yao, '2026-10-06 08:00:00');
        $this->scheduled($awa, '2026-10-06 10:00:00');

        $manager = User::factory()->manager()->create();
        $this->events($manager)->assertJsonCount(2);
        $this->events($manager, ['technician_id' => $awa->id])->assertJsonCount(1)->assertJsonPath('0.technician', $awa->name);
    }

    public function test_technician_only_receives_their_own_events(): void
    {
        $yao = User::factory()->technicien()->create();
        $awa = User::factory()->technicien()->create();
        $this->scheduled($yao, '2026-10-06 08:00:00');
        $this->scheduled($awa, '2026-10-06 10:00:00');

        // Même en demandant les interventions d'un collègue.
        $this->events($yao, ['technician_id' => $awa->id])->assertJsonCount(1)->assertJsonPath('0.technician', $yao->name);
    }

    public function test_events_require_a_valid_period(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->getJson(route('planning.events', ['start' => '2026-10-12', 'end' => '2026-10-05']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('end');
    }
}
