<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    /**
     * Authenticate user and issue a Bearer token via Laravel Sanctum.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('role')->where('username', $validated['username'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse('Username atau password salah', null, 401);
        }

        if (! $user->is_active) {
            return $this->errorResponse('Akun pengguna tidak aktif. Hubungi administrator.', null, 403);
        }

        // Generate Sanctum Bearer token
        $token = $user->createToken('cbt-auth-token')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'role' => $user->role->name ?? 'unknown',
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil');
    }

    /**
     * Get authenticated user profile without exposing sensitive information.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'role' => $user->role->name ?? 'unknown',
            ],
        ], 'Data pengguna berhasil diambil');
    }

    /**
     * Revoke current access token on logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout berhasil');
    }
}
