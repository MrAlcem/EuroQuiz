<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_register_creates_a_user_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'token', 'user' => ['id', 'name', 'email']]);
        $response->assertJsonPath('user.email', 'jane@example.com');
        $response->assertJsonPath('mfa_required', false);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_a_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'something-else',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_registered_user_can_log_in_with_their_password(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('mfa_required', false);
    }

    public function test_authenticated_user_can_set_up_and_verify_mfa(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $setup = $this->postJson('/api/mfa/setup');
        $setup->assertOk()->assertJsonStructure(['secret', 'qr_code_svg', 'otp_url']);

        $secret = $setup->json('secret');
        $verify = $this->postJson('/api/mfa/verify', [
            'code' => (new Google2FA())->getCurrentOtp($secret),
        ]);

        $verify->assertOk()->assertJsonPath('two_factor_enabled', true);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);
    }

    public function test_mfa_enabled_user_must_complete_the_mfa_login_challenge(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $login->assertOk()->assertJsonPath('mfa_required', true);
        $login->assertJsonStructure(['challenge_token']);
        $this->assertArrayNotHasKey('token', $login->json());

        $verified = $this->postJson('/api/auth/mfa/verify', [
            'challenge_token' => $login->json('challenge_token'),
            'code' => (new Google2FA())->getCurrentOtp($secret),
        ]);

        $verified->assertOk()
            ->assertJsonPath('mfa_required', false)
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);
    }
}
