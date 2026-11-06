<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // I test svuotano il database: non devono mai girare su quello reale
        $database = config('database.connections.'.config('database.default').'.database');
        if (! str_ends_with((string) $database, '_test')) {
            $this->fail("I test devono usare un database che finisce con _test, non '$database'.");
        }

        // Gli asset compilati con Vite non servono nei test
        $this->withoutVite();
    }
}
