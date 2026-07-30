<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_user_can_setup_and_verify_two_factor_auth(): void
    {
        $user = User::factory()->create();

        // Access setup page
        $response = $this->actingAs($user)->get('/user/two-factor-setup');
        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);

        // Generate current TOTP code using service
        $validCode = \App\Services\TwoFactorService::getCode($user->two_factor_secret);

        // Confirm 2FA
        $enableResponse = $this->actingAs($user)->post('/user/two-factor-enable', [
            'code' => $validCode,
        ]);

        $enableResponse->assertRedirect(route('profile.edit'));
        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
    }

    public function test_user_with_confirmed_2fa_is_redirected_to_secondary_challenge(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => \App\Services\TwoFactorService::generateSecretKey(),
            'two_factor_recovery_codes' => ['1234-5678'],
            'two_factor_confirmed_at' => now(),
        ]);

        // Login with password
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));

        // Complete 2FA challenge
        $validCode = \App\Services\TwoFactorService::getCode($user->two_factor_secret);
        $challengeResponse = $this->post('/two-factor-challenge', [
            'code' => $validCode,
        ]);

        $this->assertAuthenticatedAs($user);
    }
}
