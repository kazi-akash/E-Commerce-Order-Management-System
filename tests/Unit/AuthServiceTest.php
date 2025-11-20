<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\RefreshToken;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    public function test_register_creates_user_and_returns_tokens()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'customer',
        ];

        $result = $this->authService->register($data);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('john@example.com', $result['user']->email);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'customer',
        ]);
    }

    public function test_register_defaults_to_customer_role()
    {
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ];

        $result = $this->authService->register($data);

        $this->assertEquals('customer', $result['user']->role);
    }

    public function test_register_hashes_password()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $result = $this->authService->register($data);
        $user = User::where('email', 'john@example.com')->first();

        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_login_returns_tokens_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->authService->login('john@example.com', 'password123');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertEquals($user->id, $result['user']->id);
    }

    public function test_login_returns_null_with_invalid_email()
    {
        $result = $this->authService->login('nonexistent@example.com', 'password123');

        $this->assertNull($result);
    }

    public function test_login_returns_null_with_invalid_password()
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->authService->login('john@example.com', 'wrongpassword');

        $this->assertNull($result);
    }

    public function test_login_creates_refresh_token_record()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $metadata = [
            'user_agent' => 'Mozilla/5.0',
            'ip_address' => '127.0.0.1',
        ];

        $result = $this->authService->login('john@example.com', 'password123', $metadata);

        $this->assertDatabaseHas('refresh_tokens', [
            'user_id' => $user->id,
            'user_agent' => 'Mozilla/5.0',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_refresh_access_token_returns_new_token()
    {
        $user = User::factory()->create();
        $refreshToken = 'test-refresh-token';

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
            'expires_at' => now()->addDays(7),
        ]);

        $result = $this->authService->refreshAccessToken($refreshToken);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertEquals($user->id, $result['user']->id);
    }

    public function test_refresh_returns_null_with_invalid_token()
    {
        $result = $this->authService->refreshAccessToken('invalid-token');

        $this->assertNull($result);
    }

    public function test_refresh_returns_null_with_expired_token()
    {
        $user = User::factory()->create();
        $refreshToken = 'expired-token';

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
            'expires_at' => now()->subDay(),
        ]);

        $result = $this->authService->refreshAccessToken($refreshToken);

        $this->assertNull($result);
    }

    public function test_refresh_returns_null_with_revoked_token()
    {
        $user = User::factory()->create();
        $refreshToken = 'revoked-token';

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
            'expires_at' => now()->addDays(7),
            'is_revoked' => true,
            'revoked_at' => now(),
        ]);

        $result = $this->authService->refreshAccessToken($refreshToken);

        $this->assertNull($result);
    }

    public function test_logout_revokes_refresh_token()
    {
        $user = User::factory()->create();
        $refreshToken = 'test-token';

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
            'expires_at' => now()->addDays(7),
        ]);

        $result = $this->authService->logout($refreshToken);

        $this->assertTrue($result);
        $this->assertDatabaseHas('refresh_tokens', [
            'token' => hash('sha256', $refreshToken),
            'is_revoked' => true,
        ]);
    }

    public function test_logout_returns_false_with_invalid_token()
    {
        $result = $this->authService->logout('invalid-token');

        $this->assertFalse($result);
    }

    public function test_revoke_all_tokens_revokes_user_tokens()
    {
        $user = User::factory()->create();

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', 'token1'),
            'expires_at' => now()->addDays(7),
        ]);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', 'token2'),
            'expires_at' => now()->addDays(7),
        ]);

        $this->authService->revokeAllTokens($user);

        $this->assertEquals(2, RefreshToken::where('user_id', $user->id)
            ->where('is_revoked', true)
            ->count());
    }

    public function test_verify_access_token_returns_payload_for_valid_token()
    {
        $user = User::factory()->create();
        $result = $this->authService->login($user->email, 'password');

        $payload = $this->authService->verifyAccessToken($result['access_token']);

        $this->assertNotNull($payload);
        $this->assertEquals($user->id, $payload->sub);
        $this->assertEquals($user->email, $payload->email);
    }

    public function test_verify_access_token_returns_null_for_invalid_token()
    {
        $payload = $this->authService->verifyAccessToken('invalid-token');

        $this->assertNull($payload);
    }

    public function test_access_token_contains_user_role()
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $result = $this->authService->login($user->email, 'password');

        $payload = $this->authService->verifyAccessToken($result['access_token']);

        $this->assertEquals('vendor', $payload->role);
    }
}
