<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz de la aplicación sirve la landing pública a visitantes no autenticados.
     */
    public function test_the_application_serves_the_public_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
