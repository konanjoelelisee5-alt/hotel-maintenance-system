<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // La gestion des comptes redemande le mot de passe (middleware password.confirm) :
        // par défaut on le considère confirmé, pour tester ce qui vient après.
        // Les tests de la confirmation elle-même appellent forgetPasswordConfirmation().
        $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    protected function forgetPasswordConfirmation(): static
    {
        $this->app['session']->forget('auth.password_confirmed_at');

        return $this;
    }
}
