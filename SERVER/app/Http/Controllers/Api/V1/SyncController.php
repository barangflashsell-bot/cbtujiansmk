<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Answer;
use App\Models\ExamAttempt;
use App\Models\QuestionOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends ApiController
{
    /**
     * Synchronize offline answers queue for an attempt (Atomic Upsert / Idempotent).
     */
    public function sync(Request $request, string $attemptId): JsonResponse
    {
        $attempt = ExamAttempt::find($attemptId);

        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Ownership authorization: only the student who owns the attempt can sync answers
        if (in_array($userRole, ['student', 'peserta'], true)) {
            if (! $user->student || $attempt->student_id !== $user->student->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak dapat menyinkronkan jawaban sesi ujian milik peserta lain', null, 403);
            }
        }

        $now = now();

        // Check if attempt has timed out
        if ($attempt->status === 'in_progress' && $now->gt($attempt->ends_at)) {
            $attempt->update(['status' => 'timeout']);
        }

        // Lock check: attempt must be in_progress
        if ($attempt->status !== 'in_progress') {
            return $this->errorResponse('Sesi ujian telah selesai atau terkunci. Sinkronisasi jawaban ditolak', null, 403);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.selected_option_id' => ['nullable', 'integer', 'exists:question_options,id'],
            'answers.*.essay_answer' => ['nullable', 'string'],
            'answers.*.is_flagged' => ['nullable', 'boolean'],
            'answers.*.answered_at' => ['nullable', 'date'],
        ]);

        $exam = $attempt->exam;
        $validQuestionIds = $exam->questions()->pluck('questions.id')->all();

        // Validate all questions belong to this exam
        foreach ($validated['answers'] as $index => $answerItem) {
            if (! in_array($answerItem['question_id'], $validQuestionIds, true)) {
                return $this->errorResponse("Butir soal ID {$answerItem['question_id']} bukan merupakan bagian dari paket ujian ini", [
                    "answers.{$index}.question_id" => ['Butir soal bukan bagian dari paket ujian yang sedang dikerjakan.'],
                ], 422);
            }
        }

        // Fetch options for mapping labels
        $optionIds = collect($validated['answers'])->pluck('selected_option_id')->filter()->all();
        $options = QuestionOption::whereIn('id', $optionIds)->get()->keyBy('id');

        // Check each option matches its question
        foreach ($validated['answers'] as $index => $answerItem) {
            if (! empty($answerItem['selected_option_id'])) {
                $option = $options->get($answerItem['selected_option_id']);
                if (! $option || $option->question_id !== $answerItem['question_id']) {
                    return $this->errorResponse("Pilihan jawaban tidak valid untuk butir soal ID {$answerItem['question_id']}", [
                        "answers.{$index}.selected_option_id" => ['Pilihan jawaban tidak sesuai dengan butir soal.'],
                    ], 422);
                }
            }
        }

        // Execute batch upsert within database transaction with pessimistic attempt locking
        $syncedAnswers = DB::transaction(function () use ($attempt, $validated, $options, $now) {
            $lockedAttempt = ExamAttempt::where('id', $attempt->id)->lockForUpdate()->first();

            if (! $lockedAttempt || $lockedAttempt->status !== 'in_progress' || $now->gt($lockedAttempt->ends_at)) {
                if ($lockedAttempt && $lockedAttempt->status === 'in_progress' && $now->gt($lockedAttempt->ends_at)) {
                    $lockedAttempt->update(['status' => 'timeout']);
                }

                return null;
            }

            $saved = [];
            foreach ($validated['answers'] as $item) {
                $optionLabel = null;
                if (! empty($item['selected_option_id'])) {
                    $option = $options->get($item['selected_option_id']);
                    $optionLabel = $option?->option_label;
                }

                $record = Answer::updateOrCreate(
                    [
                        'attempt_id' => $lockedAttempt->id,
                        'question_id' => $item['question_id'],
                    ],
                    [
                        'selected_option_id' => $item['selected_option_id'] ?? null,
                        'selected_option' => $optionLabel,
                        'essay_answer' => $item['essay_answer'] ?? null,
                        'is_flagged' => (bool) ($item['is_flagged'] ?? false),
                        'answered_at' => ! empty($item['answered_at']) ? $item['answered_at'] : $now,
                        'synced_at' => $now,
                    ]
                );

                $saved[] = [
                    'id' => $record->id,
                    'question_id' => $record->question_id,
                    'selected_option_id' => $record->selected_option_id,
                    'selected_option' => $record->selected_option,
                    'is_flagged' => (bool) $record->is_flagged,
                    'synced_at' => $record->synced_at,
                ];
            }

            $lockedAttempt->update(['last_activity_at' => $now]);

            return $saved;
        });

        if ($syncedAnswers === null) {
            return $this->errorResponse('Sesi ujian telah selesai atau terkunci. Sinkronisasi jawaban ditolak', null, 403);
        }

        $remainingSeconds = max(0, (int) $attempt->ends_at->diffInSeconds($now, false) * -1);

        return $this->successResponse([
            'status' => 'SYNCED',
            'synced_count' => count($syncedAnswers),
            'synced_at' => $now->toIso8601String(),
            'remaining_seconds' => $remainingSeconds,
            'server_time' => $now->toIso8601String(),
            'answers' => $syncedAnswers,
        ], 'Antrean jawaban berhasil disinkronkan');
    }
}
