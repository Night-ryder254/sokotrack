<?php

namespace Tests\Feature\Auth;

use App\Mail\VendorWelcome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Public registration creates a vendor, so it needs business details and agreement to the terms.
     */
    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Vendor',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Test Vendor Ltd',
            'phone' => '254712345678',
            'agree_terms' => '1',
        ], $overrides);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_as_vendors(): void
    {
        Mail::fake();

        $response = $this->post('/register', $this->registrationData());

        // If the form later gains a required field, this line names it in the failure message.
        $response->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->first();

        // A vendor is an external customer: the user's role stays "none" and the Vendor row marks them as one.
        $this->assertSame('none', $user->role);
        $this->assertNotNull($user->vendor);
        $this->assertSame('Test Vendor Ltd', $user->vendor->business_name);

        Mail::assertSent(VendorWelcome::class);
    }

    public function test_registration_requires_agreeing_to_the_terms(): void
    {
        $this->post('/register', $this->registrationData(['agree_terms' => null]))
            ->assertSessionHasErrors('agree_terms');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
