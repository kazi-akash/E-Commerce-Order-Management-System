<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\User;

class JwtAuth
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $payload = $this->authService->verifyAccessToken($token);

        if (!$payload) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }

        $user = User::find($payload->sub);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        $request->merge(['auth_user' => $user]);
        auth()->setUser($user);

        return $next($request);
    }
}
