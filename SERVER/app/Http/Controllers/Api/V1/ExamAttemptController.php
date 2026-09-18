<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamAttemptController extends ApiController
{
    /**
     * Start a new exam attempt or recover an existing in-progress attempt.
     */
    public function start(Request $request, string $examId): JsonResponse
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return $this->errorResponse('Paket ujian tidak ditemukan', null, 404);
        }

        // Validate exam status
        if (! in_array($exam->status, ['published', 'active'], true)) {
            return $this->errorResponse('Sesi ujian tidak aktif atau belum dipublikasikan', null, 400);
        }

        $now = now();

        // Validate schedule window
        if ($exam->start_window && $now->lt($exam->start_window)) {
            return $this->errorResponse("Ujian belum dimulai. Waktu mulai: {$exam->start_window}", null, 400);
        }

        if ($exam->end_window && $now->gt($exam->end_window)) {
            return $this->errorResponse('Jadwal pelaksanaan ujian telah berakhir', null, 400);
        }

        // Validate exam token if required
        if (! empty($exam->token)) {
            $tokenInput = trim((string) $request->input('token'));
            if (empty($tokenInput) || strtoupper($tokenInput) !== strtoupper(trim((string) $exam->token))) {
                return $this->errorResponse('Token ujian salah atau belum diinput', [
                    'token' => ['Token ujian tidak valid.'],
                ], 400);
            }
        }

        // Resolve student profile
        $user = $request->user();
        $student = $user->student;

        if (! $student) {
            return $this->errorResponse('Hanya siswa/peserta yang dapat memulai sesi ujian', null, 403);
        }

        // Validate exam enrollment
        $participant = ExamParticipant::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $participant) {
            return $this->errorResponse('Siswa tidak terdaftar pada sesi ujian ini', null, 403);
        }

        // Atomic attempt retrieval, recovery, or creation within retryable transaction with race fallback
        try {
            $attempt = DB::transaction(function () use ($exam, $student, $participant, $now, $request) {
                $existingAttempt = ExamAttempt::where('exam_id', $exam->id)
                    ->where('student_id', $student->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingAttempt) {
                    // Auto timeout check
                    if ($existingAttempt->status === 'in_progress' && $now->gt($existingAttempt->ends_at)) {
                        $existingAttempt->update(['status' => 'timeout']);
                    }

                    // Closed attempt handling
                    if (in_array($existingAttempt->status, ['submitted', 'timeout', 'blocked'], true)) {
                        if (! $participant->allow_retest) {
                            return false;
                        }

                        // Allow retest: reset existing attempt safely within single row constraint
                        $startedAt = $now;
                        $endsAt = $this->calculateEndsAt($exam, $startedAt);

                        $existingAttempt->update([
                            'started_at' => $startedAt,
                            'ends_at' => $endsAt,
                            'submitted_at' => null,
                            'status' => 'in_progress',
                            'last_activity_at' => $startedAt,
                            'ip_address' => $request->ip(),
                            'device_info' => substr((string) $request->userAgent(), 0, 255),
                        ]);

                        return $existingAttempt;
                    }

                    // Recovery mechanism: return existing active attempt
                    $existingAttempt->update([
                        'last_activity_at' => $now,
                        'ip_address' => $request->ip(),
                        'device_info' => substr((string) $request->userAgent(), 0, 255),
                    ]);

                    return $existingAttempt;
                }

                // Create new attempt atomically
                $startedAt = $now;
                $endsAt = $this->calculateEndsAt($exam, $startedAt);

                return ExamAttempt::create([
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'started_at' => $startedAt,
                    'ends_at' => $endsAt,
                    'status' => 'in_progress',
                    'last_activity_at' => $startedAt,
                    'ip_address' => $request->ip(),
                    'device_info' => substr((string) $request->userAgent(), 0, 255),
                ]);
            }, 3);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? 0;
            $errorMessage = $e->getMessage();

            // Specifically handle duplicate entry 1062 on uk_exam_student_attempt or deadlock 1213
            $isDuplicateAttemptKey = ($errorCode === 1062 && (str_contains($errorMessage, 'uk_exam_student_attempt') || str_contains($errorMessage, 'exam_attempts')));
            $isDeadlock = ($errorCode === 1213 || str_contains($errorMessage, 'Deadlock'));

            if ($isDuplicateAttemptKey || $isDeadlock) {
                // Recover the attempt created by the concurrent request
                $recoveredAttempt = ExamAttempt::where('exam_id', $exam->id)
                    ->where('student_id', $student->id)
                    ->first();

                if ($recoveredAttempt) {
                    if (in_array($recoveredAttempt->status, ['submitted', 'timeout', 'blocked'], true) && ! $participant->allow_retest) {
                        return $this->errorResponse('Ujian sudah pernah diselesaikan dan tidak diizinkan ujian ulang', null, 403);
                    }

                    $recoveredAttempt->update([
                        'last_activity_at' => $now,
                        'ip_address' => $request->ip(),
                        'device_info' => substr((string) $request->userAgent(), 0, 255),
                    ]);

                    $attempt = $recoveredAttempt;
                } else {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        if ($attempt === false) {
            return $this->errorResponse('Ujian sudah pernah diselesaikan dan tidak diizinkan ujian ulang', null, 403);
        }

        // Fetch questions without answer keys (is_correct stripped)
        $examQuestionsQuery = $exam->examQuestions()
            ->with([
                'question' => function ($q) {
                    $q->select('id', 'subject_id', 'question_type', 'content', 'media_path');
                },
                'question.options' => function ($q) {
                    // CRITICAL: NEVER select is_correct for students
                    $q->select('id', 'question_id', 'option_label', 'content');
                },
            ])
            ->orderBy('order_index')
            ->get();

        // Acak urutan butir soal jika shuffle_questions aktif (seed per siswa & attempt agar konsisten saat refresh)
        if ($exam->shuffle_questions) {
            $seed = (int) (($student->id ?? 1) * 1000 + ($attempt->id ?? 1));
            $examQuestionsQuery = $examQuestionsQuery->shuffle($seed);
        }

        $questions = $examQuestionsQuery->values()->map(function ($eq, $idx) use ($exam) {
            $options = $eq->question->options;
            // Acak urutan pilihan jawaban jika shuffle_options aktif
            if ($exam->shuffle_options) {
                $options = $options->shuffle();
            }
            $labels = ['A', 'B', 'C', 'D', 'E'];

            return [
                'id' => $eq->question->id,
                'order_index' => $idx + 1,
                'original_order' => $eq->order_index,
                'weight' => $eq->weight,
                'question_type' => $eq->question->question_type,
                'content' => $eq->question->content,
                'media_path' => $eq->question->media_path,
                'options' => $options->values()->map(function ($opt, $optIdx) use ($labels) {
                    return [
                        'id' => $opt->id,
                        'label' => $labels[$optIdx] ?? $opt->option_label,
                        'content' => $opt->content,
                    ];
                }),
            ];
        });

        // Load previously saved answers if any
        $savedAnswers = Answer::where('attempt_id', $attempt->id)
            ->select('question_id', 'selected_option_id', 'selected_option', 'essay_answer', 'is_flagged')
            ->get();

        $remainingSeconds = max(0, (int) $attempt->ends_at->diffInSeconds(now(), false) * -1);

        return $this->successResponse([
            'attempt_id' => $attempt->id,
            'exam_id' => $exam->id,
            'started_at' => $attempt->started_at,
            'ends_at' => $attempt->ends_at,
            'duration_seconds' => $exam->duration_minutes * 60,
            'remaining_seconds' => $remainingSeconds,
            'server_time' => now(),
            'status' => $attempt->status,
            'questions' => $questions,
            'saved_answers' => $savedAnswers,
        ], 'Sesi ujian berhasil dimulai');
    }

    /**
     * Display details of the specified exam attempt.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $attempt = ExamAttempt::with([
            'exam.subject:id,name,code',
            'student.user:id,name,username',
        ])->find($id);

        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Ownership authorization: student can only view their own attempt
        if (in_array($userRole, ['student', 'peserta'], true)) {
            if (! $user->student || $attempt->student_id !== $user->student->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk melihat sesi ujian ini', null, 403);
            }
        }

        // Auto timeout check
        if ($attempt->status === 'in_progress' && now()->gt($attempt->ends_at)) {
            $attempt->update(['status' => 'timeout']);
        }

        $remainingSeconds = max(0, (int) $attempt->ends_at->diffInSeconds(now(), false) * -1);

        return $this->successResponse([
            'id' => $attempt->id,
            'exam_id' => $attempt->exam_id,
            'student_id' => $attempt->student_id,
            'started_at' => $attempt->started_at,
            'ends_at' => $attempt->ends_at,
            'submitted_at' => $attempt->submitted_at,
            'status' => $attempt->status,
            'remaining_seconds' => $remainingSeconds,
            'server_time' => now(),
            'exam' => [
                'id' => $attempt->exam->id,
                'title' => $attempt->exam->title,
                'duration_minutes' => $attempt->exam->duration_minutes,
                'subject' => $attempt->exam->subject,
            ],
            'student' => [
                'id' => $attempt->student->id,
                'nis' => $attempt->student->nis,
                'name' => $attempt->student->user->name ?? null,
            ],
        ], 'Detail sesi ujian berhasil diambil');
    }

    /**
     * Complete and submit the exam attempt (idempotent).
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $attempt = ExamAttempt::find($id);

        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Ownership authorization
        if (in_array($userRole, ['student', 'peserta'], true)) {
            if (! $user->student || $attempt->student_id !== $user->student->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk menyelesaikan sesi ujian ini', null, 403);
            }
        }

        // Idempotency: if already submitted, return success without re-submitting
        if ($attempt->status === 'submitted') {
            return $this->successResponse([
                'status' => 'SUBMITTED',
                'submitted_at' => $attempt->submitted_at,
            ], 'Ujian telah berhasil diselesaikan');
        }

        $now = now();

        $result = DB::transaction(function () use ($id, $now) {
            $lockedAttempt = ExamAttempt::where('id', $id)->lockForUpdate()->first();

            if (! $lockedAttempt) {
                return null;
            }

            // Re-check after lock
            if ($lockedAttempt->status === 'submitted') {
                return \App\Models\Result::where('attempt_id', $lockedAttempt->id)->first()
                    ?? app(ResultController::class)->calculateAndStoreResult($lockedAttempt);
            }

            $lockedAttempt->update([
                'status' => 'submitted',
                'submitted_at' => $now,
                'last_activity_at' => $now,
            ]);

            return app(ResultController::class)->calculateAndStoreResult($lockedAttempt);
        });

        if (! $result) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        return $this->successResponse([
            'status' => 'SUBMITTED',
            'submitted_at' => $now,
            'result_id' => $result->id,
        ], 'Ujian telah berhasil diselesaikan');
    }

    /**
     * Helper to compute ends_at capped by exam end_window.
     */
    protected function calculateEndsAt(Exam $exam, Carbon $startedAt): Carbon
    {
        $calculated = $startedAt->copy()->addMinutes($exam->duration_minutes);

        if ($exam->end_window) {
            $endWindow = Carbon::parse($exam->end_window);
            return $calculated->gt($endWindow) ? $endWindow : $calculated;
        }

        return $calculated;
    }
}
