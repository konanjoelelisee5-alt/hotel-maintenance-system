<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\AppVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Version de l'application et page « Nouveautés » (resources/changelog.php) : journal
 * bien formé, affichage dans les menus et à la connexion, filtre par rôle, point bleu.
 */
class AppVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_changelog_is_well_formed_and_newest_first(): void
    {
        $releases = AppVersion::releases();
        $roles = array_map(fn (UserRole $r) => $r->value, UserRole::cases());
        $this->assertNotEmpty($releases);
        $this->assertSame($releases[0]['version'], AppVersion::current());

        foreach ($releases as $i => $release) {
            $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-(beta|rc)\.\d+)?$/', $release['version']);
            $this->assertSame($release['date'], Carbon::createFromFormat('Y-m-d', $release['date'])->format('Y-m-d'));
            $this->assertNotEmpty($release['changes'], "Version {$release['version']} sans changement.");
            if ($i > 0) {
                $this->assertTrue(version_compare($releases[$i - 1]['version'], $release['version'], '>'), 'Versions dans le désordre ou en double.');
                $this->assertGreaterThanOrEqual($release['date'], $releases[$i - 1]['date']);
            }
            foreach ($release['changes'] as $change) {
                $this->assertArrayHasKey($change['type'], AppVersion::TYPES);
                $this->assertNotEmpty($change['text']);
                $this->assertTrue($change['roles'] === null || array_diff($change['roles'], $roles) === [], 'Rôle inconnu dans le journal.');
            }
        }
    }

    public function test_version_is_shown_in_menus_and_on_the_login_page(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Version '.AppVersion::current());

        $this->actingAs(User::factory()->technicien()->create())
            ->get(route('technicien.dashboard'))->assertOk()
            ->assertSee('v'.AppVersion::current())
            ->assertSee('Nouveautés')
            ->assertSee('<meta name="app-version" content="'.AppVersion::current().'">', false);
    }

    public function test_release_notes_are_filtered_by_role_and_the_dot_goes_off_once_read(): void
    {
        $technician = User::factory()->technicien()->create();
        $this->assertTrue(AppVersion::hasUnseen($technician));
        $this->actingAs($technician)->get(route('technicien.dashboard'))->assertSee('(non lues)');

        $technicianOnly = collect(AppVersion::releasesFor($technician))->flatMap(fn ($r) => $r['changes'])->first(fn ($c) => $c['roles'] === ['technicien']);
        $response = $this->actingAs($technician)->get(route('release-notes.index'))->assertOk()
            ->assertSee('Nouveau pour vous');
        if ($technicianOnly) {
            $response->assertSee($technicianOnly['text']);
            $this->actingAs(User::factory()->housekeeping()->create())->get(route('release-notes.index'))->assertOk()
                ->assertDontSee($technicianOnly['text']);
        }

        $this->assertSame(AppVersion::current(), $technician->fresh()->seen_version);
        $this->assertFalse(AppVersion::hasUnseen($technician->fresh()));
        $this->actingAs($technician->fresh())->get(route('technicien.dashboard'))->assertDontSee('(non lues)');
    }

    public function test_version_endpoint_returns_the_current_version(): void
    {
        $this->getJson(route('app.version'))->assertOk()->assertExactJson(['version' => AppVersion::current()]);
    }

    public function test_pre_release_versions_compare_in_the_right_order(): void
    {
        $this->assertTrue(version_compare('0.9.0-beta.2', '0.9.0-beta.1', '>'));
        $this->assertTrue(version_compare('1.0.0-rc.1', '0.9.0-beta.9', '>'));
        $this->assertTrue(version_compare('1.0.0', '1.0.0-rc.2', '>'));
        $this->assertTrue(AppVersion::isPreRelease('0.9.0-beta.1'));
        $this->assertFalse(AppVersion::isPreRelease('1.0.0'));

        // Vu une version plus récente que la dernière qui le concerne : pas de point bleu.
        $user = User::factory()->manager()->make();
        $user->seen_version = '99.0.0';
        $this->assertFalse(AppVersion::hasUnseen($user));
    }
}
