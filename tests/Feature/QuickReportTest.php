<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuickReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_form_renders_with_prefilled_room(): void
    {
        Room::create(['number' => '214', 'floor' => '2']);

        $this->actingAs(User::factory()->housekeeping()->create())
            ->get(route('quick-reports.create', ['chambre' => '214']))
            ->assertOk()
            ->assertSee('Expliquez avec votre voix')
            ->assertSee('214');
    }

    public function test_voice_report_creates_work_order_with_audio_attachment(): void
    {
        $agent = User::factory()->housekeeping()->create();
        $room = Room::create(['number' => '214', 'floor' => '2']);

        $response = $this->actingAs($agent)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'room_occupancy' => 'libre',
            'category' => 'eau',
            'audio' => UploadedFile::fake()->create('message-vocal.webm', 120, 'audio/webm'),
            'photo' => UploadedFile::fake()->create('photo.jpg', 200, 'image/jpeg'),
        ]);

        $workOrder = WorkOrder::where('reported_by', $agent->id)->firstOrFail();
        $response->assertCreated()->assertJson(['redirect' => route('quick-reports.sent', $workOrder)]);

        $this->assertSame($room->id, $workOrder->room_id);
        $this->assertSame('Eau / fuite · Chambre 214 (message vocal)', $workOrder->title);
        $this->assertSame('moyenne', $workOrder->priority->code);
        $this->assertSame('maintenance', $workOrder->type->code);
        $this->assertSame('ouvert', $workOrder->status);
        $this->assertNull($workOrder->assigned_to);
        $this->assertSame(1, $workOrder->statusHistories()->count());

        $audio = $workOrder->attachments()->where('mime_type', 'like', 'audio/%')->firstOrFail();
        Storage::disk('public')->assertExists($audio->file_path);
        $this->assertSame(2, $workOrder->attachments()->count());
    }

    public function test_urgent_report_uses_urgent_priority(): void
    {
        Room::create(['number' => '101', 'floor' => '1']);
        $agent = User::factory()->housekeeping()->create();

        $this->actingAs($agent)->postJson(route('quick-reports.store'), [
            'room_number' => '101', 'category' => 'electricite', 'urgent' => 1, 'room_occupancy' => 'libre',
        ])->assertCreated();

        $this->assertSame('urgente', WorkOrder::where('reported_by', $agent->id)->firstOrFail()->priority->code);
    }

    public function test_common_area_report_needs_no_room(): void
    {
        $agent = User::factory()->housekeeping()->create();

        $this->actingAs($agent)->postJson(route('quick-reports.store'), [
            'common_area' => 1, 'category' => 'autre', 'note' => 'Ampoule grillée couloir 3e',
        ])->assertCreated();

        $workOrder = WorkOrder::where('reported_by', $agent->id)->firstOrFail();
        $this->assertNull($workOrder->room_id);
        $this->assertSame('Autre · Parties communes', $workOrder->title);
        $this->assertSame('Ampoule grillée couloir 3e', $workOrder->description);
    }

    public function test_report_in_a_listed_common_area(): void
    {
        $pool = Room::create(['type' => Room::TYPE_COMMON_AREA, 'number' => 'PISC', 'name' => 'Piscine']);
        $agent = User::factory()->housekeeping()->create();

        $this->actingAs($agent)->get(route('quick-reports.create'))->assertSee('Piscine');

        $this->actingAs($agent)->postJson(route('quick-reports.store'), [
            'common_area' => 1, 'common_area_id' => $pool->id, 'category' => 'eau',
        ])->assertCreated();

        $workOrder = WorkOrder::where('reported_by', $agent->id)->firstOrFail();
        $this->assertSame($pool->id, $workOrder->room_id);
        $this->assertSame('Eau / fuite · Piscine', $workOrder->title);
    }

    public function test_out_of_service_rooms_and_common_area_codes_are_not_valid_room_numbers(): void
    {
        Room::create(['number' => '301', 'floor' => '3', 'status' => 'hors_service']);
        Room::create(['type' => Room::TYPE_COMMON_AREA, 'number' => 'HALL', 'name' => 'Hall']);
        $agent = User::factory()->housekeeping()->create();

        foreach (['301', 'HALL'] as $number) {
            $this->actingAs($agent)
                ->postJson(route('quick-reports.store'), ['room_number' => $number, 'category' => 'tv'])
                ->assertJsonValidationErrors(['room_number']);
        }
    }

    public function test_room_and_category_are_required(): void
    {
        $this->actingAs(User::factory()->housekeeping()->create())
            ->postJson(route('quick-reports.store'), ['room_number' => '999'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category', 'room_number']);
    }

    public function test_non_audio_file_is_rejected_as_voice_message(): void
    {
        Room::create(['number' => '101', 'floor' => '1']);

        $this->actingAs(User::factory()->housekeeping()->create())
            ->postJson(route('quick-reports.store'), [
                'room_number' => '101', 'category' => 'tv', 'room_occupancy' => 'libre',
                'audio' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
            ])
            ->assertJsonValidationErrors(['audio']);
    }

    public function test_confirmation_page_is_limited_to_who_can_see_the_order(): void
    {
        Room::create(['number' => '101', 'floor' => '1']);
        $agent = User::factory()->housekeeping()->create();
        $this->actingAs($agent)->postJson(route('quick-reports.store'), ['room_number' => '101', 'category' => 'clim', 'room_occupancy' => 'libre']);
        $workOrder = WorkOrder::where('reported_by', $agent->id)->firstOrFail();

        $this->actingAs($agent)->get(route('quick-reports.sent', $workOrder))->assertOk()->assertSee('Chambre 101');
        $this->actingAs(User::factory()->housekeeping()->create())->get(route('quick-reports.sent', $workOrder))->assertForbidden();
    }

    public function test_work_order_page_plays_the_voice_message(): void
    {
        Room::create(['number' => '101', 'floor' => '1']);
        $agent = User::factory()->housekeeping()->create();
        $this->actingAs($agent)->postJson(route('quick-reports.store'), [
            'room_number' => '101', 'category' => 'eau', 'room_occupancy' => 'libre',
            'audio' => UploadedFile::fake()->create('message-vocal.webm', 50, 'audio/webm'),
        ]);
        $workOrder = WorkOrder::where('reported_by', $agent->id)->firstOrFail();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('<audio', false);
    }
}
