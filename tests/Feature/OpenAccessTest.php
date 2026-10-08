<?php

namespace Tests\Feature;

use App\Support\OpenAccess;
use Tests\TestCase;

/**
 * Accès ouvert (« Voir en tant que ») : sur un poste de développement ou un site de
 * démonstration seulement, jamais sur le vrai site de l'hôtel.
 */
class OpenAccessTest extends TestCase
{
    private function enabledWith(string $env, bool $flag, bool $demo): bool
    {
        $this->app['env'] = $env;
        config(['app.open_access' => $flag, 'app.demo' => $demo]);

        return OpenAccess::enabled();
    }

    public function test_off_unless_switched_on(): void
    {
        $this->assertFalse($this->enabledWith('local', false, false));
        $this->assertTrue($this->enabledWith('local', true, false));
    }

    public function test_demo_site_in_production_mode_keeps_it(): void
    {
        $this->assertTrue($this->enabledWith('production', true, true));
    }

    public function test_real_production_never_has_it(): void
    {
        $this->assertFalse($this->enabledWith('production', true, false));
    }
}
