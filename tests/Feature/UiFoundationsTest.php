<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Socle commun de l'interface : bouton unique, action principale dans la zone du
 * pouce, retour logique, message de réussite et bandeau hors ligne, page active.
 */
class UiFoundationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_button_component_renders_the_shared_button(): void
    {
        $this->assertSame(
            '<button type="submit" class="btn btn-primary"> Enregistrer </button>',
            preg_replace('/\s+/', ' ', trim(Blade::render('<x-button>Enregistrer</x-button>')))
        );

        $link = Blade::render('<x-button href="/x" variant="secondary" size="sm" disabled>Voir</x-button>');
        $this->assertStringContainsString('class="btn btn-secondary btn-sm"', $link);
        $this->assertStringContainsString('aria-disabled="true"', $link);

        // Les anciens boutons Breeze ont le même rendu.
        $this->assertStringContainsString('class="btn btn-primary"', Blade::render('<x-primary-button>OK</x-primary-button>'));
        $this->assertStringContainsString('class="btn btn-danger-solid"', Blade::render('<x-danger-button>Supprimer</x-danger-button>'));
    }

    public function test_no_view_keeps_the_old_breeze_style(): void
    {
        // Ni bouton Breeze, ni palette Tailwind par défaut (gris, indigo…), ni mode sombre :
        // les vues n'utilisent que les couleurs nommées de tailwind.config.js.
        $pattern = '/bg-gray-800 text-white|\b(?:gray|slate|indigo|emerald|orange|red|green|blue|amber)-[1-9]00\b|\bdark:/';
        $old = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => preg_match($pattern, $file->getContents()))
            ->map(fn ($file) => $file->getRelativePathname())->values()->all();

        $this->assertSame([], $old);
    }

    public function test_every_role_gets_the_same_profile_screen(): void
    {
        foreach (['admin', 'manager', 'technicien', 'housekeeping', 'reception'] as $role) {
            $user = User::factory()->{$role}()->create();

            $this->actingAs($user)->get(route('profile.edit'))
                ->assertOk()
                ->assertSee('Mes informations')
                ->assertSee('Mot de passe')
                ->assertSee($user->role === UserRole::Housekeeping ? 'Housekeeping' : $user->role_label)
                ->assertDontSee('Renvoyer le lien de vérification');
        }
    }

    public function test_profile_offers_to_resend_the_verification_link(): void
    {
        $this->actingAs(User::factory()->admin()->unverified()->create())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Renvoyer le lien de vérification');
    }

    public function test_page_action_sits_in_the_thumb_zone_on_phones(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())->get(route('users.index'))->assertOk()->getContent();

        // Bas de l'écran, au-dessus de la barre de navigation, sans masquer la fin de la page.
        $this->assertMatchesRegularExpression('#<div data-thumb-bar class="tab:hidden[^"]*bottom-\[calc\(64px\+env\(safe-area-inset-bottom\)\)\]#', $html);
        $this->assertStringContainsString('pb-[calc(136px+env(safe-area-inset-bottom))]', $html);
        $this->assertStringContainsString('<nav data-bottom-nav class="tab:hidden', $html);
        $this->assertStringContainsString('id="offline-banner"', $html);
    }

    public function test_phones_and_tablets_reach_every_screen_from_the_menu(): void
    {
        // Feuille (layouts.sheet) : sidebar sur ordinateur ; sur téléphone et tablette, barre du
        // haut et menu ouvert depuis la gauche, avec tous les écrans de la sidebar.
        $html = $this->actingAs(User::factory()->admin()->create())->get(route('users.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<aside data-rc-sidebar class="hidden desk:flex', $html);

        preg_match('#<aside id="mobile-menu".*?</aside>#s', $html, $menu);
        $this->assertNotEmpty($menu);
        foreach (['Pièces &amp; stock', 'Fournisseurs', 'Équipements', 'Paramètres', 'Se déconnecter'] as $entry) {
            $this->assertStringContainsString($entry, $menu[0]);
        }
        $this->assertStringContainsString('class="desk:hidden fixed inset-0 z-50"', $html);
        $this->assertStringContainsString('absolute inset-y-0 left-0', $html);
        $this->assertSame(1, substr_count($html, '@click="menuOpen = true"'));
    }

    public function test_sideways_scroll_areas_have_no_scrollbar_on_touch_screens(): void
    {
        // Téléphone et tablette : on glisse au doigt, la barre horizontale est masquée ;
        // l'ordinateur la garde (seul moyen de défiler de côté à la souris).
        $css = File::get(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('#@media \(max-width: 1199\.98px\) \{\s*\.overflow-x-auto, \.overflow-x-scroll, \.overflow-auto \{ scrollbar-width: none; \}#', $css);
        $this->assertStringContainsString('.overflow-x-auto::-webkit-scrollbar', $css);
    }

    public function test_reception_can_hand_over_the_counter_from_the_tablet_menu(): void
    {
        // Coquille de la réception (layouts.sheet) : sur tablette et téléphone, le menu
        // ouvert depuis la barre du haut porte « Changer de réceptionniste ».
        $html = $this->actingAs(User::factory()->reception()->create())->get(route('work-orders.index'))->assertOk()->getContent();

        preg_match('#<aside id="mobile-menu".*?</aside>#s', $html, $menu);
        $this->assertStringContainsString('Changer de réceptionniste', $menu[0]);
        $this->assertSame(1, substr_count($html, '@click="menuOpen = true"'));
    }

    public function test_back_button_returns_to_the_previous_screen(): void
    {
        $agent = User::factory()->housekeeping()->create();

        $this->actingAs($agent)->get(route('quick-reports.create'))->assertOk()->assertSee('data-back', false);
    }

    public function test_active_page_is_announced_in_the_phone_bar(): void
    {
        $html = $this->actingAs(User::factory()->technicien()->create())->get(route('work-orders.index'))->getContent();

        // Barre du bas (nav data-bottom-nav) : l'entrée active est annoncée, pas seulement colorée.
        $bottomNav = str($html)->after('<nav data-bottom-nav')->before('</nav>')->toString();
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('work-orders.index'), '#').'"\s+aria-current="page"#', $bottomNav);
    }

    public function test_success_message_uses_the_shared_toast(): void
    {
        $html = $this->actingAs(User::factory()->manager()->create())
            ->withSession(['success' => 'Ordre créé.'])
            ->get(route('work-orders.index'))->getContent();

        $this->assertStringContainsString('x-ref="text" x-text="message">Ordre créé.</span>', $html);
    }

    public function test_no_emoji_in_the_interface_or_the_alerts(): void
    {
        // Pas d'emoji ni de symboles (✓, ✕, ⚠) : les icônes au trait de l'application
        // portent le sens, et les messages des notifications restent du texte simple.
        $pattern = '/[\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F000}-\x{1FAFF}\x{FE0F}]/u';
        $files = collect([app_path(), resource_path('views'), resource_path('js')])
            ->flatMap(fn (string $dir) => File::allFiles($dir))
            ->filter(fn ($file) => preg_match($pattern, $file->getContents()))
            ->map(fn ($file) => $file->getRelativePathname())->values()->all();

        $this->assertSame([], $files);
    }
}
