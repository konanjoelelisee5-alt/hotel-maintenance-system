<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * En-tête commun : chaque écran a un titre et son action principale aussi sur
 * téléphone. L'ancien en-tête Breeze (<x-slot name="header">) n'existait que sur
 * ordinateur — titre vide et bouton invisible sur mobile.
 */
class PageHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_view_uses_the_desktop_only_header_slot_any_more(): void
    {
        $legacy = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => preg_match('/<x-slot name="header">|<x-slot:header>/', $file->getContents()))
            ->map(fn ($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $legacy);
    }

    public function test_title_and_main_action_are_rendered_for_the_phone_bar_too(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('users.index'))
            ->assertOk()
            ->getContent();

        // Barre mobile + en-tête bureau : le titre et l'action apparaissent chacun deux fois.
        $this->assertStringContainsString('<div class="text-[16px] font-semibold truncate">Utilisateurs</div>', $html);
        $this->assertSame(2, substr_count($html, '+ Nouvel utilisateur'));
    }

    public function test_success_message_is_shown_once(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->withSession(['success' => 'Utilisateur créé.'])
            ->get(route('users.index'))->getContent();
        $this->assertSame(1, substr_count($html, 'Utilisateur créé.'));
    }
}
