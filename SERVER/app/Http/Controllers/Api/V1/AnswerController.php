<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Answer;
use App\Models\ExamAttempt;
use App\Models\QuestionOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnswerController extends ApiController
{
    /**
     * Display all answers saved for the specified attempt.
     */
    public function index(Request $request, string $attemptId): JsonResponse
    {
        $attempt = ExamAttempt::find($attemptId);

        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Ownership authorization
        if (in_array($userRole, ['student', 'peserta'], true)) {
            if (! $user->student || $attempt->student_id !== $user->student->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk melihat jawaban sesi ujian ini', null, 403);
            }
        }

        $answers = $attempt->answers()
            ->select('id', 'attempt_id', 'question_id', 'selected_option_id', 'selected_option', 'essay_answer', 'is_flagged', 'answered_at', 'synced_at')
            ->orderBy('question_id')
            ->get();

        return $this->successResponse($answers, 'Daftar jawaban berhasil diambil');
    }

    /**
     * Store or update an answer for the specified attempt (Atomic Upsert / Idempotent).
     */
    public function store(Request $request, string $attemptId): JsonResponse
    {
        $attempt = ExamAttempt::find($attemptId);

        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Ownership authorization: only owner can submit answers
        if (in_array($userRole, ['student', 'peserta'], true)) {
            if (! $user->student || $attempt->student_id !== $user->student->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak dapat mengubah jawaban sesi ujian milik peserta lain', null, 403);
            }
        }

        $now = now();

        // Check if attempt has timed out
        if ($attempt->status === 'in_progress' && $now->gt($attempt->ends_at)) {
            $attempt->update(['status' => 'timeout']);
        }

        // Lock check: attempt must be in_progress
        if ($attempt->status !== 'in_progress') {
            return $this->errorResponse('Sesi ujian telah selesai atau terkunci. Perubahan jawaban ditolak', null, 403);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_option_id' => ['nullable', 'integer', 'exists:question_options,id'],
            'essay_answer' => ['nullable', 'string'],
            'is_flagged' => ['nullable', 'boolean'],
        ]);

        $exam = $attempt->exam;

        // Server-side question integrity: question must belong to the exam
        $isQuestionInExam = $exam->questions()
            ->where('questions.id', $validated['question_id'])
            ->exists();

        if (! $isQuestionInExam) {
            return $this->errorResponse('Butir soal bukan merupakan bagian dari paket ujian ini', [
                'question_id' => ['Butir soal bukan bagian dari paket ujian yang sedang dikerjakan.'],
            ], 422);
        }

        // Validate selected option if provided
        $optionLabel = null;
        if (! empty($validated['selected_option_id'])) {
            $option = QuestionOption::where('id', $validated['selected_option_id'])
                ->where('question_id', $validated['question_id'])
                ->first();

            if (! $option) {
                return $this->errorResponse('Pilihan jawaban tidak valid untuk butir soal ini', [
                    'selected_option_id' => ['Pilihan jawaban tidak sesuai dengan butir soal.'],
                ], 422);
            }

            $optionLabel = $option->option_label;
        }

        // Atomic Upsert with pessimistic attempt locking: idempotent storage respecting uk_attempt_question
        $answer = DB::transaction(function () use ($attempt, $validated, $optionLabel, $now) {
            $lockedAttempt = ExamAttempt::where('id', $attempt->id)->lockForUpdate()->first();

            if (! $lockedAttempt || $lockedAttempt->status !== 'in_progress' || $now->gt($lockedAttempt->ends_at)) {
                if ($lockedAttempt && $lockedAttempt->status === 'in_progress' && $now->gt($lockedAttempt->ends_at)) {
                    $lockedAttempt->update(['status' => 'timeout']);
                }

                return null;
            }

            $record = Answer::updateOrCreate(
                [
                    'attempt_id' => $lockedAttempt->id,
                    'question_id' => $validated['question_id'],
                ],
                [
                    'selected_option_id' => $validated['selected_option_id'] ?? null,
                    'selected_option' => $optionLabel,
                    'essay_answer' => $validated['essay_answer'] ?? null,
                    'is_flagged' => (bool) ($validated['is_flagged'] ?? false),
                    'answered_at' => $now,
                    'synced_at' => $now,
                ]
            );

            $lockedAttempt->update(['last_activity_at' => $now]);

            return $record;
        });

        if ($answer === null) {
            return $this->errorResponse('Sesi ujian telah selesai atau terkunci. Perubahan jawaban ditolak', null, 403);
        }

        $remainingSeconds = max(0, (int) $attempt->ends_at->diffInSeconds($now, false) * -1);

        return $this->successResponse([
            'status' => 'SAVED',
            'synced_at' => $answer->synced_at,
            'remaining_seconds' => $remainingSeconds,
            'answer' => [
                'id' => $answer->id,
                'attempt_id' => $answer->attempt_id,
                'question_id' => $answer->question_id,
                'selected_option_id' => $answer->selected_option_id,
                'selected_option' => $answer->selected_option,
                'essay_answer' => $answer->essay_answer,
                'is_flagged' => $answer->is_flagged,
                'answered_at' => $answer->answered_at,
            ],
        ], 'Jawaban berhasil disimpan');
    }
}
