<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_the_sign_in_form(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Show password')
            ->assertSee('Create an account');
    }

    public function test_login_rejects_missing_credentials_with_required_messages(): void
    {
        $response = $this->from(route('login'))
            ->post(route('login.submit'));

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);
    }

    public function test_login_rejects_an_invalid_email_address(): void
    {
        $response = $this->from(route('login'))
            ->post(route('login.submit'), [
                'email' => 'not-an-email',
                'password' => 'password',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'The email field must be a valid email address.',
            ]);
    }

    public function test_valid_login_input_redirects_to_the_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'alex@example.com',
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_a_valid_email_with_an_incorrect_password(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))
            ->post(route('login.submit'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_creates_and_authenticates_a_user(): void
    {
        $response = $this->post(route('register.submit'), [
            'name' => 'Taylor Reed',
            'email' => 'taylor@example.com',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs(User::query()->where('email', 'taylor@example.com')->firstOrFail());
        $this->assertDatabaseHas('users', [
            'name' => 'Taylor Reed',
            'email' => 'taylor@example.com',
        ]);
        $this->assertDatabaseHas('projects', [
            'owner_id' => User::query()->where('email', 'taylor@example.com')->value('id'),
            'name' => 'Personal',
        ]);
        $this->assertDatabaseHas('projects', [
            'owner_id' => User::query()->where('email', 'taylor@example.com')->value('id'),
            'name' => 'Team',
        ]);
        $user = User::query()->where('email', 'taylor@example.com')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.registered',
            'subject_id' => $user->getKey(),
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'user.created',
            'subject_id' => $user->getKey(),
        ]);
    }

    public function test_register_page_renders_sign_up_fields_and_login_link(): void
    {
        $response = $this->get(route('register'));

        $response
            ->assertOk()
            ->assertSee('Sign Up')
            ->assertSee('Full name')
            ->assertSee('Email address')
            ->assertSee('Confirm Password')
            ->assertSee(route('login'))
            ->assertSee(route('register.submit'), false);
    }

    public function test_logout_redirects_to_the_home_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirectToRoute('home');
        $this->assertGuest();
    }

    public function test_inactive_users_cannot_sign_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->from(route('login'))
            ->post(route('login.submit'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
