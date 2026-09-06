<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Les tests Feature qui touchent une page Inertia passent par
        // resources/views/app.blade.php, qui appelle @vite() : sans ça,
        // Laravel cherche public/build/manifest.json, qui n'existe que
        // si `npm run build` a tourné avant (jamais le cas en CI, où le
        // job "backend" ne construit pas les assets front). withoutVite()
        // remplace le rendu de @vite() par une chaîne vide pour les tests.
        $this->withoutVite();
    }
}
