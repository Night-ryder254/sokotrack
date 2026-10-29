<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    // SokoTrack has no public home page: "/" sends visitors on to login or their dashboard.

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_signed_in_users_are_sent_to_their_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
