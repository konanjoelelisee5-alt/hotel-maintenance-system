<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomInspection;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\RoomInspectionChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Outils de la gouvernante : plan des étages, bilan du mois, tournée d'inspection.
 */
class HousekeepingSupervisionTest extends TestCase
{
    use RefreshDatabase;

    private User $head;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Room::create(['number' => '214', 'floor' => 'Étage 2']);
        Room::create(['number' => '215', 'floor' => 'Étage 2']);
        $this->head = User::factory()->housekeeping()->create(['is_department_head' => true]);
        $this->agent = User::factory()->housekeeping()->create();
    }

    private function report(string $category = 'clim', bool $urgent = false): WorkOrder
    {
        $this->actingAs($this->agent)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'room_occupancy' => 'libre', 'category' => $category, 'urgent' => $urgent ? 1 : 0,
        ])->assertCreated();

        return WorkOrder::latest('id')->firstOrFail();
    }

    public function test_tools_are_reserved_to_the_governess(): void
    {
        foreach (['housekeeping.floor-plan', 'housekeeping.monthly-report', 'inspections.index'] as $route) {
            $this->actingAs($this->agent)->get(route($route))->assertForbidden();
            $this->actingAs($this->head)->get(route($route))->assertOk();
        }
        $this->actingAs(User::factory()->reception()->create(['is_department_head' => true]))->get(route('housekeeping.floor-plan'))->assertForbidden();
    }

    public function test_floor_plan_shows_rooms_by_floor_with_their_reports(): void
    {
        $workOrder = $this->report(urgent: true);

        $this->actingAs($this->head)->get(route('housekeeping.floor-plan'))
            ->assertOk()->assertSee('Étage 2')->assertSee('214')->assertSee('215')
            ->assertSee('Panne urgente ou en retard')->assertSee($workOrder->code());
    }

    public function test_monthly_report_counts_the_team_reports(): void
    {
        $this->report();
        $done = $this->report('eau');
        $done->update(['status' => 'resolu', 'completed_at' => now()->addHours(3)]);

        $response = $this->actingAs($this->head)->get(route('housekeeping.monthly-report'))->assertOk();
        $stats = $response->viewData('stats');
        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['repaired']);
        $this->assertEquals(3.0, $stats['avgHours']);
        $response->assertSee('Climatisation')->assertSee('Chambre 214');
    }

    public function test_inspection_creates_one_work_order_per_category_and_completes_open_ones(): void
    {
        $openClim = $this->report('clim');

        $this->actingAs($this->head)->post(route('inspections.store'), ['room_number' => '214']);
        $inspection = RoomInspection::firstOrFail();
        $this->assertCount(count(RoomInspectionChecklist::POINTS), $inspection->items);

        // Starting again on the same room resumes the inspection in progress.
        $this->actingAs($this->head)->post(route('inspections.store'), ['room_number' => '214'])
            ->assertRedirect(route('inspections.show', $inspection));
        $this->assertSame(1, RoomInspection::count());

        foreach ($inspection->items as $item) {
            $result = in_array($item->point_key, ['sdb_lavabo', 'sdb_douche', 'chambre_clim'], true) ? 'nok' : 'ok';
            $this->actingAs($this->head)->patchJson(route('inspections.points.update', [$inspection, $item]), [
                'result' => $result, 'comment' => $item->point_key === 'sdb_lavabo' ? 'Fuit au pied' : null,
            ])->assertOk();
        }
        $lavabo = $inspection->items->firstWhere('point_key', 'sdb_lavabo');
        $this->actingAs($this->head)->post(route('inspections.points.photo', [$inspection, $lavabo]), [
            'photo' => UploadedFile::fake()->create('fuite.jpg', 120, 'image/jpeg'),
        ])->assertOk();

        $this->actingAs($this->head)->post(route('inspections.complete', $inspection), ['notes' => 'RAS ailleurs'])
            ->assertRedirect(route('inspections.show', $inspection))
            ->assertSessionHas('success', 'Inspection terminée : 1 OT créé(s), 1 OT existant(s) complété(s).');

        $inspection->refresh();
        $this->assertTrue($inspection->isDone());

        // Eau : lavabo + douche dans un seul OT, avec la photo.
        $eau = WorkOrder::where('title', 'like', 'Eau / fuite · %')->firstOrFail();
        $this->assertStringContainsString('Robinet du lavabo : Fuit au pied', $eau->description);
        $this->assertStringContainsString('Douche ou baignoire', $eau->description);
        $this->assertSame($this->head->id, $eau->reported_by);
        $this->assertSame(1, $eau->attachments()->count());

        // Clim : déjà signalée → constat ajouté à l'OT ouvert, pas de doublon.
        $this->assertSame(1, WorkOrder::where('title', 'like', 'Climatisation · %')->count());
        $this->assertStringContainsString('Climatisation', $openClim->comments()->value('content'));
        $this->assertSame($openClim->id, $inspection->items->firstWhere('point_key', 'chambre_clim')->work_order_id);

        // Une inspection terminée ne se modifie plus.
        $this->actingAs($this->head)->patchJson(route('inspections.points.update', [$inspection, $lavabo]), ['result' => 'ok'])->assertForbidden();
        $this->actingAs($this->head)->get(route('inspections.show', $inspection))->assertOk()->assertSee('Non conformités')->assertSee($eau->code());
    }

    public function test_an_inspection_cannot_be_finished_half_done_and_can_be_abandoned(): void
    {
        $this->actingAs($this->head)->post(route('inspections.store'), ['room_number' => '215']);
        $inspection = RoomInspection::firstOrFail();

        $this->actingAs($this->head)->post(route('inspections.complete', $inspection))->assertStatus(422);

        $this->actingAs($this->head)->delete(route('inspections.destroy', $inspection))->assertRedirect(route('inspections.index'));
        $this->assertSame(0, RoomInspection::count());
        $this->assertSame(0, WorkOrder::count());
    }
}
