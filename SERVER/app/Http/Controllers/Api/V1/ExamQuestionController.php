<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamQuestionController extends ApiController
{
    /**
     * Display a listing of questions attached to the specified exam.
     */
    public function index(Request $request, string $examId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $paginator = $exam->questions()
            ->with(['options'])
            ->withPivot(['order_index', 'weight'])
            ->orderBy('exam_questions.order_index')
            ->paginate($perPage);

        $user = $request->user();
        if ($user && $user->role && $user->role->name === 'student') {
            // Strict answer-key protection: ensure is_correct is never exposed to students
            foreach ($paginator->items() as $question) {
                if ($question->options) {
                    $question->options->makeHidden(['is_correct']);
                }
            }
        }

        return $this->successResponse([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Daftar butir soal ujian berhasil diambil');
    }

    /**
     * Attach a question to the specified exam.
     */
    public function store(Request $request, string $examId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
        ]);

        $question = Question::find($validated['question_id']);

        // Check subject consistency
        if ($question->subject_id !== $exam->subject_id) {
            return $this->errorResponse('Mata pelajaran butir soal tidak sesuai dengan mata pelajaran paket ujian', [
                'question_id' => ['Mata pelajaran butir soal tidak sesuai dengan mata pelajaran paket ujian.'],
            ], 422);
        }

        // Check duplicate
        if ($exam->examQuestions()->where('question_id', $validated['question_id'])->exists()) {
            return $this->errorResponse('Butir soal ini sudah terdaftar dalam paket ujian', [
                'question_id' => ['Butir soal ini sudah terdaftar dalam paket ujian.'],
            ], 422);
        }

        $examQuestion = DB::transaction(function () use ($exam, $question, $validated) {
            $nextOrder = ($exam->examQuestions()->max('order_index') ?? 0) + 1;

            return ExamQuestion::create([
                'exam_id' => $exam->id,
                'question_id' => $question->id,
                'order_index' => $validated['order_index'] ?? $nextOrder,
                'weight' => $validated['weight'] ?? $question->score_weight ?? 1.00,
            ]);
        });

        $examQuestion->load(['question.options']);

        return $this->successResponse($examQuestion, 'Butir soal berhasil ditambahkan ke paket ujian', 201);
    }

    /**
     * Update order or weight of a question in the specified exam.
     */
    public function update(Request $request, string $examId, string $questionId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $examQuestion = $exam->examQuestions()->where('question_id', $questionId)->first();

        if (! $examQuestion) {
            return $this->errorResponse('Butir soal tidak ditemukan dalam paket ujian ini', null, 404);
        }

        $validated = $request->validate([
            'order_index' => ['sometimes', 'required', 'integer', 'min:0'],
            'weight' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999.99'],
        ]);

        DB::transaction(function () use ($examQuestion, $validated) {
            $examQuestion->update($validated);
        });

        return $this->successResponse($examQuestion, 'Konfigurasi butir soal ujian berhasil diperbarui');
    }

    /**
     * Remove a question from the specified exam.
     */
    public function destroy(string $examId, string $questionId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        $examQuestion = $exam->examQuestions()->where('question_id', $questionId)->first();

        if (! $examQuestion) {
            return $this->errorResponse('Butir soal tidak ditemukan dalam paket ujian ini', null, 404);
        }

        // Integrity check: do not detach if exam attempts already exist
        if ($exam->attempts()->exists()) {
            return $this->errorResponse('Tidak dapat menghapus butir soal dari ujian yang sudah memiliki riwayat pengerjaan siswa', null, 400);
        }

        DB::transaction(function () use ($examQuestion) {
            $examQuestion->delete();
        });

        return $this->successResponse(null, 'Butir soal berhasil dihapus dari paket ujian');
    }
}
