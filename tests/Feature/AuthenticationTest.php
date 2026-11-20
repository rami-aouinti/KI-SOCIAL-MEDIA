<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_before_opening_the_dashboard(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));

        $this->get('/weekly-plans/1/report')
            ->assertRedirect(route('login'));
    }

    public function test_user_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create([
            'email' => 'editor@example.com',
            'password' => 'a-strong-password-123',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'a-strong-password-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get('/')
            ->assertOk();

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_invalid_credentials_do_not_authenticate_a_user(): void
    {
        User::factory()->create([
            'email' => 'editor@example.com',
            'password' => 'a-strong-password-123',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'editor@example.com',
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_valid_credentials(): void
    {
        $this->post(route('login.store'), [
            'email' => 'not-an-email',
            'password' => '',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_authentication_uses_the_architecture_user_model(): void
    {
        $this->assertSame(User::class, config('auth.providers.users.model'));

        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
