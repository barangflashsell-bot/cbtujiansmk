<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Question;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends ApiController
{
    /**
     * Display a paginated listing of questions.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $query = Question::with(['subject:id,code,name', 'creator.user:id,name'])
            ->withCount('options')
            ->latest('id');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }

        $paginator = $query->paginate($perPage);

        return $this->successResponse([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Daftar butir soal berhasil diambil');
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'created_by' => ['nullable', 'integer', 'exists:teachers,id'],
            'question_type' => ['required', 'string', 'in:single_choice,multiple_choice,essay'],
            'content' => ['required', 'string'],
            'media_path' => ['nullable', 'string', 'max:255'],
            'score_weight' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard'],
            'explanation' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        // Determine creator teacher id based on current authenticated user role
        $teacherId = null;
        if ($request->user() && $request->user()->teacher) {
            $teacherId = $request->user()->teacher->id;
        } elseif (! empty($validated['created_by'])) {
            $teacherId = (int) $validated['created_by'];
        } else {
            $teacherId = Teacher::first()?->id;
        }

        if (! $teacherId) {
            return $this->errorResponse('Data guru pembuat soal (created_by) wajib tersedia', [
                'created_by' => ['Data guru pembuat soal tidak ditemukan dalam sistem.'],
            ], 422);
        }

        $question = Question::create([
            'subject_id' => $validated['subject_id'],
            'created_by' => $teacherId,
            'question_type' => $validated['question_type'],
            'content' => $validated['content'],
            'media_path' => $validated['media_path'] ?? null,
            'score_weight' => $validated['score_weight'] ?? 1.00,
            'difficulty' => $validated['difficulty'] ?? 'medium',
            'explanation' => $validated['explanation'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        $question->load(['subject:id,code,name', 'creator.user:id,name', 'options']);

        return $this->successResponse($question, 'Butir soal berhasil dibuat', 201);
    }

    /**
     * Display the specified question.
     */
    public function show(string $id): JsonResponse
    {
        $question = Question::with(['subject:id,code,name', 'creator.user:id,name', 'options'])
            ->find($id);

        if (! $question) {
            return $this->errorResponse('Butir soal tidak ditemukan', null, 404);
        }

        return $this->successResponse($question, 'Detail butir soal berhasil diambil');
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $question = Question::find($id);

        if (! $question) {
            return $this->errorResponse('Butir soal tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'subject_id' => ['sometimes', 'required', 'integer', 'exists:subjects,id'],
            'created_by' => ['sometimes', 'nullable', 'integer', 'exists:teachers,id'],
            'question_type' => ['sometimes', 'required', 'string', 'in:single_choice,multiple_choice,essay'],
            'content' => ['sometimes', 'required', 'string'],
            'media_path' => ['nullable', 'string', 'max:255'],
            'score_weight' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard'],
            'explanation' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $question->update($validated);
        $question->load(['subject:id,code,name', 'creator.user:id,name', 'options']);

        return $this->successResponse($question, 'Butir soal berhasil diperbarui');
    }

    /**
     * Remove the specified question.
     */
    public function destroy(string $id): JsonResponse
    {
        $question = Question::find($id);

        if (! $question) {
            return $this->errorResponse('Butir soal tidak ditemukan', null, 404);
        }

        // Integrity check: prevent deletion if question is already in exams or answers
        if ($question->examQuestions()->exists() || $question->answers()->exists()) {
            return $this->errorResponse('Tidak dapat menghapus soal karena sudah terhubung dalam paket ujian atau lembar jawaban siswa', null, 400);
        }

        $question->delete();

        return $this->successResponse(null, 'Butir soal berhasil dihapus');
    }
}
