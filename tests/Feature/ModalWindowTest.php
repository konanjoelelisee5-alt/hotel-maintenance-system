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

    // ===== Ajouts rapides et utilisateurs =====

    public static function quickAddPages(): array
    {
        return [
            'lieu' => ['rooms.index', 'rooms.create', 'Nouveau lieu'],
            'équipement' => ['equipment.index', 'equipment.create', 'Nouvel équipement'],
            'fournisseur' => ['suppliers.index', 'suppliers.create', 'Nouveau fournisseur'],
            'pièce' => ['parts.index', 'parts.create', 'Nouvelle pièce'],
            'compétence' => ['skills.index', 'skills.create', 'Nouvelle compétence'],
            "type d'OT" => ['work-order-types.index', 'work-order-types.create', "Nouveau type d'OT"],
            'priorité' => ['work-order-priorities.index', 'work-order-priorities.create', 'Nouvelle priorité'],
            'utilisateur' => ['users.index', 'users.create', 'Nouvel utilisateur'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('quickAddPages')]
    public function test_quick_add_opens_in_a_window_and_keeps_its_full_page(string $index, string $create, string $title): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route($index))
            ->assertSee('href="'.route($create).'" data-modal', false);

        $fragment = $this->actingAs($admin)->get(route($create), self::MODAL)
            ->assertOk()
            ->assertSee($title)
            ->assertSee('data-modal-close', false)
            ->getContent();
        $this->assertStringNotContainsString('<html', $fragment);

        $this->actingAs($admin)->get(route($create))->assertOk()->assertSee('<html', false)->assertSee($title);
    }

    public function test_room_created_from_the_window_leads_to_its_page(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->post(route('rooms.store'), ['type' => 'chambre', 'number' => '512', 'floor' => '5', 'status' => 'disponible'], self::MODAL + ['Accept' => 'application/json']);

        $room = \App\Models\Room::where('number', '512')->firstOrFail();
        $response->assertOk()->assertExactJson(['redirect' => route('rooms.show', $room)]);
    }

    public function test_user_edit_opens_in_a_window(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->technicien()->create(['name' => 'Yao Konan']);

        $this->actingAs($admin)->get(route('users.index'))
            ->assertSee('href="'.route('users.edit', $user).'" data-modal', false);

        $fragment = $this->actingAs($admin)->get(route('users.edit', $user), self::MODAL)
            ->assertOk()
            ->assertSee('Modifier Yao Konan')
            ->assertSee('Réinitialiser le mot de passe')
            ->getContent();
        $this->assertStringNotContainsString('<html', $fragment);
        $this->assertStringNotContainsString('shadow-sm rounded-lg', $fragment);
    }

    public function test_account_window_still_asks_for_the_password_first(): void
    {
        $admin = User::factory()->admin()->create();
        $this->forgetPasswordConfirmation();

        // La fenêtre reçoit l'adresse de la page de confirmation et s'y rend.
        $this->actingAs($admin)->get(route('users.create'), self::MODAL)
            ->assertOk()
            ->assertExactJson(['redirect' => route('password.confirm')]);
    }

    public function test_normal_form_submission_still_redirects(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->post(route('work-orders.store'), $this->payload())
            ->assertRedirect();
    }
}
