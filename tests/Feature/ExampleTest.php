<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The home page sends the visitor to the conversation list.
     */
    public function test_the_home_page_redirects_to_conversations(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/conversations');
    }
}
