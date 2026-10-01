<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAndEquipmentTest extends TestCase
{
    use RefreshDatabase;

    // ===== Libellé intelligent =====

    public function test_label_distinguishes_rooms_and_common_areas(): void
    {
        $this->assertSame('Chambre 312', Room::factory()->create(['number' => '312'])->label);
        $this->assertSame('Piscine', Room::factory()->commonArea('PISC', 'Piscine')->create()->label);
    }

    public function test_equipment_label_includes_its_location(): void
    {
        $pool = Room::factory()->commonArea('PISC', 'Piscine')->create();
        $pump = Equipment::create(['name' => 'Pompe de filtration', 'room_id' => $pool->id]);

        $this->assertSame('Pompe de filtration — Piscine', $pump->label);
    }

    // ===== Gestion des lieux =====

    public function test_manager_can_create_a_common_area(): void
    {
        $this->actingAs(User::factory()->manager()->create())->post(route('rooms.store'), [
            'type' => 'espace_commun',
            'number' => ' chauf ',
            'name' => 'Chaufferie',
            'floor' => 'Sous-sol',
            'status' => 'disponible',
        ])->assertSessionHasNoErrors();

        $room = Room::where('number', 'CHAUF')->firstOrFail();
        $this->assertTrue($room->isCommonArea());
        $this->assertTrue(ActivityLog::where('action', 'room.created')->exists());
    }

    public function test_common_area_requires_a_name_and_cannot_be_occupied(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('rooms.store'), [
            'type' => 'espace_commun',
            'number' => 'HALL',
            'status' => 'occupee',
        ])->assertSessionHasErrors(['name', 'status']);
    }

    public function test_room_number_must_be_unique(): void
    {
        Room::factory()->create(['number' => '312']);

        $this->actingAs(User::factory()->admin()->create())->post(route('rooms.store'), [
            'type' => 'chambre',
            'number' => '312',
            'status' => 'disponible',
        ])->assertSessionHasErrors('number');
    }

    public function test_destroy_puts_room_out_of_service_and_keeps_history(): void
    {
        $room = Room::factory()->create(['number' => '214']);
        $workOrder = $this->workOrderIn($room);

        $this->actingAs(User::factory()->manager()->create())
            ->delete(route('rooms.destroy', $room))
            ->assertRedirect(route('rooms.show', $room))
            ->assertSessionHas('warning');

        $this->assertSame('hors_service', $room->fresh()->status);
        $this->assertSame($room->id, $workOrder->fresh()->room_id);
    }

    public function test_requesting_services_and_technicians_cannot_manage_places(): void
    {
        foreach (['housekeeping', 'reception', 'technicien'] as $role) {
            $this->actingAs(User::factory()->{$role}()->create())->get(route('rooms.index'))->assertForbidden();
            $this->actingAs(User::factory()->{$role}()->create())->get(route('equipment.index'))->assertForbidden();
        }
    }

    public function test_room_page_shows_equipment_and_work_order_history(): void
    {
        $pool = Room::factory()->commonArea()->create();
        Equipment::create(['name' => 'Pompe de filtration', 'room_id' => $pool->id]);
        $this->workOrderIn($pool, 'Eau trouble');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('rooms.show', $pool))
            ->assertOk()
            ->assertSee('Pompe de filtration')
            ->assertSee('Eau trouble');
    }

    // ===== Gestion des équipements =====

    public function test_admin_can_create_equipment_in_a_common_area(): void
    {
        $kitchen = Room::factory()->commonArea('CUIS', 'Cuisine')->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('equipment.store'), [
            'name' => 'Four mixte',
            'type' => 'Four',
            'room_id' => $kitchen->id,
            'status' => 'operationnel',
        ])->assertSessionHasNoErrors();

        $this->assertSame($kitchen->id, Equipment::where('name', 'Four mixte')->value('room_id'));
    }

    public function test_destroy_puts_equipment_out_of_service(): void
    {
        $boiler = Equipment::create(['name' => 'Chaudière']);

        $this->actingAs(User::factory()->admin()->create())->delete(route('equipment.destroy', $boiler));

        $this->assertSame('hors_service', $boiler->fresh()->status);
        $this->assertTrue(ActivityLog::where('action', 'equipment.updated')->exists());
    }

    // ===== Formulaire d'OT =====

    public function test_work_order_form_groups_places_and_hides_out_of_service_ones(): void
    {
        Room::factory()->create(['number' => '101']);
        Room::factory()->commonArea('PISC', 'Piscine')->create();
        Room::factory()->create(['number' => '999', 'status' => 'hors_service']);
        Equipment::create(['name' => 'Vieille chaudière', 'status' => 'hors_service']);

        $this->actingAs(User::factory()->housekeeping()->create())
            ->get(route('work-orders.create'))
            ->assertOk()
            ->assertSee('<optgroup label="Espaces communs">', false)
            ->assertSee('Chambre 101')
            ->assertSee('Piscine')
            ->assertDontSee('Chambre 999')
            ->assertDontSee('Vieille chaudière');
    }

    public function test_a_work_order_can_target_a_common_area(): void
    {
        $pool = Room::factory()->commonArea()->create();

        $this->actingAs(User::factory()->housekeeping()->create())->post(route('work-orders.store'), [
            'title' => 'Fuite au bord du bassin',
            'room_id' => $pool->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
        ])->assertSessionHasNoErrors();

        $workOrder = WorkOrder::where('title', 'Fuite au bord du bassin')->firstOrFail();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('work-orders.show', $workOrder))
            ->assertSee('Piscine')
            ->assertDontSee('Chambre PISC');
    }

    private function workOrderIn(Room $room, string $title = 'Panne'): WorkOrder
    {
        return WorkOrder::create([
            'title' => $title,
            'room_id' => $room->id,
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
        ]);
    }
}
