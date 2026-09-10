<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends ApiController
{
    /**
     * Display a paginated listing of subjects.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $paginator = Subject::withCount(['questions', 'exams'])
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
        ], 'Daftar mata pelajaran berhasil diambil');
    }

    /**
     * Store a newly created subject.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:128'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $subject = Subject::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'status' => $validated['status'] ?? 'active',
        ]);

        $subject->loadCount(['questions', 'exams']);

        return $this->successResponse($subject, 'Mata pelajaran berhasil dibuat', 201);
    }

    /**
     * Display the specified subject.
     */
    public function show(string $id): JsonResponse
    {
        $subject = Subject::withCount(['questions', 'exams'])->find($id);

        if (! $subject) {
            return $this->errorResponse('Mata pelajaran tidak ditemukan', null, 404);
        }

        return $this->successResponse($subject, 'Detail mata pelajaran berhasil diambil');
    }

    /**
     * Update the specified subject.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (! $subject) {
            return $this->errorResponse('Mata pelajaran tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:32', 'unique:subjects,code,'.$subject->id],
            'name' => ['sometimes', 'required', 'string', 'max:128'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $subject->update($validated);
        $subject->loadCount(['questions', 'exams']);

        return $this->successResponse($subject, 'Mata pelajaran berhasil diperbarui');
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(string $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (! $subject) {
            return $this->errorResponse('Mata pelajaran tidak ditemukan', null, 404);
        }

        // Integrity check: prevent deletion if subject has authored questions or exams
        if ($subject->questions()->exists() || $subject->exams()->exists()) {
            return $this->errorResponse('Tidak dapat menghapus mata pelajaran karena terhubung dengan bank soal atau ujian', null, 400);
        }

        $subject->delete();

        return $this->successResponse(null, 'Mata pelajaran berhasil dihapus');
    }
}
