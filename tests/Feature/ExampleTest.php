<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root route: guests read the landing (the intro replaced the
     * login redirect), signed-in users bounce to their dashboard.
     */
    public function test_the_application_intro_shows_for_guests(): void
    {
        $this->get('/')->assertOk();
    }
}
