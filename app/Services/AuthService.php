<?php

namespace App\Services;

use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    private string $jwtSecret;
    private int $accessTokenExpiry = 3600; // 1 hour
    private int $refreshTokenExpiry = 604800; // 7 days

    public function __construct()
    {
        $this->jwtSecret = config('app.key');
    }

    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'customer',
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        return $this->generateTokens($user);
    }

    public function login(string $email, string $password, array $metadata = []): ?array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $this->generateTokens($user, $metadata);
    }

    public function refreshAccessToken(string $refreshToken, array $metadata = []): ?array
    {
        $tokenRecord = RefreshToken::where('token', hash('sha256', $refreshToken))
            ->valid()
            ->first();

        if (!$tokenRecord) {
            return null;
        }

        $user = $tokenRecord->user;
        
        // Generate new access token
        $accessToken = $this->createAccessToken($user);

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenExpiry,
            'user' => $user,
        ];
    }

    public function logout(string $refreshToken): bool
    {
        $tokenRecord = RefreshToken::where('token', hash('sha256', $refreshToken))->first();

        if ($tokenRecord) {
            $tokenRecord->revoke();
            return true;
        }

        return false;
    }

    public function revokeAllTokens(User $user): void
    {
        $user->refreshTokens()->update([
            'is_revoked' => true,
            'revoked_at' => now(),
        ]);
    }

    private function generateTokens(User $user, array $metadata = []): array
    {
        $accessToken = $this->createAccessToken($user);
        $refreshToken = $this->createRefreshToken($user, $metadata);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenExpiry,
            'user' => $user,
        ];
    }

    private function createAccessToken(User $user): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + $this->accessTokenExpiry,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function createRefreshToken(User $user, array $metadata = []): string
    {
        $token = Str::random(64);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addSeconds($this->refreshTokenExpiry),
            'user_agent' => $metadata['user_agent'] ?? null,
            'ip_address' => $metadata['ip_address'] ?? null,
        ]);

        return $token;
    }

    public function verifyAccessToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
