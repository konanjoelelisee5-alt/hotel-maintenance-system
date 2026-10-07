<?php

namespace Tests\Feature;

use App\Enums\RoomOccupancy;
use App\Models\InterventionReport;
use App\Models\Part;
use App\Models\PartReservation;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Support\ReceptionDesk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ce que le technicien doit savoir avant d'entrer dans la chambre : la situation du
 * client, les réparations déjà faites au même endroit, et (accueil) ce qu'il doit
 * prendre au magasin.
 */
class TechnicianFieldInfoTest extends TestCase
{
    use RefreshDatabase;

    private User $technician;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->technician = User::factory()->technicien()->create();
        $this->room = Room::create(['number' => '305', 'floor' => 'Étage 3']);
    }

    private function workOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Fuite lavabo',
            'room_id' => $this->room->id,
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'assigned_to' => $this->technician->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'urgente')->firstOrFail()->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
    }

    public function test_work_order_page_shows_the_guest_situation_set_by_reception(): void
    {
        $workOrder = $this->workOrder(['room_occupancy' => RoomOccupancy::ClientPresent]);

        $this->actingAs($this->technician)->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Client dans la chambre')
            ->assertSee('Frappez et présentez-vous', false);

        $this->travelTo(now()->setTime(10, 0));
        ReceptionDesk::updateSituation($this->room, 'sorti', '15:00', null, 'Il revient avec sa famille', User::factory()->reception()->create());

        $this->actingAs($this->technician)->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Client sorti')
            ->assertSee('Retour prévu à 15h00 : réparez avant.')
            ->assertSee('Il revient avec sa famille');
    }

    public function test_no_guest_notice_once_repaired_or_in_a_common_area(): void
    {
        $repaired = $this->workOrder(['room_occupancy' => RoomOccupancy::ClientPresent, 'status' => 'resolu', 'completed_at' => now()]);
        $this->assertNull($repaired->guestNotice());

        $hall = Room::create(['number' => 'HALL', 'name' => 'Hall', 'type' => Room::TYPE_COMMON_AREA]);
        $this->assertNull($this->workOrder(['room_id' => $hall->id, 'room_occupancy' => RoomOccupancy::ClientPresent])->guestNotice());
    }

    public function test_previous_repairs_of_the_same_room_are_listed_with_what_was_done(): void
    {
        $other = User::factory()->technicien()->create(['name' => 'Yao Tech']);
        $old = $this->workOrder(['title' => 'Fuite lavabo ancienne', 'assigned_to' => $other->id, 'status' => 'ferme', 'completed_at' => now()->subDays(20)]);
        InterventionReport::create(['work_order_id' => $old->id, 'technician_id' => $other->id, 'work_performed' => 'Joint changé', 'recommendations' => 'Remplacer le siphon']);
        $this->workOrder(['title' => 'Trop ancienne', 'status' => 'ferme', 'completed_at' => now()->subDays(WorkOrder::PREVIOUS_REPAIRS_DAYS + 5)]);
        $current = $this->workOrder();

        $this->actingAs($this->technician)->get(route('work-orders.show', $current))
            ->assertOk()
            ->assertSee('Déjà réparé ici')
            ->assertSee('Fuite lavabo ancienne')
            ->assertSee('Yao Tech')
            ->assertSee('Joint changé')
            ->assertSee('Remplacer le siphon')
            ->assertDontSee('Trop ancienne')
            // L'OT d'un collègue n'est pas ouvrable par ce technicien : pas de lien.
            ->assertDontSee(route('work-orders.show', $old));
    }

    public function test_dashboard_lists_parts_to_pick_up_and_guests_without_the_misleading_stock_line(): void
    {
        $part = Part::create([
            'sku' => 'PART-001', 'name' => 'Joint robinet', 'unit' => 'unité',
            'quantity_on_hand' => 1, 'quantity_reserved' => 0, 'reorder_threshold' => 5, 'unit_cost' => 2.5, 'is_active' => true,
        ]);
        $workOrder = $this->workOrder(['room_occupancy' => RoomOccupancy::ClientPresent]);
        PartReservation::create(['part_id' => $part->id, 'work_order_id' => $workOrder->id, 'quantity' => 2, 'status' => 'reservee', 'reserved_by' => $this->technician->id]);

        $this->actingAs($this->technician)->get(route('technicien.dashboard'))
            ->assertOk()
            ->assertSee('Prendre au magasin : 2 unité Joint robinet')
            ->assertSee('Chambre 305 : client dans la chambre')
            ->assertDontSee('disponible au magasin');
    }
}
