<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Côté serveur des fenêtres (resources/js/modal.js) : la page répond avec son seul
 * contenu, et la fin d'un formulaire renvoie l'adresse où aller au lieu de rediriger.
 */
class ModalWindowTest extends TestCase
{
    use RefreshDatabase;

    private const MODAL = ['X-Modal' => '1'];

    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Climatisation en panne',
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            ...$overrides,
        ];
    }

    public function test_create_button_opens_in_a_window(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertSee('href="'.route('work-orders.create').'" data-modal', false)
            ->assertSee('id="remote-modal"', false);
    }

    public function test_create_page_answers_the_window_with_the_form_only(): void
    {
        $html = $this->actingAs(User::factory()->manager()->create())
            ->get(route('work-orders.create'), self::MODAL)
            ->assertOk()
            ->assertSee('Nouvel ordre de travail')
            ->assertSee('action="'.route('work-orders.store').'"', false)
            ->assertSee('data-modal-close', false)
            ->getContent();

        $this->assertStringNotContainsString('<html', $html);
    }

    public function test_create_page_without_window_keeps_the_full_layout(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('work-orders.create'))
            ->assertOk()
            ->assertSee('<html', false);
    }

    public function test_successful_creation_from_the_window_points_to_the_new_work_order(): void
    {
        $response = $this->actingAs(User::factory()->manager()->create())
            ->post(route('work-orders.store'), $this->payload(), self::MODAL + ['Accept' => 'application/json']);

        $workOrder = WorkOrder::where('title', 'Climatisation en panne')->firstOrFail();
        $response->assertOk()->assertExactJson(['redirect' => route('work-orders.show', $workOrder)]);

        // Le message « succès » attend la page de l'OT, il n'a pas été consommé en route.
        $this->get(route('work-orders.show', $workOrder))->assertSee('Ordre de travail créé avec succès.');
    }

    public function test_invalid_form_in_the_window_returns_field_errors(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->post(route('work-orders.store'), $this->payload(['title' => '']), self::MODAL + ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');

        $this->assertSame(0, WorkOrder::count());
    }

    public function test_normal_form_submission_still_redirects(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->post(route('work-orders.store'), $this->payload())
            ->assertRedirect();
    }
}
