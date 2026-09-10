<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Classes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassController extends ApiController
{
    /**
     * Display a paginated listing of classes.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $paginator = Classes::withCount('students')
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
        ], 'Daftar kelas berhasil diambil');
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'level' => ['required', 'string', 'max:16'],
            'academic_year' => ['required', 'string', 'max:16'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $class = Classes::create([
            'name' => $validated['name'],
            'level' => $validated['level'],
            'academic_year' => $validated['academic_year'],
            'status' => $validated['status'] ?? 'active',
        ]);

        $class->loadCount('students');

        return $this->successResponse($class, 'Kelas berhasil dibuat', 201);
    }

    /**
     * Display the specified class.
     */
    public function show(string $id): JsonResponse
    {
        $class = Classes::withCount('students')->find($id);

        if (! $class) {
            return $this->errorResponse('Kelas tidak ditemukan', null, 404);
        }

        return $this->successResponse($class, 'Detail kelas berhasil diambil');
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $class = Classes::find($id);

        if (! $class) {
            return $this->errorResponse('Kelas tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:64'],
            'level' => ['sometimes', 'required', 'string', 'max:16'],
            'academic_year' => ['sometimes', 'required', 'string', 'max:16'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $class->update($validated);
        $class->loadCount('students');

        return $this->successResponse($class, 'Kelas berhasil diperbarui');
    }

    /**
     * Remove the specified class.
     */
    public function destroy(string $id): JsonResponse
    {
        $class = Classes::find($id);

        if (! $class) {
            return $this->errorResponse('Kelas tidak ditemukan', null, 404);
        }

        // Integrity check: prevent deletion if class has enrolled students
        if ($class->students()->exists()) {
            return $this->errorResponse('Tidak dapat menghapus kelas karena masih memiliki siswa terdaftar', null, 400);
        }

        $class->delete();

        return $this->successResponse(null, 'Kelas berhasil dihapus');
    }
}
