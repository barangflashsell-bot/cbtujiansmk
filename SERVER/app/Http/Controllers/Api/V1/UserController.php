<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends ApiController
{
    /**
     * Display a paginated listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $paginator = User::with('role:id,name,display_name')
            ->latest('id')
            ->paginate($perPage);

        return $this->successResponse([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Daftar pengguna berhasil diambil');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64', 'unique:users,username'],
            'name' => ['required', 'string', 'max:128'],
            'email' => ['nullable', 'email', 'max:128', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->load('role:id,name,display_name');

        return $this->successResponse($user, 'User berhasil dibuat', 201);
    }

    /**
     * Display the specified user.
     */
    public function show(string $id): JsonResponse
    {
        $user = User::with('role:id,name,display_name')->find($id);

        if (! $user) {
            return $this->errorResponse('User tidak ditemukan', null, 404);
        }

        return $this->successResponse($user, 'Detail user berhasil diambil');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse('User tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'username' => ['sometimes', 'required', 'string', 'max:64', 'unique:users,username,'.$user->id],
            'name' => ['sometimes', 'required', 'string', 'max:128'],
            'email' => ['nullable', 'email', 'max:128', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['sometimes', 'required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->load('role:id,name,display_name');

        return $this->successResponse($user, 'User berhasil diperbarui');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse('User tidak ditemukan', null, 404);
        }

        if ($request->user() && $request->user()->id === $user->id) {
            return $this->errorResponse('Tidak dapat menghapus akun sendiri yang sedang aktif digunakan', null, 400);
        }

        $user->delete();

        return $this->successResponse(null, 'User berhasil dihapus');
    }
}
