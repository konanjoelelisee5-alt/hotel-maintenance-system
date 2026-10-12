<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Application installable (PWA) : manifeste valide, icônes présentes, et chaque page
 * (connexion comprise) déclare le manifeste et le service worker.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_valid_and_its_icons_exist(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['short_name']);
        $purposes = array_column($manifest['icons'], 'purpose');
        $this->assertContains('maskable', $purposes);
        $this->assertContains('any', $purposes);
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path($icon['src']));
            [$width, $height] = getimagesize(public_path($icon['src']));
            $this->assertSame($icon['sizes'], "{$width}x{$height}");
        }
        $this->assertFileExists(public_path('images/pwa/apple-touch-icon.png'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('sw.js'));
    }

    public function test_login_and_app_pages_declare_the_manifest_and_service_worker(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('manifest.json', false)
            ->assertSee('sw.js', false);

        $this->actingAs(User::factory()->technicien()->create())
            ->get(route('technicien.dashboard'))->assertOk()
            ->assertSee('manifest.json', false)
            ->assertSee('apple-touch-icon.png', false)
            ->assertSee("Installer l'application", false);
    }
}
