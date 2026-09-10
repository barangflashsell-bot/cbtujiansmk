<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherController extends ApiController
{
    /**
     * Display a paginated listing of teachers.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $paginator = Teacher::with('user:id,username,name,email,is_active')
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
        ], 'Daftar guru berhasil diambil');
    }

    /**
     * Store a newly created teacher.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id', 'unique:teachers,user_id'],
            'username' => ['required_without:user_id', 'nullable', 'string', 'max:64', 'unique:users,username'],
            'name' => ['required_without:user_id', 'nullable', 'string', 'max:128'],
            'password' => ['required_without:user_id', 'nullable', 'string', 'min:6'],
            'nip' => ['nullable', 'string', 'max:32', 'unique:teachers,nip'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $teacher = DB::transaction(function () use ($validated) {
            $userId = $validated['user_id'] ?? null;

            if (! $userId) {
                $teacherRole = Role::where('name', 'teacher')->firstOrFail();

                $user = User::create([
                    'username' => $validated['username'],
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['password']),
                    'role_id' => $teacherRole->id,
                    'is_active' => true,
                ]);

                $userId = $user->id;
            }

            return Teacher::create([
                'user_id' => $userId,
                'nip' => $validated['nip'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        });

        $teacher->load('user:id,username,name,email,is_active');

        return $this->successResponse($teacher, 'Data guru berhasil ditambahkan', 201);
    }

    /**
     * Display the specified teacher.
     */
    public function show(string $id): JsonResponse
    {
        $teacher = Teacher::with('user:id,username,name,email,is_active')->find($id);

        if (! $teacher) {
            return $this->errorResponse('Data guru tidak ditemukan', null, 404);
        }

        return $this->successResponse($teacher, 'Detail guru berhasil diambil');
    }

    /**
     * Update the specified teacher.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $teacher = Teacher::find($id);

        if (! $teacher) {
            return $this->errorResponse('Data guru tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:32', 'unique:teachers,nip,'.$teacher->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:128'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        DB::transaction(function () use ($teacher, $validated) {
            $teacher->update([
                'nip' => array_key_exists('nip', $validated) ? $validated['nip'] : $teacher->nip,
                'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $teacher->phone,
            ]);

            if (! empty($validated['name']) || ! empty($validated['password'])) {
                $userUpdates = [];
                if (! empty($validated['name'])) {
                    $userUpdates['name'] = $validated['name'];
                }
                if (! empty($validated['password'])) {
                    $userUpdates['password'] = Hash::make($validated['password']);
                }
                $teacher->user()->update($userUpdates);
            }
        });

        $teacher->load('user:id,username,name,email,is_active');

        return $this->successResponse($teacher, 'Data guru berhasil diperbarui');
    }

    /**
     * Remove the specified teacher.
     */
    public function destroy(string $id): JsonResponse
    {
        $teacher = Teacher::find($id);

        if (! $teacher) {
            return $this->errorResponse('Data guru tidak ditemukan', null, 404);
        }

        // Integrity check: prevent deletion if teacher authored questions
        if ($teacher->questions()->exists()) {
            return $this->errorResponse('Tidak dapat menghapus guru karena terhubung dengan butir soal di bank soal', null, 400);
        }

        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;
            $teacher->delete();
            if ($user) {
                $user->delete();
            }
        });

        return $this->successResponse(null, 'Data guru berhasil dihapus');
    }
}
