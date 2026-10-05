<?php

namespace Tests\Feature;

use App\Models\InterventionReport;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\HousekeepingReportNotification;
use App\Support\OnCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Suivi des signalements Housekeeping : retirer une erreur, ajouter une précision,
 * doublons, historique, pannes récurrentes, réparation visible du demandeur.
 */
class HousekeepingFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Room::create(['number' => '214', 'floor' => '2']);
        $this->agent = User::factory()->housekeeping()->create();
    }

    private function report(?User $agent = null, string $category = 'clim', string $room = '214'): WorkOrder
    {
        $agent ??= $this->agent;
        $this->actingAs($agent)->postJson(route('quick-reports.store'), [
            'room_number' => $room, 'room_occupancy' => 'libre', 'category' => $category,
        ])->assertCreated();

        return WorkOrder::where('reported_by', $agent->id)->latest('id')->firstOrFail();
    }

    // ===== Retirer un signalement =====

    public function test_author_withdraws_a_fresh_report_which_is_cancelled_not_deleted(): void
    {
        $workOrder = $this->report();

        $this->actingAs($this->agent)->post(route('quick-reports.withdraw', $workOrder), ['reason' => 'lieu', 'detail' => 'C\'était la 215'])
            ->assertRedirect(route('work-orders.show', $workOrder));

        $workOrder->refresh();
        $this->assertSame('annule', $workOrder->status);
        $this->assertDatabaseHas('work_orders', ['id' => $workOrder->id]);
        $this->assertSame(
            'Retiré par le demandeur : Erreur de chambre ou de lieu — C\'était la 215',
            $workOrder->statusHistories()->latest('id')->value('note'),
        );
    }

    public function test_withdrawal_needs_a_reason(): void
    {
        $workOrder = $this->report();

        $this->actingAs($this->agent)->post(route('quick-reports.withdraw', $workOrder), [])->assertSessionHasErrors('reason');
        $this->assertSame('ouvert', $workOrder->fresh()->status);
    }

    public function test_withdrawal_is_closed_after_the_window_once_assigned_or_for_someone_else(): void
    {
        $late = $this->report();
        $late->forceFill(['created_at' => now()->subMinutes(16)])->save();
        $this->actingAs($this->agent)->post(route('quick-reports.withdraw', $late), ['reason' => 'erreur'])->assertForbidden();

        $assigned = $this->report();
        $assigned->update(['assigned_to' => User::factory()->technicien()->create()->id]);
        $this->actingAs($this->agent)->post(route('quick-reports.withdraw', $assigned), ['reason' => 'erreur'])->assertForbidden();

        $other = $this->report(User::factory()->housekeeping()->create());
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);
        $this->actingAs($head)->post(route('quick-reports.withdraw', $other), ['reason' => 'erreur'])->assertForbidden();
    }

    // ===== Ajouter une précision =====

    public function test_requester_adds_a_note_voice_and_photo_without_changing_the_report(): void
    {
        $workOrder = $this->report();

        $this->actingAs($this->agent)->postJson(route('quick-reports.complement', $workOrder), [
            'note' => 'La fuite a empiré.',
            'audio' => UploadedFile::fake()->create('precision.webm', 80, 'audio/webm'),
            'photo' => UploadedFile::fake()->create('photo.jpg', 120, 'image/jpeg'),
        ])->assertCreated()->assertJson(['redirect' => route('work-orders.show', $workOrder)]);

        $this->assertSame('Précision du demandeur : La fuite a empiré.', $workOrder->comments()->value('content'));
        $this->assertSame(2, $workOrder->attachments()->where('uploaded_by', $this->agent->id)->count());
        $this->assertSame('ouvert', $workOrder->fresh()->status);

        $this->actingAs($this->agent)->get(route('work-orders.show', $workOrder))
            ->assertOk()->assertSee('Signalement et précisions')->assertSee('La fuite a empiré.');
    }

    public function test_a_precision_needs_content_and_an_open_report(): void
    {
        $workOrder = $this->report();
        $this->actingAs($this->agent)->postJson(route('quick-reports.complement', $workOrder), [])->assertUnprocessable();

        $workOrder->update(['status' => 'resolu', 'completed_at' => now()]);
        $this->actingAs($this->agent)->postJson(route('quick-reports.complement', $workOrder), ['note' => 'Encore'])->assertForbidden();

        // Une autre agente ne voit pas ce signalement : elle ne peut pas le compléter.
        $mine = $this->report();
        $this->actingAs(User::factory()->housekeeping()->create())
            ->postJson(route('quick-reports.complement', $mine), ['note' => 'x'])->assertForbidden();
    }

    // ===== Doublons =====

    public function test_report_form_knows_what_is_already_open_at_each_place(): void
    {
        $existing = $this->report(User::factory()->housekeeping()->create());

        $openReports = $this->actingAs($this->agent)->get(route('quick-reports.create'))->assertOk()->viewData('openReports');

        $this->assertCount(1, $openReports);
        $this->assertSame('214', $openReports[0]['place']);
        $this->assertSame('clim', $openReports[0]['category']);
        $this->assertSame($existing->code(), $openReports[0]['code']);
        // Signalement d'une collègue : pas de lien vers une fiche qu'elle ne peut pas ouvrir.
        $this->assertNull($openReports[0]['url']);
    }

    // ===== Historique =====

    public function test_history_lists_finished_reports_grouped_by_day(): void
    {
        $open = $this->report();
        $done = $this->report(category: 'eau');
        $done->update(['status' => 'resolu', 'completed_at' => now()]);

        $this->actingAs($this->agent)->get(route('work-orders.index'))
            ->assertSee($open->code())->assertDontSee($done->code());

        $this->actingAs($this->agent)->get(route('work-orders.index', ['vue' => 'historique']))
            ->assertSee($done->code())->assertDontSee($open->code())->assertSee("Aujourd'hui");
    }

    // ===== Pannes récurrentes et réparation visible =====

    public function test_head_sees_repeated_failures_and_the_report_shows_the_repeat(): void
    {
        $this->report();
        $second = $this->report();
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);

        $this->actingAs($head)->get(route('housekeeping.dashboard'))->assertSee('Pannes récurrentes')->assertSee('2 fois');
        $this->actingAs($this->agent)->get(route('work-orders.show', $second))->assertSee('Panne récurrente');
    }

    // ===== Qui est prévenu =====

    public function test_withdrawing_an_urgent_report_warns_on_call_and_reception(): void
    {
        $this->actingAs($this->agent)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'room_occupancy' => 'client_present', 'category' => 'eau', 'urgent' => 1,
        ])->assertCreated();
        $workOrder = WorkOrder::where('reported_by', $this->agent->id)->firstOrFail();
        $this->assertNotNull($workOrder->reception_alerted_at);
        $manager = User::factory()->manager()->create(['receives_maintenance_alerts' => true, 'phone' => '+2250700000000']);
        $reception = User::factory()->reception()->create();

        Notification::fake();
        $this->travelTo(now()->setTime(10, 0));
        $workOrder->forceFill(['created_at' => now()])->save();
        $this->actingAs($this->agent)->post(route('quick-reports.withdraw', $workOrder), ['reason' => 'regle']);

        $withdrawn = fn ($n) => $n instanceof HousekeepingReportNotification && $n->toArray($this->agent)['step'] === HousekeepingReportNotification::WITHDRAWN;
        Notification::assertSentTo($reception, HousekeepingReportNotification::class, $withdrawn);
        $this->assertContains($manager->id, OnCall::recipients()->pluck('id')->all());
        Notification::assertSentTo($manager, HousekeepingReportNotification::class, $withdrawn);
    }

    public function test_a_precision_warns_the_assigned_technician(): void
    {
        $workOrder = $this->report();
        $technician = User::factory()->technicien()->create();
        $workOrder->update(['assigned_to' => $technician->id, 'status' => 'en_cours']);

        Notification::fake();
        $this->actingAs($this->agent)->postJson(route('quick-reports.complement', $workOrder), ['note' => 'Ça goutte encore.'])->assertCreated();

        Notification::assertSentTo($technician, HousekeepingReportNotification::class,
            fn ($n) => str_contains($n->toArray($technician)['message'], 'Ça goutte encore.'));
    }

    public function test_the_governess_is_told_of_her_team_urgent_reports(): void
    {
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);

        Notification::fake();
        $this->actingAs($this->agent)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'room_occupancy' => 'libre', 'category' => 'electricite', 'urgent' => 1,
        ])->assertCreated();
        $this->actingAs($this->agent)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'room_occupancy' => 'libre', 'category' => 'tv',
        ])->assertCreated();

        Notification::assertSentToTimes($head, HousekeepingReportNotification::class, 1);
    }

    // ===== Finitions =====

    public function test_late_reports_carry_a_badge(): void
    {
        $workOrder = $this->report();
        $workOrder->forceFill(['sla_resolution_due_at' => now()->subHour()])->save();

        $this->actingAs($this->agent)->get(route('work-orders.index'))->assertSee('En retard');
    }

    public function test_the_application_speaks_french(): void
    {
        $this->actingAs($this->agent)->get(route('profile.edit'))
            ->assertOk()->assertSee('Changer le mot de passe')->assertDontSee('Update Password');

        $this->actingAs($this->agent)->put(route('password.update'), [])
            ->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'Le champ mot de passe actuel est obligatoire.']);

        $this->actingAs($this->agent)->get('/cette-page-n-existe-pas')->assertNotFound()->assertSee('Page introuvable');
    }

    public function test_requester_sees_what_the_technician_did(): void
    {
        $workOrder = $this->report();
        $technician = User::factory()->technicien()->create();
        $workOrder->update(['assigned_to' => $technician->id, 'status' => 'resolu', 'completed_at' => now()]);
        InterventionReport::create(['work_order_id' => $workOrder->id, 'technician_id' => $technician->id, 'work_performed' => 'Remplacement du condensateur.']);

        $this->actingAs($this->agent)->get(route('work-orders.show', $workOrder))
            ->assertSee('Réparation effectuée')->assertSee('Remplacement du condensateur.');
    }
}
