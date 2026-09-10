<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends ApiController
{
    /**
     * Display a paginated listing of exams.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $user = $request->user();

        $query = Exam::with(['subject:id,code,name', 'creator:id,name'])
            ->withCount('questions')
            ->latest('id');

        // Filter for students: only active or published exams
        if ($user && $user->role && $user->role->name === 'student') {
            $query->whereIn('status', ['published', 'active']);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
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
        ], 'Daftar paket ujian berhasil diambil');
    }

    /**
     * Store a newly created exam.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'token' => ['nullable', 'string', 'max:16'],
            'start_window' => ['required', 'date'],
            'end_window' => ['required', 'date', 'after:start_window'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'show_result' => ['nullable', 'boolean'],
            'allow_review' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:draft,published,active,completed'],
        ]);

        $exam = DB::transaction(function () use ($validated, $request) {
            return Exam::create([
                'subject_id' => $validated['subject_id'],
                'created_by' => $request->user()->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'instructions' => $validated['instructions'] ?? null,
                'duration_minutes' => $validated['duration_minutes'],
                'passing_score' => $validated['passing_score'] ?? 75.00,
                'token' => $validated['token'] ?? null,
                'start_window' => $validated['start_window'],
                'end_window' => $validated['end_window'],
                'shuffle_questions' => $validated['shuffle_questions'] ?? true,
                'shuffle_options' => $validated['shuffle_options'] ?? true,
                'show_result' => $validated['show_result'] ?? false,
                'allow_review' => $validated['allow_review'] ?? false,
                'status' => $validated['status'] ?? 'draft',
            ]);
        });

        $exam->load(['subject:id,code,name', 'creator:id,name']);

        return $this->successResponse($exam, 'Paket ujian berhasil dibuat', 201);
    }

    /**
     * Display the specified exam.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $exam = Exam::with(['subject:id,code,name', 'creator:id,name', 'questions.options'])
            ->withCount('questions')
            ->find($id);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        if ($user && $user->role && $user->role->name === 'student') {
            if ($exam->status === 'draft') {
                return $this->errorResponse('Paket ujian belum dibuka untuk peserta', null, 403);
            }

            // Strict answer-key protection: sanitize options for student
            foreach ($exam->questions as $question) {
                if ($question->options) {
                    $question->options->makeHidden(['is_correct']);
                }
            }
        }

        return $this->successResponse($exam, 'Detail paket ujian berhasil diambil');
    }

    /**
     * Update the specified exam.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'subject_id' => ['sometimes', 'required', 'integer', 'exists:subjects,id'],
            'title' => ['sometimes', 'required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:1440'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'token' => ['nullable', 'string', 'max:16'],
            'start_window' => ['sometimes', 'required', 'date'],
            'end_window' => [
                'sometimes',
                'required',
                'date',
                'after:'.($request->input('start_window') ?? $exam->start_window),
            ],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'show_result' => ['nullable', 'boolean'],
            'allow_review' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:draft,published,active,completed'],
        ]);

        DB::transaction(function () use ($exam, $validated) {
            $exam->update($validated);
        });

        $exam->load(['subject:id,code,name', 'creator:id,name']);

        return $this->successResponse($exam, 'Paket ujian berhasil diperbarui');
    }

    /**
     * Remove the specified exam.
     */
    public function destroy(string $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        // Integrity check: do not delete exam if student attempts already exist
        if ($exam->attempts()->exists()) {
            return $this->errorResponse('Tidak dapat menghapus ujian karena sudah memiliki riwayat pengerjaan siswa', null, 400);
        }

        DB::transaction(function () use ($exam) {
            $exam->delete();
        });

        return $this->successResponse(null, 'Paket ujian berhasil dihapus');
    }
}
