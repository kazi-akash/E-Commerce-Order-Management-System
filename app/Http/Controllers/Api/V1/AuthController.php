<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:admin,vendor,customer',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->authService->register($request->all());

        return response()->json([
            'message' => 'Registration successful',
            'data' => $result,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $metadata = [
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ];

        $result = $this->authService->login(
            $request->email,
            $request->password,
            $metadata
        );

        if (!$result) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'data' => $result,
        ]);
    }

    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $metadata = [
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ];

        $result = $this->authService->refreshAccessToken($request->refresh_token, $metadata);

        if (!$result) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        return response()->json([
            'message' => 'Token refreshed',
            'data' => $result,
        ]);
    }

    public function logout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->authService->logout($request->refresh_token);

        return response()->json(['message' => 'Logout successful']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'data' => auth()->user(),
        ]);
    }
}
