<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Answer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\QuestionOption;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends ApiController
{
    /**
     * Display a listing of exam results with optional filtering and role-based isolation.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        $query = Result::with([
            'exam' => function ($q) {
                $q->select('id', 'subject_id', 'title', 'show_result');
            },
            'exam.subject:id,name,code',
            'student:id,user_id,class_id,nis',
            'student.user:id,name,username',
            'student.schoolClass:id,name,level',
        ])->latest('id');

        // Student Isolation: Only view own published results
        if (in_array($userRole, ['student', 'peserta'], true)) {
            if (! $user->student) {
                return $this->errorResponse('Profil siswa tidak ditemukan', null, 403);
            }

            $query->where('student_id', $user->student->id)
                ->where(function ($q) {
                    $q->where('is_published', true)
                        ->orWhereHas('exam', function ($eq) {
                            $eq->where('show_result', true);
                        });
                });
        } else {
            // Teacher & Admin Filters
            if ($request->filled('exam_id')) {
                $query->where('exam_id', $request->query('exam_id'));
            }

            if ($request->filled('student_id')) {
                $query->where('student_id', $request->query('student_id'));
            }

            if ($request->filled('class_id')) {
                $classId = $request->query('class_id');
                $query->whereHas('student', function ($sq) use ($classId) {
                    $sq->where('class_id', $classId);
                });
            }

            if ($request->filled('is_published')) {
                $query->where('is_published', filter_var($request->query('is_published'), FILTER_VALIDATE_BOOLEAN));
            }
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage);

        return $this->successResponse([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Daftar hasil ujian berhasil diambil');
    }

    /**
     * Display the specified exam result detail with ownership and publication checks.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $result = Result::with([
            'exam' => function ($q) {
                $q->select('id', 'subject_id', 'title', 'duration_minutes', 'passing_score', 'show_result');
            },
            'exam.subject:id,name,code',
            'student:id,user_id,class_id,nis',
            'student.user:id,name,username',
            'student.schoolClass:id,name,level',
            'attempt:id,started_at,ends_at,submitted_at,status',
        ])->find($id);

        if (! $result) {
            return $this->errorResponse('Data hasil ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Student Authorization & Publication Protection
        if (in_array($userRole, ['student', 'peserta'], true)) {
            if (! $user->student || $result->student_id !== $user->student->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk melihat hasil ujian ini', null, 403);
            }

            $canView = $result->is_published || ($result->exam->show_result ?? false);
            if (! $canView) {
                return $this->errorResponse('Hasil ujian belum dipublikasikan oleh guru/admin', null, 403);
            }
        }

        return $this->successResponse($result, 'Detail hasil ujian berhasil diambil');
    }

    /**
     * Display result by attempt ID.
     */
    public function showByAttempt(Request $request, string $attemptId): JsonResponse
    {
        $attempt = ExamAttempt::find($attemptId);

        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        $result = Result::where('attempt_id', $attempt->id)->first();

        if (! $result) {
            return $this->errorResponse('Hasil ujian untuk sesi ini belum tersedia', null, 404);
        }

        return $this->show($request, (string) $result->id);
    }

    /**
     * Trigger grading for a specific attempt (Server Source of Truth).
     */
    public function grade(Request $request, string $attemptId): JsonResponse
    {
        $attempt = ExamAttempt::with('exam')->find($attemptId);

        if (! $attempt) {
            return $this->errorResponse('Sesi ujian tidak ditemukan', null, 404);
        }

        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        // Student can only grade/finalize their own attempt
        if (in_array($userRole, ['student', 'peserta'], true)) {
            if (! $user->student || $attempt->student_id !== $user->student->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk menilai sesi ujian ini', null, 403);
            }
        }

        $now = now();

        // Check if in_progress attempt has timed out
        if ($attempt->status === 'in_progress' && $now->gt($attempt->ends_at)) {
            $attempt->update([
                'status' => 'timeout',
                'last_activity_at' => $now,
            ]);
        }

        // Attempt must be submitted or timeout to be graded
        if (! in_array($attempt->status, ['submitted', 'timeout'], true)) {
            return $this->errorResponse('Sesi ujian masih berlangsung dan belum dapat dinilai', null, 400);
        }

        $result = $this->calculateAndStoreResult($attempt);

        return $this->successResponse($result, 'Penilaian ujian berhasil diproses', 200);
    }

    /**
     * Toggle or update result publication status (Admin & Teacher only).
     */
    public function publish(Request $request, string $id): JsonResponse
    {
        $result = Result::find($id);

        if (! $result) {
            return $this->errorResponse('Data hasil ujian tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'is_published' => ['required', 'boolean'],
        ]);

        $result->update([
            'is_published' => (bool) $validated['is_published'],
        ]);

        return $this->successResponse($result, 'Status publikasi hasil ujian berhasil diperbarui');
    }

    /**
     * Core grading calculation engine (Strict Server Source of Truth).
     * Calculates correct, wrong, unanswered counts and normalized final score.
     */
    public function calculateAndStoreResult(ExamAttempt $attempt): Result
    {
        $exam = $attempt->exam;

        return DB::transaction(function () use ($attempt, $exam) {
            // Load exam questions with their order and weight
            $examQuestions = ExamQuestion::where('exam_id', $exam->id)
                ->with('question.options')
                ->get();

            $totalQuestions = $examQuestions->count();

            // Load all answers submitted for this attempt
            $answers = Answer::where('attempt_id', $attempt->id)
                ->get()
                ->keyBy('question_id');

            $correctCount = 0;
            $wrongCount = 0;
            $unansweredCount = 0;
            $earnedMcScore = 0.00;
            $earnedEssayScore = 0.00;

            foreach ($examQuestions as $eq) {
                $question = $eq->question;
                $weight = (float) ($eq->weight > 0 ? $eq->weight : 1.00);
                $answer = $answers->get($eq->question_id);

                // Unanswered question condition
                if (! $answer || ($answer->selected_option_id === null && empty($answer->essay_answer))) {
                    $unansweredCount++;
                    if ($answer) {
                        $answer->update([
                            'is_correct' => false,
                            'earned_score' => 0.00,
                        ]);
                    }
                    continue;
                }

                // Multiple choice / Single choice question grading
                if (in_array($question->question_type, ['single_choice', 'multiple_choice'], true)) {
                    $selectedOption = QuestionOption::where('id', $answer->selected_option_id)
                        ->where('question_id', $question->id)
                        ->first();

                    if ($selectedOption && $selectedOption->is_correct) {
                        $correctCount++;
                        $earnedMcScore += $weight;
                        $answer->update([
                            'is_correct' => true,
                            'earned_score' => $weight,
                        ]);
                    } else {
                        $wrongCount++;
                        $answer->update([
                            'is_correct' => false,
                            'earned_score' => 0.00,
                        ]);
                    }
                } elseif ($question->question_type === 'essay') {
                    // Preserve existing manual essay score if already evaluated by teacher
                    $essayEarned = (float) ($answer->earned_score ?? 0.00);
                    $earnedEssayScore += $essayEarned;
                    if ($answer->is_correct === true) {
                        $correctCount++;
                    } elseif ($answer->is_correct === false) {
                        $wrongCount++;
                    }
                }
            }

            // Calculate normalized score (0 - 100 scale)
            $totalWeight = $examQuestions->sum(function ($eq) {
                return (float) ($eq->weight > 0 ? $eq->weight : 1.00);
            });

            $totalEarned = $earnedMcScore + $earnedEssayScore;
            $calculatedScore = 0.00;
            if ($totalWeight > 0) {
                $calculatedScore = round(($totalEarned / $totalWeight) * 100, 2);
            } elseif ($totalQuestions > 0) {
                $calculatedScore = round(($correctCount / $totalQuestions) * 100, 2);
            }

            $finalScore = $calculatedScore;

            // Atomic Upsert respecting unique constraint uk_attempt_result
            return Result::updateOrCreate(
                ['attempt_id' => $attempt->id],
                [
                    'exam_id' => $attempt->exam_id,
                    'student_id' => $attempt->student_id,
                    'correct_count' => $correctCount,
                    'wrong_count' => $wrongCount,
                    'unanswered_count' => $unansweredCount,
                    'mc_score' => $earnedMcScore,
                    'essay_score' => $earnedEssayScore,
                    'score' => $calculatedScore,
                    'final_score' => $finalScore,
                    'status' => 'completed',
                    'is_published' => (bool) ($exam->show_result ?? false),
                    'graded_at' => now(),
                ]
            );
        });
    }

    /**
     * Grade an essay answer manually (Admin & Teacher only).
     */
    public function gradeEssay(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $userRole = strtolower($user->role->name ?? '');

        if (! in_array($userRole, ['admin', 'teacher', 'guru'], true)) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk menilai soal essay', null, 403);
        }

        $result = Result::with('exam')->find($id);
        if (! $result) {
            return $this->errorResponse('Data hasil ujian tidak ditemukan', null, 404);
        }

        if ($userRole !== 'admin' && $result->exam->created_by !== $user->id) {
            return $this->errorResponse('Akses ditolak. Anda hanya dapat menilai hasil ujian yang Anda buat', null, 403);
        }

        $validated = $request->validate([
            'answer_id' => ['required', 'integer', 'exists:answers,id'],
            'earned_score' => ['required', 'numeric', 'min:0'],
            'is_correct' => ['nullable', 'boolean'],
        ]);

        $answer = Answer::where('id', $validated['answer_id'])
            ->where('attempt_id', $result->attempt_id)
            ->first();

        if (! $answer) {
            return $this->errorResponse('Jawaban tidak ditemukan pada sesi ujian ini', null, 404);
        }

        $examQuestion = ExamQuestion::where('exam_id', $result->exam_id)
            ->where('question_id', $answer->question_id)
            ->first();

        $maxScore = (float) ($examQuestion?->weight > 0 ? $examQuestion->weight : 100.0);
        $scoreGiven = min((float) $validated['earned_score'], $maxScore);

        $answer->update([
            'earned_score' => $scoreGiven,
            'is_correct' => $validated['is_correct'] ?? ($scoreGiven > 0),
        ]);

        $attempt = ExamAttempt::find($result->attempt_id);
        $updatedResult = $this->calculateAndStoreResult($attempt);

        return $this->successResponse($updatedResult, 'Penilaian soal essay berhasil disimpan');
    }
}
