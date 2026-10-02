<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Actions dangereuses : jamais au même niveau que l'action courante, toujours
 * confirmées par la fenêtre de l'application (plus de confirm() du navigateur).
 */
class DangerActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_view_uses_the_browser_confirm_any_more(): void
    {
        $native = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_contains($file->getContents(), 'return confirm('))
            ->map(fn ($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $native);
    }

    public function test_putting_a_room_out_of_service_sits_in_the_more_menu_and_is_confirmed(): void
    {
        $room = Room::factory()->create();

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('rooms.show', $room))->assertOk()->getContent();

        $menu = substr($html, strpos($html, 'aria-haspopup="menu"'));
        $this->assertStringContainsString('action="'.route('rooms.destroy', $room).'"', $menu);
        $this->assertStringContainsString('data-confirm-title="Mettre ce lieu hors service ?"', $menu);
        $this->assertStringContainsString('data-confirm-tone="danger"', $menu);
    }
}
