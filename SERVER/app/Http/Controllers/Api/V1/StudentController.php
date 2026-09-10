<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends ApiController
{
    /**
     * Display a paginated listing of students.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $paginator = Student::with([
            'user:id,username,name,email,is_active',
            'schoolClass:id,name,level,academic_year',
        ])
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
        ], 'Daftar siswa berhasil diambil');
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id', 'unique:students,user_id'],
            'username' => ['required_without:user_id', 'nullable', 'string', 'max:64', 'unique:users,username'],
            'name' => ['required_without:user_id', 'nullable', 'string', 'max:128'],
            'password' => ['required_without:user_id', 'nullable', 'string', 'min:6'],
            'class_id' => ['required', 'exists:classes,id'],
            'nis' => ['required', 'string', 'max:32', 'unique:students,nis'],
            'nisn' => ['nullable', 'string', 'max:32'],
            'gender' => ['required', 'in:L,P'],
        ]);

        $student = DB::transaction(function () use ($validated) {
            $userId = $validated['user_id'] ?? null;

            if (! $userId) {
                $studentRole = Role::where('name', 'student')->firstOrFail();

                $user = User::create([
                    'username' => $validated['username'],
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['password']),
                    'role_id' => $studentRole->id,
                    'is_active' => true,
                ]);

                $userId = $user->id;
            }

            return Student::create([
                'user_id' => $userId,
                'class_id' => $validated['class_id'],
                'nis' => $validated['nis'],
                'nisn' => $validated['nisn'] ?? null,
                'gender' => $validated['gender'],
            ]);
        });

        $student->load(['user:id,username,name,email,is_active', 'schoolClass:id,name,level,academic_year']);

        return $this->successResponse($student, 'Data siswa berhasil ditambahkan', 201);
    }

    /**
     * Display the specified student.
     */
    public function show(string $id): JsonResponse
    {
        $student = Student::with([
            'user:id,username,name,email,is_active',
            'schoolClass:id,name,level,academic_year',
        ])->find($id);

        if (! $student) {
            return $this->errorResponse('Data siswa tidak ditemukan', null, 404);
        }

        return $this->successResponse($student, 'Detail siswa berhasil diambil');
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $student = Student::find($id);

        if (! $student) {
            return $this->errorResponse('Data siswa tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'class_id' => ['sometimes', 'required', 'exists:classes,id'],
            'nis' => ['sometimes', 'required', 'string', 'max:32', 'unique:students,nis,'.$student->id],
            'nisn' => ['nullable', 'string', 'max:32'],
            'gender' => ['sometimes', 'required', 'in:L,P'],
            'name' => ['nullable', 'string', 'max:128'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        DB::transaction(function () use ($student, $validated) {
            $student->update([
                'class_id' => $validated['class_id'] ?? $student->class_id,
                'nis' => $validated['nis'] ?? $student->nis,
                'nisn' => array_key_exists('nisn', $validated) ? $validated['nisn'] : $student->nisn,
                'gender' => $validated['gender'] ?? $student->gender,
            ]);

            if (! empty($validated['name']) || ! empty($validated['password'])) {
                $userUpdates = [];
                if (! empty($validated['name'])) {
                    $userUpdates['name'] = $validated['name'];
                }
                if (! empty($validated['password'])) {
                    $userUpdates['password'] = Hash::make($validated['password']);
                }
                $student->user()->update($userUpdates);
            }
        });

        $student->load(['user:id,username,name,email,is_active', 'schoolClass:id,name,level,academic_year']);

        return $this->successResponse($student, 'Data siswa berhasil diperbarui');
    }

    /**
     * Remove the specified student.
     */
    public function destroy(string $id): JsonResponse
    {
        $student = Student::find($id);

        if (! $student) {
            return $this->errorResponse('Data siswa tidak ditemukan', null, 404);
        }

        // Integrity check: prevent deletion if student already has exam attempts
        if ($student->examAttempts()->exists()) {
            return $this->errorResponse('Tidak dapat menghapus siswa karena sudah memiliki riwayat ujian. Nonaktifkan akun siswa sebagai alternatif.', null, 400);
        }

        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();
            if ($user) {
                $user->delete();
            }
        });

        return $this->successResponse(null, 'Data siswa berhasil dihapus');
    }
}
